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
        Audit::log('auth.failed', 'user', null, ['email' => mb_substr($email, 0, 190)]);
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
