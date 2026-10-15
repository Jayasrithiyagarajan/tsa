<?php

final class AdminClientController extends Controller
{
    public function index(): void
    {
        $q = trim((string) input('q', ''));
        $p = [];
        $where = '1=1';
        if ($q !== '') { $where = '(c.name LIKE ? OR c.code LIKE ? OR c.email LIKE ? OR c.city LIKE ?)'; $p = array_fill(0, 4, "%$q%"); }
        $sql = "SELECT c.*, (SELECT COUNT(*) FROM jobs j WHERE j.client_id = c.id) jobs_n,
                   (SELECT COUNT(*) FROM jobs j WHERE j.client_id = c.id AND j.status NOT IN ('completed','cancelled')) open_n,
                   (SELECT COUNT(*) FROM equipment e WHERE e.client_id = c.id) eq_n,
                   (SELECT COUNT(*) FROM users u WHERE u.client_id = c.id) users_n
                FROM clients c WHERE $where ORDER BY c.name";
        if ($this->wantsCsv()) {
            csv_export('clients', DB::all($sql, $p), ['code' => 'Code', 'name' => 'Name', 'contact_person' => 'Contact', 'email' => 'Email', 'phone' => 'Phone', 'city' => 'City', 'jobs_n' => 'Jobs', 'eq_n' => 'Equipment', 'status' => 'Status']);
        }
        [$rows, $meta] = paginate($sql, $p);
        $this->view('admin/clients/index', compact('rows', 'meta', 'q') + ['title' => 'Clients', 'crumbs' => ['Clients' => null]]);
    }

    public function create(): void
    {
        $this->view('admin/clients/form', ['client' => null, 'title' => 'New Client', 'crumbs' => ['Clients' => 'admin/clients', 'New' => null]]);
    }

    public function store(): void
    {
        $errors = validate($_POST, ['code' => 'required|max:20', 'name' => 'required|max:190', 'email' => 'email', 'phone' => 'phone']);
        if ($errors) $this->failValidation($errors);
        $this->attempt(fn () => 'admin/clients/' . ClientService::create($_POST), 'Client created.');
    }

    public function show(string $id): void
    {
        $client = DB::one('SELECT * FROM clients WHERE id = ?', [(int) $id]);
        if (!$client) $this->notFound();
        $jobs = DB::all('SELECT * FROM jobs WHERE client_id = ? ORDER BY id DESC LIMIT 20', [$client['id']]);
        $equipment = DB::all('SELECT e.*, t.name type_name FROM equipment e LEFT JOIN inspection_types t ON t.id = e.inspection_type_id WHERE e.client_id = ? ORDER BY e.description', [$client['id']]);
        $users = DB::all("SELECT * FROM users WHERE client_id = ? AND role = 'customer'", [$client['id']]);
        $certs = (int) DB::value("SELECT COUNT(*) FROM certificates c JOIN sub_jobs s ON s.id = c.sub_job_id JOIN jobs j ON j.id = s.job_id WHERE j.client_id = ? AND c.status = 'issued'", [$client['id']]);
        $this->view('admin/clients/show', compact('client', 'jobs', 'equipment', 'users', 'certs') + [
            'types' => DB::all("SELECT id, name FROM inspection_types ORDER BY name"),
            'title' => $client['name'], 'crumbs' => ['Clients' => 'admin/clients', $client['name'] => null],
        ]);
    }

    public function update(string $id): void
    {
        $errors = validate($_POST, ['name' => 'required|max:190', 'email' => 'email', 'phone' => 'phone', 'status' => 'in:active,inactive']);
        if ($errors) $this->failValidation($errors);
        $this->attempt(function () use ($id) {
            DB::update('clients', [
                'name' => input('name'), 'contact_person' => nullable(input('contact_person')), 'email' => nullable(strtolower((string) input('email'))),
                'phone' => nullable(input('phone')), 'address' => nullable(input('address')), 'city' => nullable(input('city')),
                'vat_no' => nullable(input('vat_no')), 'status' => input('status', 'active'),
            ], 'id = ?', [(int) $id]);
            Audit::log('client.updated', 'client', (int) $id);
        }, 'Client saved.');
    }

    public function equipment(): void
    {
        $q = trim((string) input('q', ''));
        $client = (int) input('client', 0);
        $due = (int) input('due', 0);
        $where = ['1=1'];
        $p = [];
        if ($q !== '') { $where[] = '(e.description LIKE ? OR e.equipment_no LIKE ? OR e.serial_no LIKE ? OR e.manufacturer LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%", "%$q%"); }
        if ($client) { $where[] = 'e.client_id = ?'; $p[] = $client; }
        if ($due) { $where[] = 'e.next_inspection_date <= CURDATE() + INTERVAL ? DAY'; $p[] = $due; }
        $sql = "SELECT e.*, c.name client_name, t.name type_name FROM equipment e JOIN clients c ON c.id = e.client_id
                LEFT JOIN inspection_types t ON t.id = e.inspection_type_id WHERE " . implode(' AND ', $where) . ' ORDER BY e.next_inspection_date IS NULL, e.next_inspection_date, e.description';
        if ($this->wantsCsv()) {
            csv_export('equipment', DB::all($sql, $p), ['client_name' => 'Client', 'description' => 'Description', 'equipment_no' => 'Equipment ID', 'serial_no' => 'Serial',
                'manufacturer' => 'Manufacturer', 'model' => 'Model', 'capacity' => 'Capacity', 'type_name' => 'Inspection Type', 'last_inspection_date' => 'Last Inspection', 'next_inspection_date' => 'Next Due', 'status' => 'Status']);
        }
        [$rows, $meta] = paginate($sql, $p, 25);
        $this->view('admin/clients/equipment', compact('rows', 'meta', 'q', 'client', 'due') + [
            'clients' => DB::all('SELECT id, name FROM clients ORDER BY name'),
            'types' => DB::all("SELECT id, name FROM inspection_types ORDER BY name"),
            'title' => 'Equipment', 'crumbs' => ['Equipment' => null],
        ]);
    }

    public function saveEquipment(): void
    {
        $errors = validate($_POST, ['client_id' => 'required|int', 'description' => 'required|max:190', 'year_built' => 'int']);
        if ($errors) $this->failValidation($errors);
        $this->attempt(function () {
            $data = [
                'client_id' => (int) input('client_id'), 'inspection_type_id' => nullable(input('inspection_type_id')),
                'description' => strtoupper((string) input('description')), 'equipment_no' => nullable(input('equipment_no')), 'serial_no' => nullable(input('serial_no')),
                'manufacturer' => nullable(input('manufacturer')), 'model' => nullable(input('model')), 'capacity' => nullable(input('capacity')),
                'year_built' => nullable(input('year_built')), 'location' => nullable(input('location')),
                'status' => in_array(input('status'), ['active', 'out_of_service', 'retired'], true) ? input('status') : 'active',
            ];
            if ($id = (int) input('id')) {
                DB::update('equipment', $data, 'id = ?', [$id]);
                Audit::log('equipment.updated', 'equipment', $id);
            } else {
                $id = DB::insert('equipment', $data);
                Audit::log('equipment.created', 'equipment', $id);
            }
        }, 'Equipment saved.');
    }
}
