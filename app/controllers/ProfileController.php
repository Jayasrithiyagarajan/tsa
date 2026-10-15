<?php

final class ProfileController extends Controller
{
    public function show(): void
    {
        $u = Auth::user();
        $profile = DB::one('SELECT * FROM inspector_profiles WHERE user_id = ?', [$u['id']]);
        $skills = DB::all('SELECT t.name, t.code FROM inspector_skills k JOIN inspection_types t ON t.id = k.inspection_type_id WHERE k.user_id = ?', [$u['id']]);
        $this->view('shared/profile', compact('u', 'profile', 'skills') + ['title' => 'My Profile', 'crumbs' => ['My Profile' => null]]);
    }

    public function update(): void
    {
        $errors = validate($_POST, ['name' => 'required|max:120', 'phone' => 'phone']);
        if ($errors) $this->failValidation($errors);
        DB::update('users', ['name' => input('name'), 'phone' => nullable(input('phone'))], 'id = ?', [Auth::id()]);
        Audit::log('profile.updated', 'user', Auth::id());
        flash('success', 'Profile updated.');
        redirect('profile');
    }

    public function password(): void
    {
        $u = DB::one('SELECT password_hash FROM users WHERE id = ?', [Auth::id()]);
        if (!password_verify((string) ($_POST['current'] ?? ''), $u['password_hash'])) {
            $this->failValidation(['current' => 'Current password is incorrect.'], 'Password not changed.');
        }
        $new = (string) ($_POST['new'] ?? '');
        if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
            $this->failValidation(['new' => 'Use at least 8 characters with letters and numbers.'], 'Password not changed.');
        }
        if ($new !== ($_POST['confirm'] ?? '')) $this->failValidation(['confirm' => 'Passwords do not match.'], 'Password not changed.');
        DB::update('users', ['password_hash' => password_hash($new, PASSWORD_DEFAULT)], 'id = ?', [Auth::id()]);
        Audit::log('auth.password_changed', 'user', Auth::id());
        flash('success', 'Password changed.');
        redirect('profile');
    }

    public function signature(): void
    {
        if (!Auth::is('inspector', 'qc', 'admin')) $this->forbid();
        $this->attempt(function () {
            $f = store_upload($_FILES['signature'] ?? [], 'signatures', ['image/png', 'image/jpeg']);
            DB::update('users', ['signature_path' => $f['path']], 'id = ?', [Auth::id()]);
            Audit::log('profile.signature', 'user', Auth::id());
        }, 'Signature uploaded. It will be printed on documents you sign from now on.', 'profile');
    }

    public function notifications(): void
    {
        [$rows, $meta] = paginate('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC', [Auth::id()], 30);
        $this->view('shared/notifications', compact('rows', 'meta') + ['title' => 'Notifications', 'crumbs' => ['Notifications' => null]]);
    }

    public function openNotification(string $id): void
    {
        $n = DB::one('SELECT * FROM notifications WHERE id = ? AND user_id = ?', [(int) $id, Auth::id()]);
        if (!$n) $this->notFound();
        DB::update('notifications', ['read_at' => now()], 'id = ? AND read_at IS NULL', [$n['id']]);
        redirect($n['link'] && !preg_match('#^(https?:)?//#', $n['link']) ? $n['link'] : 'notifications');
    }

    public function readAll(): void
    {
        DB::update('notifications', ['read_at' => now()], 'user_id = ? AND read_at IS NULL', [Auth::id()]);
        redirect_back('notifications');
    }
}
