<?php

final class PublicController extends Controller
{
    public function home(): void
    {
        $types = DB::all("SELECT * FROM inspection_types WHERE status = 'active' ORDER BY category, name");
        $this->view('public/home', ['title' => 'Inspection Services', 'types' => $types], 'public');
    }

    public function requestForm(): void
    {
        $types = DB::all("SELECT id, name, category FROM inspection_types WHERE status = 'active' ORDER BY name");
        $this->view('public/request', ['title' => 'Request an Inspection', 'types' => $types], 'public');
    }

    /** Website channel. Honeypot + per-session rate limit instead of a CAPTCHA. */
    public function requestSubmit(): void
    {
        if (!empty($_POST['website'])) {               // honeypot field, invisible to humans
            redirect('request/thanks/received');
        }
        $recent = array_filter($_SESSION['web_requests'] ?? [], fn ($t) => $t > time() - 3600);
        if (count($recent) >= 5) {
            flash('error', 'Too many requests from this browser. Please call us or email info@tuv-east.com.');
            redirect('request');
        }
        $errors = validate($_POST, [
            'company_name' => 'required|max:190', 'contact_name' => 'required|max:120',
            'email' => 'required|email|max:190', 'phone' => 'required|phone', 'location' => 'required|max:190',
            'preferred_date' => 'date', 'description' => 'max:2000',
        ]);
        $items = array_values(array_filter($_POST['items'] ?? [], fn ($i) => is_array($i) && trim($i['equipment_description'] ?? '') !== ''));
        if (!$items) $errors['items'] = 'Add at least one equipment item.';
        if ($errors) $this->failValidation($errors);

        $id = RequestService::create($_POST, $items, 'website');
        $recent[] = time();
        $_SESSION['web_requests'] = $recent;
        redirect('request/thanks/' . DB::value('SELECT request_no FROM service_requests WHERE id = ?', [$id]));
    }

    public function requestThanks(string $no): void
    {
        $this->view('public/thanks', ['title' => 'Request received', 'no' => preg_match('/^REQ-\d{4}-\d+$/', $no) ? $no : null], 'public');
    }

    public function verify(): void
    {
        $q = trim((string) input('certificate_no', ''));
        $cert = $q !== '' ? $this->lookup('c.certificate_no = ?', $q) : null;
        $this->view('public/verify', ['title' => 'Verify a Certificate', 'q' => $q, 'cert' => $cert, 'searched' => $q !== ''], 'public');
    }

    public function verifyToken(string $token): void
    {
        $cert = preg_match('/^[a-f0-9]{32}$/', $token) ? $this->lookup('c.verify_token = ?', $token) : null;
        $this->view('public/verify', ['title' => 'Certificate Verification', 'q' => $cert['certificate_no'] ?? '', 'cert' => $cert, 'searched' => true, 'docs' => $this->docLinks($cert, $token)], 'public');
    }

    /** QR on a sticker. Discloses the certificate only once QC has issued it; otherwise just the sticker state. */
    public function stickerScan(string $token): void
    {
        $st = preg_match('/^[a-f0-9]{32}$/', $token)
            ? DB::one('SELECT s.sticker_no, s.status, s.color, s.sub_job_id, c.certificate_no FROM stickers s LEFT JOIN certificates c ON c.sub_job_id = s.sub_job_id WHERE s.verify_token = ?', [$token])
            : null;
        $cert = $st && $st['status'] === 'closed' && $st['certificate_no'] ? $this->lookup('c.certificate_no = ?', $st['certificate_no']) : null;
        $this->view('public/verify', ['title' => 'Sticker Verification', 'q' => $cert['certificate_no'] ?? '', 'cert' => $cert, 'sticker' => $st ?: ['sticker_no' => null], 'searched' => true, 'docs' => $this->docLinks($cert, $token)], 'public');
    }

    /** Target of the QR code on the back of an inspector ID card: the card online + a validity message. */
    public function verifyCard(string $token): void
    {
        $card = preg_match('/^[a-f0-9]{32}$/', $token) ? InspectorCardService::find('c.verify_token = ?', [$token]) : null;
        $this->view('public/verify_card', [
            'title' => 'ID Card Verification',
            'm' => $card ? InspectorCardService::model($card) : null,
        ], 'public');
    }

    /** Download links shown after a QR scan (not after typing a certificate number): certificate, checklist and sticker, when they exist. */
    private function docLinks(?array $cert, string $token): array
    {
        if (!$cert || $cert['status'] !== 'issued') return [];
        $base = 'verify/doc/' . $token . '/';
        $links = [['label' => 'Certificate', 'icon' => 'award', 'url' => url($base . 'certificate') . '?download=1']];
        if (DB::value('SELECT id FROM checklists WHERE sub_job_id = ?', [$cert['sub_job_id']])) {
            $links[] = ['label' => 'Checklist', 'icon' => 'clipboard', 'url' => url($base . 'checklist') . '?download=1'];
        }
        if (DB::value("SELECT id FROM stickers WHERE sub_job_id = ? AND status = 'closed' LIMIT 1", [$cert['sub_job_id']])) {
            $links[] = ['label' => 'Sticker', 'icon' => 'tag', 'url' => url($base . 'sticker')];
        }
        return $links;
    }

    /** Only issued or revoked certificates are disclosed publicly — never drafts. Released as soon as QC approves the sub-job. */
    private function lookup(string $where, string $v): ?array
    {
        return DB::one(
            "SELECT c.certificate_no, c.status, c.result, c.inspection_date, c.issue_date, c.next_inspection_date, c.revoked_reason,
                    t.name type_name, e.description eq_description, e.equipment_no, cl.name client_name,
                    sj.id AS sub_job_id, sj.sub_job_no, sj.approved_at, j.job_no, j.location,
                    e.serial_no eq_serial_no, e.manufacturer, e.model eq_model, e.capacity eq_capacity, e.year_built eq_year,
                    iu.name inspector_name, ru.name reviewer_name,
                    (SELECT s.sticker_no FROM stickers s WHERE s.sub_job_id = sj.id AND s.status = 'closed' LIMIT 1) sticker_no
             FROM certificates c JOIN sub_jobs sj ON sj.id = c.sub_job_id JOIN jobs j ON j.id = sj.job_id
             JOIN clients cl ON cl.id = j.client_id JOIN inspection_types t ON t.id = sj.inspection_type_id
             LEFT JOIN equipment e ON e.id = sj.equipment_id
             LEFT JOIN users iu ON iu.id = sj.inspector_id
             LEFT JOIN users ru ON ru.id = c.reviewer_id
             WHERE $where AND c.status IN ('issued','revoked') LIMIT 1",   // issued = QC has approved the sub-job
            [$v]
        );
    }
}
