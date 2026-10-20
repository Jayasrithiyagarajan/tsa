<?php

/**
 * Outgoing e-mail over plain SMTP (no extra library). Settings: config('mail') - see config/config.php.
 * When SMTP is not configured (or the server is unreachable) the message is saved as an .eml file in
 * storage/mail/ instead, so nothing is lost and the app never fails because of mail.
 */
final class Mailer
{
    /** @param string|array $to  one address or a list. Returns true when the SMTP server accepted the message. Never throws. */
    public static function send($to, string $subject, string $html): bool
    {
        $to = array_values(array_unique(array_filter(
            array_map(fn ($e) => strtolower(trim((string) $e)), (array) $to),
            fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
        )));
        if (!$to) return false;

        $cfg = (array) config('mail', []);
        $from = $cfg['from'] ?? 'no-reply@tuv-east.com';
        $fromName = $cfg['from_name'] ?? 'TUV EAST';
        $raw = self::build($from, $fromName, $to, $subject, $html);
        $err = 'SMTP is not configured (set mail.host in config/config.local.php)';
        if (!empty($cfg['host'])) {
            try {
                self::smtp($cfg, $from, $to, $raw);
                return true;
            } catch (Throwable $e) {
                $err = $e->getMessage();
            }
        }
        self::spool($to, $raw, $err);
        return false;
    }

    private static function build(string $from, string $fromName, array $to, string $subject, string $html): string
    {
        $h = [
            'Date: ' . date('r'),
            'From: ' . mb_encode_mimeheader($fromName, 'UTF-8', 'B') . " <$from>",
            'To: ' . implode(', ', $to),
            'Subject: ' . mb_encode_mimeheader($subject, 'UTF-8', 'B'),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . (explode('@', $from)[1] ?? 'localhost') . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        return implode("\r\n", $h) . "\r\n\r\n" . chunk_split(base64_encode($html), 76, "\r\n");
    }

    private static function smtp(array $cfg, string $from, array $to, string $raw): void
    {
        $secure = $cfg['secure'] ?? 'tls';                       // 'tls' (STARTTLS, port 587) | 'ssl' (port 465) | '' (none)
        $port = (int) ($cfg['port'] ?? ($secure === 'ssl' ? 465 : 587));
        $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : '') . $cfg['host'] . ':' . $port, $en, $es, 15);
        if (!$fp) throw new RuntimeException("Cannot connect to {$cfg['host']}:$port ($es)");
        stream_set_timeout($fp, 15);
        $read = function () use ($fp) {
            $r = '';
            while (($l = fgets($fp, 515)) !== false) {
                $r .= $l;
                if (strlen($l) < 4 || $l[3] === ' ') break;
            }
            return $r;
        };
        $cmd = function (string $c, array $ok) use ($fp, $read) {
            if ($c !== '') fwrite($fp, $c . "\r\n");
            $r = $read();
            if (!in_array((int) substr($r, 0, 3), $ok, true)) throw new RuntimeException('SMTP: ' . trim($r));
        };
        try {
            $cmd('', [220]);
            $cmd('EHLO localhost', [250]);
            if ($secure === 'tls') {
                $cmd('STARTTLS', [220]);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('SMTP: TLS handshake failed');
                $cmd('EHLO localhost', [250]);
            }
            if (!empty($cfg['user'])) {
                $cmd('AUTH LOGIN', [334]);
                $cmd(base64_encode($cfg['user']), [334]);
                $cmd(base64_encode((string) ($cfg['pass'] ?? '')), [235]);
            }
            $cmd("MAIL FROM:<$from>", [250]);
            foreach ($to as $t) $cmd("RCPT TO:<$t>", [250, 251]);
            $cmd('DATA', [354]);
            fwrite($fp, preg_replace('/^\./m', '..', $raw) . "\r\n.\r\n");
            $cmd('', [250]);
            @fwrite($fp, "QUIT\r\n");
        } finally {
            fclose($fp);
        }
    }

    private static function spool(array $to, string $raw, string $reason): void
    {
        $dir = ROOT_PATH . '/storage/mail';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        @file_put_contents($dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.eml', 'X-Mail-Status: NOT SENT - ' . $reason . "\r\n" . $raw);
    }
}

/** The three business e-mails. Each is best-effort: a mail problem must never break the action that triggered it. */
final class MailEvents
{
    public static function jobCreated(int $jobId): void
    {
        self::guard(function () use ($jobId) {
            $j = self::job($jobId);
            if (!$j) return;
            $n = (int) DB::value("SELECT COUNT(*) FROM sub_jobs WHERE job_id = ? AND status <> 'cancelled'", [$jobId]);
            $rows = [['Job No.', $j['job_no']], ['Title', $j['title']], ['Client', $j['client_name']], ['Location', $j['location']],
                ['PO No.', $j['po_no']], ['Scheduled', $j['scheduled_date'] ? fmt_date($j['scheduled_date']) : null], ['Inspections', (string) $n]];
            Mailer::send(self::clientRecipients((int) $j['client_id']), "Job {$j['job_no']} created - {$j['title']}",
                self::wrap('A new job has been created', 'Your inspection job has been registered with TUV EAST. You can follow its progress in the customer portal.', $rows, abs_url('portal/jobs/' . $jobId), 'Open job'));
        });
    }

    public static function jobClosed(int $jobId): void
    {
        self::guard(function () use ($jobId) {
            $j = self::job($jobId);
            if (!$j) return;
            $certs = (int) DB::value("SELECT COUNT(*) FROM certificates c JOIN sub_jobs s ON s.id = c.sub_job_id WHERE s.job_id = ? AND c.status = 'issued'", [$jobId]);
            $rows = [['Job No.', $j['job_no']], ['Title', $j['title']], ['Client', $j['client_name']], ['Closed on', fmt_date(date('Y-m-d'))],
                ['Certificates issued', (string) $certs]];
            Mailer::send(self::clientRecipients((int) $j['client_id']), "Job {$j['job_no']} completed - certificates ready",
                self::wrap('Your job is completed', 'Final approval has been given and the job is closed. ' . ($certs ? 'Your certificates and reports are ready to download from the customer portal.' : 'Your reports are ready to download from the customer portal.'), $rows, abs_url('portal/jobs/' . $jobId), 'Download documents'));
        });
    }

    public static function customerAccount(int $userId, string $password): void
    {
        self::guard(function () use ($userId, $password) {
            $u = DB::one('SELECT u.name, u.email, c.name client_name FROM users u LEFT JOIN clients c ON c.id = u.client_id WHERE u.id = ?', [$userId]);
            if (!$u) return;
            $rows = [['Name', $u['name']], ['Company', $u['client_name']], ['Login email', $u['email']], ['Temporary password', $password]];
            Mailer::send($u['email'], 'Your TUV EAST customer account',
                self::wrap('Your account is ready', 'A TUV EAST customer account has been created for you. Sign in with the details below and change your password after your first login.', $rows, abs_url('login'), 'Sign in'));
        });
    }

    private static function job(int $id): ?array
    {
        return DB::one('SELECT j.*, c.name client_name FROM jobs j JOIN clients c ON c.id = j.client_id WHERE j.id = ?', [$id]);
    }

    /** The client's contact address plus every active customer login of that client. */
    private static function clientRecipients(int $clientId): array
    {
        $to = array_column(DB::all("SELECT email FROM users WHERE role = 'customer' AND client_id = ? AND status = 'active'", [$clientId]), 'email');
        $to[] = DB::value('SELECT email FROM clients WHERE id = ?', [$clientId]);
        return array_filter($to);
    }

    private static function guard(callable $fn): void
    {
        try { $fn(); } catch (Throwable $e) { error_log('[mail] ' . $e->getMessage()); }
    }

    private static function wrap(string $heading, string $intro, array $rows, string $link, string $button): string
    {
        $tr = '';
        foreach ($rows as [$k, $v]) {
            if ($v === null || $v === '') continue;
            $tr .= '<tr><td style="padding:6px 12px 6px 0;color:#6b7280;white-space:nowrap">' . e($k) . '</td><td style="padding:6px 0;font-weight:600">' . e($v) . '</td></tr>';
        }
        return '<div style="font-family:Segoe UI,Arial,sans-serif;font-size:14px;color:#111827;max-width:560px;margin:auto;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden">'
            . '<div style="background:#1e3a8a;color:#fff;padding:16px 22px;font-size:16px;font-weight:700">TUV EAST - Inspection Management</div>'
            . '<div style="padding:22px"><h2 style="margin:0 0 10px;font-size:18px">' . e($heading) . '</h2><p style="margin:0 0 14px;line-height:1.5">' . e($intro) . '</p>'
            . '<table style="border-collapse:collapse;margin-bottom:18px">' . $tr . '</table>'
            . '<a href="' . e($link) . '" style="display:inline-block;background:#1e3a8a;color:#fff;text-decoration:none;padding:10px 18px;border-radius:8px;font-weight:600">' . e($button) . '</a>'
            . '<p style="margin:18px 0 0;color:#6b7280;font-size:12px">This is an automated message from TUV EAST. Please do not reply.</p></div></div>';
    }
}
