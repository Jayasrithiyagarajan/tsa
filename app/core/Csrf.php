<?php

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . self::token() . '">';
    }

    public static function verify(): void
    {
        $sent = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($sent) || !hash_equals(self::token(), $sent)) {
            if (is_ajax()) {
                json_out(['error' => 'Session expired, reload the page.'], 419);
            }
            http_response_code(419);
            flash('error', 'Your session expired. Please try again.');
            redirect_back();
        }
    }
}
