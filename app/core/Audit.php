<?php

final class Audit
{
    public static function log(string $action, string $entityType, ?int $entityId = null, array $details = []): void
    {
        DB::insert('audit_logs', [
            'user_id'     => $_SESSION['uid'] ?? null,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'details'     => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'cli',
        ]);
    }
}

final class Notify
{
    public static function user(int $userId, string $title, ?string $body = null, ?string $link = null): void
    {
        DB::insert('notifications', ['user_id' => $userId, 'title' => $title, 'body' => $body, 'link' => $link]);
    }

    public static function role(string $role, string $title, ?string $body = null, ?string $link = null): void
    {
        $roles = $role === 'admin' ? ['admin', 'superadmin'] : [$role];      // admin notifications reach Super Admins too
        foreach (DB::all("SELECT id FROM users WHERE role IN (" . implode(',', array_fill(0, count($roles), '?')) . ") AND status = 'active'", $roles) as $u) {
            self::user((int) $u['id'], $title, $body, $link);
        }
    }

    public static function client(int $clientId, string $title, ?string $body = null, ?string $link = null): void
    {
        foreach (DB::all("SELECT id FROM users WHERE role = 'customer' AND client_id = ? AND status = 'active'", [$clientId]) as $u) {
            self::user((int) $u['id'], $title, $body, $link);
        }
    }
}
