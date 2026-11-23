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
            case 'superadmin':
            case 'admin':
            case 'qc':
                return true;
            case 'inspector':
                return (int) $sj['inspector_id'] === Auth::id();
            case 'customer':
                if ((int) $sj['client_id'] !== Auth::clientId()) return false;
                return $sj['status'] === 'approved' && $sj['job_status'] === 'completed';   // released by the admin's final approval
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
                "SELECT file_path FROM attachments WHERE sub_job_id = ? AND kind = 'photo' AND mime LIKE 'image/%' ORDER BY on_certificate DESC, " . InspectionService::attachmentOrder() . " LIMIT 12", [$subJobId])),
            'review_date' => $issued ? $cert['issue_date'] : ($qc ? substr($qc['created_at'], 0, 10) : null),
        ];
    }

    /** How many certificate photos each printed format has room for, with a note for the upload tab. */
    public static function photoCapacity(?string $layout): array
    {
        return [
            'ec' => ['max' => 30, 'note' => 'The first 2 print inside the form, the rest on continuation sheets of 8.'],
            'sc' => ['max' => 19, 'note' => 'Up to 3 drawings (caption containing "draw") plus 16 inspection pictures.'],
            'ss' => ['max' => 66, 'note' => 'Up to 2 drawings (caption containing "draw") plus 64 inspection pictures.'],
            'mo' => ['max' => 7, 'note' => '1 VIN photo (the first) plus 6 inspection pictures.'],
            'tr' => ['max' => 6, 'note' => 'Trailer pictures, 2 x 3.'],
            'ch' => ['max' => 6, 'note' => 'Report pictures.'],
            'pp' => ['max' => 8, 'note' => 'Vehicle pictures, 2 x 4.'],
            'df' => ['max' => 4, 'note' => 'Photos in the image area.'],
            'ut' => ['max' => 4, 'note' => 'Photos in the box beside the readings.'],
            'dt' => ['max' => 1, 'note' => 'One photo fills the image area (the first).'],
            'lp' => ['max' => 0, 'note' => ''], 'dr' => ['max' => 0, 'note' => ''], 'es' => ['max' => 0, 'note' => ''], 'li' => ['max' => 0, 'note' => ''],
            'ld' => ['max' => 0, 'note' => ''], 'sp' => ['max' => 0, 'note' => ''], 'an' => ['max' => 0, 'note' => ''],
        ][(string) $layout] ?? ['max' => 4, 'note' => 'Photos in the certificate image area.'];
    }

    public static function certificateModel(int $subJobId, int $seq = 1): ?array
    {
        $sj = JobService::subJob($subJobId);
        $cert = DB::one('SELECT * FROM certificates WHERE sub_job_id = ? AND seq = ?', [$subJobId, $seq]);
        if (!$sj || !$cert || !$cert['template_id']) return null;
        $tpl = DB::one('SELECT * FROM certificate_templates WHERE id = ?', [$cert['template_id']]);
        // a result statement cut off by the old 255-character column is restored from the template's full default text
        $defSt = (string) ($tpl['default_statement'] ?? ''); $curSt = (string) ($cert['result_statement'] ?? '');
        if ($defSt !== '' && strlen($curSt) >= 200 && strlen($defSt) > strlen($curSt) && strncmp($defSt, $curSt, strlen($curSt)) === 0) $cert['result_statement'] = $defSt;
        $data = json_decode($cert['data'] ?? '{}', true) ?: [];
        $fields = [];
        foreach (json_decode($tpl['fields'], true) ?: [] as $f) {  // (the extra Capacity / SWL entry feeds the sticker only, so printed layouts are untouched)
            $f['value'] = CertificateService::fieldValue($f, $sj, $cert, $data);
            $fields[] = $f;
        }
        $photoLimit = self::photoCapacity($tpl['layout'] ?? null)['max'];
        // photos tagged for this page, plus untagged ones (cert_seq NULL = every page); page-specific photos print first
        $photos = DB::all("SELECT * FROM attachments WHERE sub_job_id = ? AND on_certificate = 1 AND mime LIKE 'image/%' AND (cert_seq IS NULL OR cert_seq = ?) ORDER BY cert_seq IS NULL, " . InspectionService::attachmentOrder() . " LIMIT $photoLimit", [$subJobId, $seq]);
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
            "SELECT s.*, c.certificate_no, c.result, c.inspection_date, c.next_inspection_date, c.data cert_data,
                    e.description eq_description, e.equipment_no, e.serial_no, e.capacity, t.name type_name,
                    cl.name client_name, u.name inspector_name, sj.sub_job_no
             FROM stickers s LEFT JOIN sub_jobs sj ON sj.id = s.sub_job_id LEFT JOIN certificates c ON c.sub_job_id = s.sub_job_id LEFT JOIN equipment e ON e.id = s.equipment_id
             LEFT JOIN inspection_types t ON t.id = e.inspection_type_id
             LEFT JOIN clients cl ON cl.id = e.client_id LEFT JOIN users u ON u.id = s.inspector_id WHERE s.id = ?",
            [$stickerId]
        );
        if (!$st) return null;
        // Blue = Accepted. Red = Failed: a red (rejected) sticker, or a certificate that did not pass outright.
        $st['passed'] = $st['color'] !== 'red' && !in_array($st['result'], ['rejected', 'repair'], true);
        $st['next_date'] = $st['next_inspection_date'] ?: $st['expiry_date'];
        // capacity: what the inspector entered on the certificate / checklist wins; otherwise the equipment register
        $cd = json_decode((string) ($st['cert_data'] ?? ''), true) ?: [];
        $cap = '';
        foreach (['swl', 'capacity', 'rated_capacity', 'rated_load'] as $k) { if (trim((string) ($cd[$k] ?? '')) !== '') { $cap = trim((string) $cd[$k]); break; } }
        if ($cap === '' && trim((string) ($st['capacity'] ?? '')) === '' && !empty($st['sub_job_id'])) {
            $cl = DB::one('SELECT data FROM checklists WHERE sub_job_id = ?', [$st['sub_job_id']]);
            $ld = json_decode((string) ($cl['data'] ?? ''), true) ?: [];
            foreach (['swl', 'capacity', 'rated_capacity', 'rated_load'] as $k) { if (trim((string) ($ld[$k] ?? '')) !== '') { $cap = trim((string) $ld[$k]); break; } }
        }
        if ($cap !== '') $st['capacity'] = $cap;
        return $st;
    }

    /** QR printed on a sticker: opens the public sticker check (/s/{token}). SVG keeps it sharp and fast in bulk. */
    public static function stickerQr(?string $token): ?string
    {
        if (!$token) return null;
        $o = new QROptions(['outputType' => QRCode::OUTPUT_MARKUP_SVG, 'quietzoneSize' => 1, 'eccLevel' => QRCode::ECC_M, 'addQuietzone' => true]);
        $base = rtrim((string) config('public_url', ''), '/');
        return (new QRCode($o))->render(($base !== '' ? $base . '/s/' . $token : abs_url('s/' . $token)));
    }

    /** Sticker artwork (logo, UAF mark, LEEA full member) as data URIs. */
    public static function stickerArt(): array
    {
        $img = fn ($f) => 'data:image/png;base64,' . base64_encode(file_get_contents(ROOT_PATH . '/assets/img/' . $f));
        return ['logo' => $img('sticker-tuv.png'), 'uaf' => $img('sticker-uaf.png'), 'leea' => $img('sticker-leea.png')];
    }

    public static function qr(string $text): string
    {
        $o = new QROptions(['outputType' => QRCode::OUTPUT_IMAGE_PNG, 'scale' => 4, 'quietzoneSize' => 1, 'eccLevel' => QRCode::ECC_M]);
        return (new QRCode($o))->render($text);   // data:image/png;base64,…
    }

    /** QR for the printed ID card: vector (SVG) so it stays razor-sharp at any print resolution. */
    public static function cardQr(string $text): string
    {
        $o = new QROptions(['outputType' => QRCode::OUTPUT_MARKUP_SVG, 'quietzoneSize' => 1, 'addQuietzone' => true, 'eccLevel' => QRCode::ECC_M]);
        return (new QRCode($o))->render($text);   // data:image/svg+xml;base64,…
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
     * Dompdf looks up its bundled DejaVu Sans (the font that draws all Arabic) by bare name inside "fontDir".
     * Because fontDir is storage/fonts, the TTFs must be there too, otherwise Arabic falls back to a core font
     * and prints as garbage on hosts that have no system fonts (cPanel / Linux). Copy them from vendor if missing.
     */
    private static function ensureBundledFonts(string $dir): void
    {
        $src = ROOT_PATH . '/vendor/dompdf/dompdf/lib/fonts';
        foreach (['DejaVuSans', 'DejaVuSans-Bold', 'DejaVuSans-Oblique', 'DejaVuSans-BoldOblique'] as $f) {
            foreach (['.ttf', '.ufm'] as $ext) {
                if (!is_file("$dir/$f$ext") && is_file("$src/$f$ext")) @copy("$src/$f$ext", "$dir/$f$ext");
            }
        }
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

    /** A configured Dompdf instance (fonts registered, local files only). */
    private static function newDompdf(): Dompdf
    {
        if (!is_dir(ROOT_PATH . '/storage/fonts')) mkdir(ROOT_PATH . '/storage/fonts', 0775, true);
        self::ensureBundledFonts(ROOT_PATH . '/storage/fonts');
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
        return $pdf;
    }

    /** @param string|float[] $paper Dompdf paper name or [0, 0, width_pt, height_pt] */
    public static function pdf(string $html, string $filename, bool $download, $paper = 'A4'): void
    {
        // The forms are set in Calibri. Use a project copy of Carlito (metric-compatible, OFL) if present in
        // storage/fonts, otherwise the Calibri installed on a Windows host; Dompdf falls back to DejaVu Sans.
        $pdf = self::newDompdf();
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper($paper, 'portrait');
        $pdf->render();
        // Lock the PDF: opens without a password, but only printing is permitted (no editing, copying or form changes).
        // The owner password is private to the server; set PDF_OWNER_PASSWORD in the environment to change it.
        $owner = getenv('PDF_OWNER_PASSWORD') ?: hash('sha256', 'tsa-pdf-lock|' . ROOT_PATH);
        $pdf->getCanvas()->get_cpdf()->setEncryption('', $owner, ['print']);
        $pdf->stream($filename, ['Attachment' => $download]);
        exit;
    }

    /**
     * Blank numbered stickers, one 100 x 100 mm page each, as one PDF.
     * Laying every sticker out through Dompdf's HTML engine costs about 70 ms a page (500 pages: over 30 s, 500 MB), so
     * the fixed sticker layout is drawn straight onto the PDF canvas instead: 500 pages take about 4 seconds.
     * The geometry mirrors .stk in doc.css / documents/sticker_card (Dompdf treats every box as content-box).
     * @param array $rows stickers: sticker_no, color, verify_token
     */
    public static function stickerSheetPdf(array $rows, string $filename, bool $download = true): void
    {
        @set_time_limit(0);
        $m = 72 / 25.4;                                           // mm -> pt
        $opt = new Options();
        $opt->set('isRemoteEnabled', false);
        $opt->set('isFontSubsettingEnabled', true);
        $opt->set('chroot', [ROOT_PATH]);
        $opt->set('dpi', 96);
        $pdf = new Dompdf($opt);
        $pdf->setPaper([0, 0, 100 * $m, 100 * $m]);
        $c = $pdf->getCanvas();
        $fm = $pdf->getFontMetrics();
        $fb = $fm->getFont('helvetica', 'bold') ?: 'Helvetica-Bold';
        $fr = $fm->getFont('helvetica', 'normal') ?: 'Helvetica';
        $img = fn (string $f) => ROOT_PATH . '/assets/img/' . $f;
        $hex = fn (string $h) => [hexdec(substr($h, 1, 2)) / 255, hexdec(substr($h, 3, 2)) / 255, hexdec(substr($h, 5, 2)) / 255];
        $white = [1, 1, 1]; $black = [0, 0, 0]; $grey = $hex('#e6e7e8'); $txt = $hex('#221f1f'); $red = $hex('#ed2127');
        $blueInk = $hex('#2d3690'); $redInk = $hex('#d32027');
        $labels = ['Equipment Type', 'Identification No', 'Equip S/N', 'Capacity / SWL', 'Inspection Date', 'Next Inspection Date', 'Inspected By'];
        $addr = ['Kingdom of Saudi Arabia - Main Office - Eastern Province', 'Al Khobar, 3205 Prince Muteb St., Al sharq Tower, 5th Floor', 'Phone : 0138185501 - Mob.: +966566485177', 'E-mail : info@tuv-east.com - operations@tuv-east.com'];

        $box = fn (float $x, float $y, float $w, float $h, array $col) => $c->filled_rectangle($x * $m, $y * $m, $w * $m, $h * $m, $col);
        $frame = function (float $x, float $y, float $w, float $h, float $b, array $edge, array $fill) use ($box) { $box($x, $y, $w, $h, $edge); $box($x + $b, $y + $b, $w - 2 * $b, $h - 2 * $b, $fill); };
        $rounded = function (float $x, float $y, float $w, float $h, float $r, array $col) use ($c, $m, $box) {
            $c->clipping_roundrectangle($x * $m, $y * $m, $w * $m, $h * $m, $r * $m, $r * $m, $r * $m, $r * $m);
            $box($x, $y, $w, $h, $col);
            $c->clipping_end();
        };
        // text centred in a line box, as the HTML renderer does ($x, $top, $lineH in pt)
        $text = function (float $x, float $top, float $lineH, string $s, string $font, float $size, array $col, float $cs = 0.0, float $adj = 0.0) use ($c) {
            $c->text($x, $top + ($lineH - $c->get_font_height($font, $size)) / 2 + $adj, $s, $font, $size, $col, 0.0, $cs);
        };

        $base = rtrim((string) config('public_url', ''), '/');
        $qo = new QROptions(['eccLevel' => QRCode::ECC_M, 'addQuietzone' => true, 'quietzoneSize' => 1]);
        $first = true;
        foreach ($rows as $s) {
            if (!$first) $c->new_page();
            $first = false;
            $blue = ($s['color'] ?? '') !== 'red';
            $ink = $blue ? $blueInk : $redInk;
            $edge = $blue ? $ink : $white;                        // frames / borders
            $fg = $blue ? $txt : $white;                          // labels
            if (!$blue) $box(0, 0, 100, 100, $ink);              // rejected stickers are red all over

            // logo (on a white rounded panel when the sticker is red)
            if ($blue) $c->image($img('sticker-tuv.png'), 3.8 * $m, 4 * $m, 31.4 * $m, 16.2 * $m);
            else { $rounded(3.8, 4, 33, 17.8, 1.2, $white); $c->image($img('sticker-tuv.png'), 4.6 * $m, 4.8 * $m, 31.4 * $m, 16.2 * $m); }

            // QR: framed, modules drawn as runs
            $frame(46.2, 3.6, 15, 15, .3, $edge, $white);
            $qr = new QRCode($qo);
            $qr->addByteSegment(($base !== '' ? $base . '/s/' . $s['verify_token'] : abs_url('s/' . $s['verify_token'])));
            $g = $qr->getMatrix()->getMatrix(true);
            $n = count($g);
            $cell = 13.6 / $n;
            for ($r = 0; $r < $n; $r++) {
                $run = -1;
                for ($k = 0; $k <= $n; $k++) {
                    $on = $k < $n && $g[$r][$k];
                    if ($on && $run < 0) $run = $k;
                    if (!$on && $run >= 0) { $box(46.9 + $run * $cell, 4.3 + $r * $cell, ($k - $run) * $cell + .03, $cell + .03, $black); $run = -1; }
                }
            }

            // result box
            $word = $blue ? 'ACCEPTED' : 'REJECTED';
            $box(38.5, 18.2, 29.7, 5.9, $edge);
            $box(38.85, 18.55, 29, 5.2, $blue ? $white : $ink);
            $w = $c->get_text_width($word, $fb, 10, 0, .35 * $m);
            $text(38.85 * $m + (29 * $m - $w) / 2, 18.55 * $m, 4.5 * $m, $word, $fb, 10, $blue ? $ink : $white, .35 * $m);

            // accreditation mark
            $frame(69.93, 4.3, 25.8, 12.5, .1, $edge, $white);
            $c->image($img('sticker-uaf.png'), 70.23 * $m, 4.55 * $m, 24.6 * $m, 11.5 * $m);

            // No: box
            $box(69.95, 17.33, 25.35, 6.3, $edge);
            $box(70.05, 17.43, 25.15, 6.1, $blue ? $white : $ink);
            $box(70.05, 17.43, 7.9, 6, $blue ? $ink : $white);
            $text(70.05 * $m + (7.9 * $m - $c->get_text_width('No:', $fb, 12)) / 2, 17.43 * $m, 6 * $m, 'No:', $fb, 12, $blue ? $white : $ink);
            $no = (string) $s['sticker_no'];
            $size = min(11.3, round(16.5 / (max(1, strlen($no)) * 0.215), 1));
            $text(77.95 * $m + (17.1 * $m - $c->get_text_width($no, $fb, $size)) / 2, 17.43 * $m, 6 * $m, $no, $fb, $size, $blue ? $red : $white);

            // rows to fill in by hand (rejected stickers drop Capacity / SWL and Next Inspection Date)
            $y = 27.38;
            foreach ($labels as $l) {
                if (!$blue && in_array($l, ['Capacity / SWL', 'Next Inspection Date'], true)) continue;
                $lab = $l . ' :';
                $text(3.6 * $m, $y * $m, 5.1 * $m, $lab, $fb, 9.45, $fg, 0.0, 0.75);
                $x = 3.6 + max(29.75, $c->get_text_width($lab, $fb, 9.45) / $m) + .8;
                $rounded($x, $y, 3.6 + 92.7 - $x, 6.65, 1.5, $grey);
                $y += $blue ? 7.7 : 9.6;
            }

            // office address and LEEA mark
            foreach ($addr as $i => $ln) $text(3.7 * $m, (82.6 + $i * 3.22) * $m, 3.22 * $m, $ln, $fr, 7.45, $blue ? $ink : $white);
            if ($blue) $c->image($img('sticker-leea.png'), 84.2 * $m, 81.8 * $m, 12.2 * $m, 14.4 * $m);
            else { $rounded(84.2, 81.8, 13.8, 16, 1.2, $white); $c->image($img('sticker-leea.png'), 85 * $m, 82.6 * $m, 12.2 * $m, 14.4 * $m); }
        }

        $owner = getenv('PDF_OWNER_PASSWORD') ?: hash('sha256', 'tsa-pdf-lock|' . ROOT_PATH);
        $c->get_cpdf()->setEncryption('', $owner, ['print']);
        $pdf->stream($filename, ['Attachment' => $download]);
        exit;
    }
}
