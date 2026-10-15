<?php

/** Inspector ID cards: issue, print (front / back as separate CR80 pages), hand over, validate, revoke. */
final class AdminCardController extends Controller
{
    public function index(): void
    {
        $status = (string) input('status', '');
        $q = trim((string) input('q', ''));
        $today = date('Y-m-d');
        $where = ['1=1'];
        $p = [];
        if ($status === 'pending') { $where[] = "c.status = 'active' AND c.handed_over_at IS NULL"; }
        if ($status === 'valid') { $where[] = "c.status = 'active' AND c.handed_over_at IS NOT NULL AND c.expiry_date >= ?"; $p[] = $today; }
        if ($status === 'expired') { $where[] = "c.status = 'active' AND c.handed_over_at IS NOT NULL AND c.expiry_date < ?"; $p[] = $today; }
        if ($status === 'revoked') { $where[] = "c.status = 'revoked'"; }
        if ($q !== '') { $where[] = '(c.card_no LIKE ? OR c.holder_name LIKE ? OR c.id_number LIKE ? OR c.title LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%", "%$q%"); }
        $sql = "SELECT c.*, u.status user_status, u.email user_email FROM inspector_cards c JOIN users u ON u.id = c.user_id
                WHERE " . implode(' AND ', $where) . " ORDER BY c.status = 'revoked', c.handed_over_at IS NOT NULL, c.id DESC";
        if ($this->wantsCsv()) {
            csv_export('inspector-id-cards', DB::all($sql, $p), ['card_no' => 'ID Code', 'holder_name' => 'Name', 'id_number' => 'Iqama / ID', 'company' => 'Company', 'title' => 'Title',
                'certification' => 'Certification', 'issue_date' => 'Issue Date', 'expiry_date' => 'Expiry Date', 'handed_over_at' => 'Handed Over', 'status' => 'Status', 'revoked_reason' => 'Revoked Reason']);
        }
        [$rows, $meta] = paginate($sql, $p, 25);
        $counts = DB::one(
            "SELECT SUM(status = 'active' AND handed_over_at IS NULL) pending,
                    SUM(status = 'active' AND handed_over_at IS NOT NULL AND expiry_date >= ?) valid,
                    SUM(status = 'active' AND handed_over_at IS NOT NULL AND expiry_date < ?) expired, SUM(status = 'revoked') revoked,
                    SUM(status = 'active' AND handed_over_at IS NOT NULL AND expiry_date BETWEEN ? AND ?) expiring FROM inspector_cards",
            [$today, $today, $today, date('Y-m-d', strtotime('+30 days'))]
        );
        $this->view('admin/cards/index', compact('rows', 'meta', 'status', 'q', 'counts') + ['title' => 'Inspector ID Cards', 'crumbs' => ['Inspector ID Cards' => null]]);
    }

    private function people(): array
    {
        return DB::all(
            "SELECT u.id, u.name, u.role, p.employee_no, p.qualification, p.photo_path,
                    (SELECT GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR '\n') FROM inspector_skills k JOIN inspection_types t ON t.id = k.inspection_type_id WHERE k.user_id = u.id) skills
             FROM users u LEFT JOIN inspector_profiles p ON p.user_id = u.id
             WHERE u.role IN ('inspector','qc') AND u.status = 'active' ORDER BY FIELD(u.role,'inspector','qc'), u.name"
        );
    }

    public function create(): void
    {
        $last = (int) input('user') ? DB::one("SELECT * FROM inspector_cards WHERE user_id = ? ORDER BY id DESC LIMIT 1", [(int) input('user')]) : null;
        $this->view('admin/cards/form', [
            'card' => null, 'renew' => $last, 'people' => $this->people(), 'userId' => (int) input('user'),
            'title' => 'Issue ID card', 'crumbs' => ['Inspector ID Cards' => 'admin/cards', 'Issue' => null],
        ]);
    }

    public function edit(string $id): void
    {
        $card = InspectorCardService::find('c.id = ?', [(int) $id]);
        if (!$card) $this->notFound();
        $this->view('admin/cards/form', [
            'card' => $card, 'renew' => null, 'people' => $this->people(), 'userId' => (int) $card['user_id'],
            'model' => InspectorCardService::model($card),
            'title' => $card['card_no'], 'crumbs' => ['Inspector ID Cards' => 'admin/cards', $card['card_no'] => null],
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
        $errors = validate($_POST, [
            'user_id' => 'required|int', 'holder_name' => 'required|max:120', 'id_number' => 'max:30', 'company' => 'required|max:120',
            'title' => 'required|max:80', 'card_no' => 'max:40', 'job_ref' => 'max:60', 'certification' => 'max:120', 'assessor' => 'max:120',
            'competence' => 'max:1000', 'issue_date' => 'required|date', 'expiry_date' => 'required|date',
        ]);
        if ($errors) $this->failValidation($errors);
        $this->attempt(function () use ($id) {
            $photo = !empty($_FILES['photo']['name']) ? store_upload($_FILES['photo'], 'id-photos', ['image/jpeg', 'image/png', 'image/webp']) : null;
            $id = InspectorCardService::save($id, $_POST, $photo);
            return 'admin/cards/' . $id;
        }, $id ? 'ID card updated.' : 'ID card issued. Print the front and back from the preview.');
    }

    public function handover(string $id): void
    {
        $this->attempt(fn () => InspectorCardService::handOver((int) $id), 'Card marked as handed over. It is now valid and visible in the inspector portal.');
    }

    /** Staff check of a card: scan the QR / paste its link, or type the ID code. Shows everything, unmasked. */
    public function validate(): void
    {
        $q = trim((string) input('q', ''));
        $card = $q !== '' ? InspectorCardService::lookup($q) : null;
        if ($card) Audit::log('id_card.validated', 'inspector_card', (int) $card['id']);
        $this->view('admin/cards/validate', [
            'q' => $q, 'searched' => $q !== '',
            'm' => $card ? InspectorCardService::model($card) : null,
            'history' => $card ? DB::all('SELECT c.*, h.name handed_by FROM inspector_cards c LEFT JOIN users h ON h.id = c.handed_over_by WHERE c.user_id = ? ORDER BY c.id DESC', [$card['user_id']]) : [],
            'profile' => $card ? DB::one('SELECT u.name, u.email, u.phone, u.role, u.status, p.employee_no, p.qualification, p.cert_no, p.cert_expiry, p.base_location
                                         FROM users u LEFT JOIN inspector_profiles p ON p.user_id = u.id WHERE u.id = ?', [$card['user_id']]) : null,
            'title' => 'Validate ID Card', 'crumbs' => ['Inspector ID Cards' => 'admin/cards', 'Validate' => null],
        ]);
    }

    public function revoke(string $id): void
    {
        $this->attempt(fn () => InspectorCardService::revoke((int) $id, (string) input('reason', '')), 'ID card revoked. Scanning its QR code now shows it as revoked.');
    }

    public function print(string $id): void
    {
        $card = InspectorCardService::find('c.id = ?', [(int) $id]);
        if (!$card) $this->notFound();
        $side = in_array($_GET['side'] ?? '', ['front', 'back', 'both'], true) ? $_GET['side'] : 'front';
        $m = InspectorCardService::model($card);
        $pdf = !empty($_GET['download']) || !empty($_GET['pdf']);
        $html = View::partial('documents/id_card', ['m' => $m, 'side' => $side, 'toolbar' => $pdf ? null : ['back' => url('admin/cards/' . $card['id'])]]);
        if ($pdf) {
            Audit::log('id_card.downloaded', 'inspector_card', (int) $card['id'], ['side' => $side]);
            $pt = 72 / 25.4;
            DocumentService::pdf($html, 'ID-Card-' . $card['card_no'] . '-' . $side . '.pdf', !empty($_GET['download']),
                [0, 0, InspectorCardService::W_MM * $pt, InspectorCardService::H_MM * $pt]);
        }
        echo $html;
    }

    /** Card-wide texts: back-side e-mails, footer address and the accreditation logo strip under the photo. */
    public function settings(): void
    {
        $this->attempt(function () {
            foreach (['card_emails', 'card_address'] as $k) {
                DB::run('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', [$k, mb_substr(trim((string) input($k)), 0, 255)]);
            }
            if (!empty($_FILES['card_logos']['name'])) {
                $f = store_upload($_FILES['card_logos'], 'branding', ['image/png', 'image/jpeg', 'image/webp']);
                DB::run('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', ['card_logos_path', $f['path']]);
            } elseif (input('reset_logos')) {
                DB::run("DELETE FROM settings WHERE `key` = 'card_logos_path'");
            }
            Audit::log('settings.updated', 'settings', null, ['scope' => 'id_card']);
        }, 'Card settings saved.');
    }

    /** Profile ID photo of a user: admins see all, inspectors / QC only their own. */
    public function userPhoto(string $userId): void
    {
        if (!Auth::is('admin') && Auth::id() !== (int) $userId) $this->forbid();
        $p = InspectorCardService::profilePhoto((int) $userId);
        if (!$p || !is_file(upload_abs($p))) $this->notFound();
        header('Content-Type: ' . mime_content_type(upload_abs($p)));
        header('Cache-Control: private, no-store');
        readfile(upload_abs($p));
        exit;
    }

    /** Photos are private: served to staff only (they are embedded in the card for everyone else). */
    public function photo(string $id): void
    {
        $p = DB::value('SELECT photo_path FROM inspector_cards WHERE id = ?', [(int) $id]);
        if (!$p || !is_file(upload_abs($p))) $this->notFound();
        header('Content-Type: ' . mime_content_type(upload_abs($p)));
        header('Cache-Control: private, no-store');
        readfile(upload_abs($p));
        exit;
    }
}
