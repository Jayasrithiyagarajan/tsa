<?php

final class AuthController extends Controller
{
    public function form(): void
    {
        if (Auth::check()) redirect(Auth::home());
        $this->view('auth/login', ['title' => 'Sign in'], 'auth');
    }

    public function login(): void
    {
        // basic brute-force throttle: 5 failures → 60s lock per session
        $lock = $_SESSION['login_lock'] ?? 0;
        if ($lock > time()) {
            flash('error', 'Too many attempts. Try again in ' . ($lock - time()) . ' seconds.');
            redirect('login');
        }
        $email = (string) input('email', '');
        // Server-side throttle that a fresh session / cookie cannot reset: recent failed log-ins (audit log)
        // per account (5 in 15 min) and per IP address (20 in 15 min).
        $norm = mb_substr(strtolower(trim($email)), 0, 190);
        $since = date('Y-m-d H:i:s', time() - 15 * 60);
        $byAccount = (int) DB::value("SELECT COUNT(*) FROM audit_logs WHERE action = 'auth.failed' AND created_at >= ?
                                      AND LOWER(JSON_UNQUOTE(JSON_EXTRACT(details, '$.email'))) = ?", [$since, $norm]);
        $byIp = (int) DB::value("SELECT COUNT(*) FROM audit_logs WHERE action = 'auth.failed' AND created_at >= ? AND ip_address = ?",
                                [$since, $_SERVER['REMOTE_ADDR'] ?? '']);
        if ($byAccount >= 5 || $byIp >= 20) {
            Audit::log('auth.locked', 'user', null, ['email' => $norm, 'by' => $byAccount >= 5 ? 'account' : 'ip']);
            with_old(['email' => $email]);
            flash('error', 'Too many failed sign-in attempts. Please wait 15 minutes and try again.');
            redirect('login');
        }
        if (Auth::attempt($email, (string) ($_POST['password'] ?? ''))) {
            unset($_SESSION['login_fail'], $_SESSION['login_lock']);
            $to = $_SESSION['intended'] ?? null;
            unset($_SESSION['intended']);
            $home = Auth::home();
            if ($to && str_starts_with(parse_url($to, PHP_URL_PATH) ?: '', url($home))) {
                header('Location: ' . $to);
                exit;
            }
            redirect($home);
        }
        $_SESSION['login_fail'] = ($_SESSION['login_fail'] ?? 0) + 1;
        if ($_SESSION['login_fail'] >= 5) {
            $_SESSION['login_lock'] = time() + 60;
            $_SESSION['login_fail'] = 0;
        }
        Audit::log('auth.failed', 'user', null, ['email' => $norm]);
        with_old(['email' => $email]);
        flash('error', 'Invalid email or password.');
        redirect('login');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('login');
    }
}
