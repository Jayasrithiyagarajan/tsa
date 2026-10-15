<?php

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Builds the view-models for the controlled documents and renders them to HTML / PDF.
 * The HTML views reproduce the TUV EAST forms (F-7.5.1-CL 01 checklist, F-7.5.1-06 report layout).
 */
final class DocumentService
{
    /** Who may open documents of this sub-job. Customers only see issued documents of their own company. */
    public static function canView(array $sj, string $doc = 'certificate'): bool
    {
        switch (Auth::role()) {
            case 'admin':
            case 'qc':
                return true;
            case 'inspector':
                return (int) $sj['inspector_id'] === Auth::id();
            case 'customer':
                if ((int) $sj['client_id'] !== Auth::clientId()) return false;
                return $sj['status'] === 'approved';
        }
        return false;
    }

    private static function person(?int $userId): ?array
    {
        if (!$userId) return null;
        $u = DB::one('SELECT u.id, u.name, u.signature_path, p.qualification FROM users u LEFT JOIN inspector_profiles p ON p.user_id = u.id WHERE u.id = ?', [$userId]);
        if ($u) $u['signature'] = upload_data_uri($u['signature_path']);
        return $u;
    }

    public static function checklistModel(int $subJobId): ?array
    {
        $sj = JobService::subJob($subJobId);
        $cl = DB::one('SELECT * FROM checklists WHERE sub_job_id = ?', [$subJobId]);
        if (!$sj || !$cl) return null;
        $tpl = DB::one('SELECT * FROM checklist_templates WHERE id = ?', [$cl['template_id']]);
        $items = DB::all(
            'SELECT i.*, r.result, r.remarks FROM checklist_template_items i
             LEFT JOIN checklist_responses r ON r.item_id = i.id AND r.checklist_id = ?
             WHERE i.template_id = ? ORDER BY i.sort_order, i.id',
            [$cl['id'], $tpl['id']]
        );
        $cert = DB::one('SELECT certificate_no, status, reviewer_id, issue_date FROM certificates WHERE sub_job_id = ?', [$subJobId]);
        $issued = ($cert['status'] ?? '') === 'issued';
        // report-type inspections (no certificate): number from the same pattern, reviewer + date from the QC approval
        $qc = !$cert && $sj['status'] === 'approved'
            ? DB::one("SELECT reviewer_id, created_at FROM qc_reviews WHERE sub_job_id = ? AND decision = 'approved' ORDER BY id DESC LIMIT 1", [$subJobId])
            : null;
        return [
            'sj' => $sj, 'checklist' => $cl, 'template' => $tpl, 'items' => $items,
            'certificate_no' => $cert['certificate_no'] ?? Numbering::certificate($sj),
            'qc_reviewer' => $qc ? self::person((int) $qc['reviewer_id']) : null,
            'qc_date' => $qc ? substr($qc['created_at'], 0, 10) : null,
            'inspector' => self::person((int) ($cl['inspector_id'] ?: $sj['inspector_id'])),
            'stats' => InspectionService::checklistStats((int) $cl['id']),
            'reviewer' => $issued && $cert['reviewer_id'] ? self::person((int) $cert['reviewer_id']) : ($qc ? self::person((int) $qc['reviewer_id']) : null),
            'cert_result' => DB::value('SELECT result FROM certificates WHERE sub_job_id = ?', [$subJobId]),
            'issue_date' => $issued ? $cert['issue_date'] : ($qc ? substr($qc['created_at'], 0, 10) : null),
            'data' => json_decode((string) ($cl['data'] ?? ''), true) ?: [],
            'photos' => array_map(fn ($a) => upload_data_uri($a['file_path']), DB::all(
                "SELECT file_path FROM attachments WHERE sub_job_id = ? AND kind = 'photo' AND mime LIKE 'image/%' ORDER BY on_certificate DESC, id LIMIT 12", [$subJobId])),
            'review_date' => $issued ? $cert['issue_date'] : ($qc ? substr($qc['created_at'], 0, 10) : null),
        ];
    }

    public static function certificateModel(int $subJobId): ?array
    {
        $sj = JobService::subJob($subJobId);
        $cert = DB::one('SELECT * FROM certificates WHERE sub_job_id = ?', [$subJobId]);
        if (!$sj || !$cert || !$cert['template_id']) return null;
        $tpl = DB::one('SELECT * FROM certificate_templates WHERE id = ?', [$cert['template_id']]);
        $data = json_decode($cert['data'] ?? '{}', true) ?: [];
        $fields = [];
        foreach (json_decode($tpl['fields'], true) ?: [] as $f) {
            $f['value'] = CertificateService::fieldValue($f, $sj, $cert, $data);
            $fields[] = $f;
        }
        $photoLimit = (($tpl['layout'] ?? '') === 'ec') ? 12 : 4;
        $photos = DB::all("SELECT * FROM attachments WHERE sub_job_id = ? AND on_certificate = 1 AND mime LIKE 'image/%' ORDER BY id LIMIT $photoLimit", [$subJobId]);
        foreach ($photos as &$p) {
            $p['src'] = upload_data_uri($p['file_path']);
        }
        unset($p);
        $verifyUrl = abs_url('verify/' . $cert['verify_token']);
        return [
            'sj' => $sj, 'cert' => $cert, 'template' => $tpl, 'fields' => $fields,
            'notes' => array_values(array_filter(array_map('trim', explode("\n", (string) $tpl['notes'])))),
            'options' => explode(',', $tpl['result_options']),
            'inspector' => self::person((int) ($cert['inspector_id'] ?: $sj['inspector_id'])),
            'reviewer' => self::person($cert['reviewer_id'] ? (int) $cert['reviewer_id'] : null),
            'photos' => $photos,
            'issued' => $cert['status'] === 'issued',
            'verify_url' => $verifyUrl,
            'qr' => self::qr($verifyUrl),
        ];
    }

    public static function stickerModel(int $stickerId): ?array
    {
        $st = DB::one(
            "SELECT s.*, c.certificate_no, c.verify_token, c.inspection_date, e.description eq_description, e.equipment_no,
                    cl.name client_name, u.name inspector_name
             FROM stickers s LEFT JOIN certificates c ON c.id = s.certificate_id LEFT JOIN equipment e ON e.id = s.equipment_id
             LEFT JOIN clients cl ON cl.id = e.client_id LEFT JOIN users u ON u.id = s.inspector_id WHERE s.id = ?",
            [$stickerId]
        );
        if (!$st) return null;
        $st['qr'] = $st['verify_token'] ? self::qr(abs_url('verify/' . $st['verify_token'])) : null;
        return $st;
    }

    public static function qr(string $text): string
    {
        $o = new QROptions(['outputType' => QRCode::OUTPUT_IMAGE_PNG, 'scale' => 4, 'quietzoneSize' => 1, 'eccLevel' => QRCode::ECC_M]);
        return (new QRCode($o))->render($text);   // data:image/png;base64,…
    }

    /** Branding assets embedded as data URIs so the same HTML renders identically in browser and PDF. */
    public static function brand(): array
    {
        static $b = null;
        if ($b === null) {
            $img = fn ($f) => 'data:' . mime_content_type(ROOT_PATH . '/assets/img/' . $f) . ';base64,' . base64_encode(file_get_contents(ROOT_PATH . '/assets/img/' . $f));
            $b = ['header' => $img('doc-header.jpg'), 'footer' => $img('doc-footer.gif'), 'stamp' => $img('stamp.png')];
        }
        return $b;
    }

    /** [regular, bold] TTF paths of Calibri-metric fonts, or null when none is installed. */
    public static function fontFiles(): ?array
    {
        static $set = false;
        if ($set === false) {
            $set = null;
            foreach ([
                [ROOT_PATH . '/storage/fonts/Carlito-Regular.ttf', ROOT_PATH . '/storage/fonts/Carlito-Bold.ttf'],
                ['C:/Windows/Fonts/calibri.ttf', 'C:/Windows/Fonts/calibrib.ttf'],
            ] as $pair) {
                if (is_file($pair[0]) && is_file($pair[1])) { $set = $pair; break; }
            }
        }
        return $set;
    }

    /**
     * Width in mm of $text in Calibri (bold or regular) at $pt points, measured from the real font so that
     * page breaks of the printed checklists land where they do in the Word forms. Null if no font/GD.
     */
    public static function textWidthMm(string $text, float $pt, bool $bold = true): ?float
    {
        static $cache = [];
        $fonts = self::fontFiles();
        if (!$fonts || !function_exists('imagettfbbox')) return null;
        $key = ($bold ? 'b' : 'r') . $pt . '|' . $text;
        if (!isset($cache[$key])) {
            $box = imagettfbbox($pt, 0, $fonts[$bold ? 1 : 0], $text);   // GD: size in pt at 96 dpi
            $cache[$key] = ($box[2] - $box[0]) * 25.4 / 96;
        }
        return $cache[$key];
    }

    public static function pdf(string $html, string $filename, bool $download): void
    {
        // The forms are set in Calibri. Use a project copy of Carlito (metric-compatible, OFL) if present in
        // storage/fonts, otherwise the Calibri installed on a Windows host; Dompdf falls back to DejaVu Sans.
        if (!is_dir(ROOT_PATH . '/storage/fonts')) mkdir(ROOT_PATH . '/storage/fonts', 0775, true);
        $fontSet = self::fontFiles();
        $opt = new Options();
        $opt->set('isRemoteEnabled', false);
        $opt->set('isFontSubsettingEnabled', true);
        $opt->set('defaultFont', 'DejaVu Sans');
        $opt->set('chroot', array_values(array_unique([ROOT_PATH, $fontSet ? dirname($fontSet[0]) : ROOT_PATH])));
        $opt->set('fontDir', ROOT_PATH . '/storage/fonts');
        $opt->set('fontCache', ROOT_PATH . '/storage/fonts');
        $opt->set('dpi', 96);
        $pdf = new Dompdf($opt);
        if ($fontSet) {
            $fm = $pdf->getFontMetrics();
            $fm->registerFont(['family' => 'Calibri', 'weight' => 'normal', 'style' => 'normal'], $fontSet[0]);
            $fm->registerFont(['family' => 'Calibri', 'weight' => 'bold', 'style' => 'normal'], $fontSet[1]);
        }
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        $pdf->stream($filename, ['Attachment' => $download]);
        exit;
    }
}
