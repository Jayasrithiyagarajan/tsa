<?php

final class Auth
{
    private static ?array $user = null;

    public const HOME = [
        'superadmin' => 'admin',
        'admin'     => 'admin',
        'inspector' => 'inspector',
        'qc'        => 'qc',
        'customer'  => 'portal',
    ];

    public static function attempt(string $email, string $password): bool
    {
        $u = DB::one("SELECT * FROM users WHERE email = ? AND status = 'active'", [strtolower(trim($email))]);
        if (!$u || !password_verify($password, $u['password_hash'])) {
            return false;
        }
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            DB::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$u['id']]);
        }
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $u['id'];
        $_SESSION['last_seen'] = time();
        DB::update('users', ['last_login_at' => now()], 'id = ?', [$u['id']]);
        self::$user = null;
        Audit::log('auth.login', 'user', (int) $u['id']);
        return true;
    }

    public static function logout(): void
    {
        if (self::check()) {
            Audit::log('auth.logout', 'user', self::id());
        }
        $_SESSION = [];
        session_regenerate_id(true);
        self::$user = null;
    }

    public static function user(): ?array
    {
        if (self::$user === null && !empty($_SESSION['uid'])) {
            $timeout = config('session_minutes') * 60;
            if (time() - ($_SESSION['last_seen'] ?? 0) > $timeout) {
                $_SESSION = [];
                return null;
            }
            $_SESSION['last_seen'] = time();
            self::$user = DB::one(
                "SELECT u.*, c.name AS client_name FROM users u LEFT JOIN clients c ON c.id = u.client_id
                 WHERE u.id = ? AND u.status = 'active'",
                [$_SESSION['uid']]
            );
        }
        return self::$user;
    }

    public static function check(): bool { return self::user() !== null; }
    public static function id(): ?int    { return self::user()['id'] ?? null; }
    public static function role(): ?string { return self::user()['role'] ?? null; }
    /** A Super Admin also passes every 'admin' check — admin routes and screens are shared, Super Admin has extra rights. */
    public static function is(string ...$roles): bool
    {
        $r = self::role();
        return in_array($r, $roles, true) || ($r === 'superadmin' && in_array('admin', $roles, true));
    }
    /** Full control: user accounts, sticker stock, ID cards, templates, settings. */
    public static function isSuper(): bool { return self::role() === 'superadmin'; }
    public static function clientId(): ?int { $c = self::user()['client_id'] ?? null; return $c ? (int) $c : null; }

    public static function home(): string
    {
        return self::HOME[self::role()] ?? '';
    }
}
