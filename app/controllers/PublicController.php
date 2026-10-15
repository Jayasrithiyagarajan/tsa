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
        $this->view('public/verify', ['title' => 'Certificate Verification', 'q' => $cert['certificate_no'] ?? '', 'cert' => $cert, 'searched' => true], 'public');
    }

    /** Target of the QR code on the back of an inspector ID card: the card online + a validity message. */
    public function verifyCard(string $token): void
    {
        $card = preg_match('/^[a-f0-9]{32}$/', $token) ? InspectorCardService::find('c.verify_token = ?', [$token]) : null;
        $this->view('public/verify_card', [
            'title' => 'Inspector ID Verification',
            'm' => $card ? InspectorCardService::model($card) : null,
        ], 'public');
    }

    /** Only issued or revoked certificates are disclosed publicly — never drafts. */
    private function lookup(string $where, string $v): ?array
    {
        return DB::one(
            "SELECT c.certificate_no, c.status, c.result, c.inspection_date, c.issue_date, c.next_inspection_date, c.revoked_reason,
                    t.name type_name, e.description eq_description, e.equipment_no, cl.name client_name
             FROM certificates c JOIN sub_jobs sj ON sj.id = c.sub_job_id JOIN jobs j ON j.id = sj.job_id
             JOIN clients cl ON cl.id = j.client_id JOIN inspection_types t ON t.id = sj.inspection_type_id
             LEFT JOIN equipment e ON e.id = sj.equipment_id
             WHERE $where AND c.status IN ('issued','revoked') LIMIT 1",
            [$v]
        );
    }
}
