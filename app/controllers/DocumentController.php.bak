<?php

/** Controlled documents: on-screen preview + PDF (?pdf=1 inline, ?download=1 attachment). */
final class DocumentController extends Controller
{
    private function backUrl(array $sj): string
    {
        switch (Auth::role()) {
            case 'superadmin':
            case 'admin':     return url('admin/jobs/' . $sj['job_id']);
            case 'inspector': return url('inspector/subjobs/' . $sj['id']);
            case 'qc':        return url('qc/review/' . $sj['id']);
            default:          return url('portal/jobs/' . $sj['job_id']);
        }
    }

    private function load(string $id): array
    {
        $sj = JobService::subJob((int) $id);
        if (!$sj) $this->notFound();
        if (!DocumentService::canView($sj)) $this->forbid();
        return $sj;
    }

    private function output(string $view, array $vars, string $file, array $sj, string $status): void
    {
        $pdf = !empty($_GET['pdf']) || !empty($_GET['download']);
        $html = View::partial($view, $vars + ['toolbar' => $pdf ? null : [
            'back' => $this->backUrl($sj),
            'pdf' => '?download=1',
            'status' => $status,
        ]]);
        if ($pdf) {
            Audit::log('document.downloaded', 'sub_job', (int) $sj['id'], ['file' => $file]);
            DocumentService::pdf($html, $file, !empty($_GET['download']));
        }
        echo $html;
    }

    public function checklist(string $id): void
    {
        $sj = $this->load($id);
        $m = DocumentService::checklistModel((int) $sj['id']);
        if (!$m) $this->notFound();
        $this->output('documents/checklist', ['m' => $m], 'Checklist-' . $sj['sub_job_no'] . '.pdf', $sj, Workflow::label($m['checklist']['status']));
    }

    public function certificate(string $id): void
    {
        $sj = $this->load($id);
        $cert = DB::one('SELECT * FROM certificates WHERE sub_job_id = ?', [$sj['id']]);
        if ($cert && !$cert['template_id']) {          // uploaded certificate → stream the file
            $att = DB::one("SELECT id FROM attachments WHERE sub_job_id = ? AND kind = 'certificate_upload' ORDER BY id DESC LIMIT 1", [$sj['id']]);
            $att ? $this->attachment((string) $att['id']) : $this->notFound();
            return;
        }
        $m = DocumentService::certificateModel((int) $sj['id']);
        if (!$m) $this->notFound();
        $this->output('documents/certificate', ['m' => $m], 'Certificate-' . $m['cert']['certificate_no'] . '.pdf', $sj, Workflow::label($m['cert']['status']));
    }

    /** Digital sticker of a job: everyone who may see the job's documents (customers once QC has approved it). */
    public function sticker(string $id): void
    {
        $st = DocumentService::stickerModel((int) $id);
        if (!$st) $this->notFound();
        if ($st['sub_job_id'] && $st['status'] === 'closed') {
            $sj = $this->load((string) $st['sub_job_id']);
            $back = $_SERVER['HTTP_REFERER'] ?? $this->backUrl($sj);
        } elseif (Auth::is('admin')) {
            $back = $_SERVER['HTTP_REFERER'] ?? url('admin/stickers');
        } else {
            $this->notFound();
        }
        echo View::partial('documents/sticker', ['st' => $st, 'toolbar' => ['back' => $back]]);
    }

    /** Blank pre-printed stickers (number, colour and result only), 6 per A4 sheet, for the print shop. */
    public function stickerSheet(): void
    {
        if (!empty($_POST['ids'])) {
            $ids = array_slice(array_map('intval', (array) $_POST['ids']), 0, 2000);
        } elseif (trim((string) input('from', '')) !== '') {
            try { $ids = StickerService::range((string) input('from'), (string) input('to', '')); }
            catch (DomainException $e) { flash('error', $e->getMessage()); redirect('admin/stickers'); }
        } elseif (($b = trim((string) input('batch', ''))) !== '') {
            $ids = array_map('intval', array_column(DB::all('SELECT id FROM stickers WHERE batch_no = ?', [$b]), 'id'));
        } else {
            $ids = [];
        }
        $rows = $ids ? DB::all('SELECT id, sticker_no, verify_token, color, status FROM stickers WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ") AND status <> 'void' ORDER BY sticker_no", $ids) : [];
        if (!$rows) { flash('error', 'No printable stickers selected.'); redirect('admin/stickers'); }
        set_time_limit(300);
        Audit::log('sticker.printed', 'sticker', null, ['count' => count($rows), 'from' => $rows[0]['sticker_no'], 'to' => end($rows)['sticker_no']]);
        echo View::partial('documents/sticker_sheet', ['rows' => $rows, 'back' => url('admin/stickers')]);
    }

    public function attachment(string $id): void
    {
        $a = DB::one('SELECT * FROM attachments WHERE id = ?', [(int) $id]);
        if (!$a) $this->notFound();
        $sj = $this->load((string) $a['sub_job_id']);
        if (Auth::is('customer') && $a['kind'] === 'document') $this->forbid();
        $f = upload_abs($a['file_path']);
        if (!is_file($f)) $this->notFound();
        header('Content-Type: ' . $a['mime']);
        header('Content-Length: ' . filesize($f));
        header('Content-Disposition: ' . (!empty($_GET['download']) ? 'attachment' : 'inline') . '; filename="' . str_replace('"', '', $a['original_name']) . '"');
        header('Cache-Control: private, max-age=3600');
        readfile($f);
        exit;
    }

    /** Signature images are only served to staff (customers see them embedded in issued documents). */
    public function signature(string $id): void
    {
        if (!Auth::is('admin', 'qc') && Auth::id() !== (int) $id) $this->forbid();
        $p = DB::value('SELECT signature_path FROM users WHERE id = ?', [(int) $id]);
        if (!$p || !is_file(upload_abs($p))) $this->notFound();
        header('Content-Type: ' . mime_content_type(upload_abs($p)));
        header('Cache-Control: private, no-store');
        readfile(upload_abs($p));
        exit;
    }
}
