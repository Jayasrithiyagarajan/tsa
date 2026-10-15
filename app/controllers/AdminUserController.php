<?php

final class AdminUserController extends Controller
{
    public function index(): void
    {
        $role = (string) input('role', '');
        $q = trim((string) input('q', ''));
        $where = ['1=1'];
        $p = [];
        if ($role === 'admin') { $where[] = "u.role IN ('superadmin','admin')"; }
        elseif (in_array($role, ['inspector', 'qc', 'customer'], true)) { $where[] = 'u.role = ?'; $p[] = $role; }
        if ($q !== '') { $where[] = '(u.name LIKE ? OR u.email LIKE ?)'; array_push($p, "%$q%", "%$q%"); }
        $sql = "SELECT u.*, c.name client_name, pr.qualification, pr.cert_expiry, pr.employee_no,
                  (SELECT GROUP_CONCAT(t.code ORDER BY t.code SEPARATOR ', ') FROM inspector_skills k JOIN inspection_types t ON t.id = k.inspection_type_id WHERE k.user_id = u.id) skills,
                  (SELECT COUNT(*) FROM sub_jobs s WHERE s.inspector_id = u.id AND s.status IN ('assigned','in_progress','rework')) open_load
                FROM users u LEFT JOIN clients c ON c.id = u.client_id LEFT JOIN inspector_profiles pr ON pr.user_id = u.id
                WHERE " . implode(' AND ', $where) . " ORDER BY FIELD(u.role,'superadmin','admin','qc','inspector','customer'), u.name";
        if ($this->wantsCsv()) {
            csv_export('users', DB::all($sql, $p), ['name' => 'Name', 'email' => 'Email', 'role' => 'Role', 'client_name' => 'Client', 'qualification' => 'Qualification', 'skills' => 'Qualified For', 'status' => 'Status', 'last_login_at' => 'Last Login']);
        }
        [$rows, $meta] = paginate($sql, $p, 25);
        $counts = array_column(DB::all('SELECT role, COUNT(*) n FROM users GROUP BY role'), 'n', 'role');
        $counts['admin'] = (int) ($counts['admin'] ?? 0) + (int) ($counts['superadmin'] ?? 0);   // "Admins" tab lists both
        unset($counts['superadmin']);
        $this->view('admin/users/index', compact('rows', 'meta', 'role', 'q', 'counts') + ['title' => 'Users', 'crumbs' => ['Users & Inspectors' => null]]);
    }

    private function formData(?array $user): array
    {
        return [
            'user' => $user,
            'profile' => $user ? DB::one('SELECT * FROM inspector_profiles WHERE user_id = ?', [$user['id']]) : null,
            'skills' => $user ? array_map('intval', array_column(DB::all('SELECT inspection_type_id FROM inspector_skills WHERE user_id = ?', [$user['id']]), 'inspection_type_id')) : [],
            'types' => DB::all("SELECT id, code, name FROM inspection_types ORDER BY name"),
            'clients' => DB::all("SELECT id, name FROM clients ORDER BY name"),
        ];
    }

    public function create(): void
    {
        $this->view('admin/users/form', $this->formData(null) + [
            'title' => 'New User', 'crumbs' => ['Users & Inspectors' => 'admin/users', 'New' => null],
        ]);
    }

    public function edit(string $id): void
    {
        $user = DB::one('SELECT * FROM users WHERE id = ?', [(int) $id]);
        if (!$user) $this->notFound();
        $this->view('admin/users/form', $this->formData($user) + [
            'title' => $user['name'], 'crumbs' => ['Users & Inspectors' => 'admin/users', $user['name'] => null],
        ]);
    }

    public function store(): void
    {
        $this->save(null);
    }

    public function update(string $id): void
    {
        $this->save((int) $id);
    }

    private function save(?int $id): void
    {
        $rules = ['name' => 'required|max:120', 'email' => 'required|email|max:190', 'role' => 'required|in:superadmin,admin,inspector,qc,customer', 'phone' => 'phone', 'cert_expiry' => 'date'];
        if (!$id || input('password')) $rules['password'] = ($id ? '' : 'required|') . 'min:8';
        $errors = validate($_POST, $rules);
        $email = strtolower((string) input('email'));
        if (DB::value('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $id ?? 0])) $errors['email'] = 'This email is already registered.';
        if (input('role') === 'customer' && !input('client_id')) $errors['client_id'] = 'Customer users must belong to a client.';
        if ($id === Auth::id() && (input('role') !== 'superadmin' || input('status') === 'inactive')) $errors['role'] = 'You cannot demote or deactivate your own account.';
        if ($errors) $this->failValidation($errors);

        $this->attempt(function () use ($id, $email) {
            $data = [
                'name' => input('name'), 'email' => $email, 'role' => input('role'), 'phone' => nullable(input('phone')),
                'client_id' => input('role') === 'customer' ? (int) input('client_id') : null,
                'status' => input('status') === 'inactive' ? 'inactive' : 'active',
            ];
            if (input('password')) $data['password_hash'] = password_hash((string) input('password'), PASSWORD_DEFAULT);
            DB::tx(function () use (&$id, $data) {
                if ($id) {
                    DB::update('users', $data, 'id = ?', [$id]);
                    Audit::log('user.updated', 'user', $id, ['role' => $data['role'], 'password_reset' => isset($data['password_hash'])]);
                } else {
                    $id = DB::insert('users', $data);
                    Audit::log('user.created', 'user', $id, ['role' => $data['role']]);
                }
                if (in_array($data['role'], ['inspector', 'qc'], true)) {
                    DB::run(
                        'INSERT INTO inspector_profiles (user_id, employee_no, qualification, cert_no, cert_expiry, base_location) VALUES (?,?,?,?,?,?)
                         ON DUPLICATE KEY UPDATE employee_no=VALUES(employee_no), qualification=VALUES(qualification), cert_no=VALUES(cert_no), cert_expiry=VALUES(cert_expiry), base_location=VALUES(base_location)',
                        [$id, nullable(input('employee_no')), nullable(input('qualification')), nullable(input('cert_no')), nullable(input('cert_expiry')), nullable(input('base_location'))]
                    );
                }
                DB::run('DELETE FROM inspector_skills WHERE user_id = ?', [$id]);
                if ($data['role'] === 'inspector') {
                    foreach (array_unique(array_map('intval', (array) ($_POST['skills'] ?? []))) as $t) {
                        DB::insert('inspector_skills', ['user_id' => $id, 'inspection_type_id' => $t]);
                    }
                }
            });
            if (!empty($_FILES['signature']['name'])) {
                $f = store_upload($_FILES['signature'], 'signatures', ['image/png', 'image/jpeg']);
                DB::update('users', ['signature_path' => $f['path']], 'id = ?', [$id]);
            }
            if (!empty($_FILES['id_photo']['name']) && in_array(input('role'), ['inspector', 'qc'], true)) {
                $f = store_upload($_FILES['id_photo'], 'id-photos', ['image/jpeg', 'image/png', 'image/webp']);
                InspectorCardService::setProfilePhoto($id, $f['path']);
            }
            return 'admin/users/' . $id;
        }, 'User saved.');
    }
}
