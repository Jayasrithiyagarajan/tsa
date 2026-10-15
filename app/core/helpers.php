<?php

function config(string $key, $default = null)
{
    static $cfg = null;
    $cfg ??= require ROOT_PATH . '/config/config.php';
    return $cfg[$key] ?? $default;
}

function setting(string $key, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (DB::all('SELECT `key`, `value` FROM settings') as $r) {
            $cache[$r['key']] = $r['value'];
        }
    }
    return $cache[$key] ?? $default;
}

function e($v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    }
    return $base;
}

function url(string $path = '', array $query = []): string
{
    $u = base_path() . '/' . ltrim($path, '/');
    return $query ? $u . '?' . http_build_query($query) : $u;
}

function abs_url(string $path = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . url($path);
}

function asset(string $path): string
{
    $file = ROOT_PATH . '/assets/' . $path;
    $v = is_file($file) ? filemtime($file) : 1;
    return url('assets/' . $path) . '?v=' . $v;
}

function redirect(string $path, array $query = []): void
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path, $query)));
    exit;
}

function redirect_back(string $fallback = ''): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref && parse_url($ref, PHP_URL_HOST) === $host) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function with_old(array $data, array $errors = []): void
{
    $_SESSION['old'] = $data;
    $_SESSION['errors'] = $errors;
}

function old(string $key, $default = '')
{
    return $_SESSION['old_view'][$key] ?? $default;
}

function errors(): array
{
    return $_SESSION['errors_view'] ?? [];
}

function field_error(string $key): string
{
    $err = errors()[$key] ?? null;
    return $err ? '<p class="field-error">' . e($err) . '</p>' : '';
}

function input(string $key, $default = null)
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || str_starts_with(request_path(), '/api/');
}

function request_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    return '/' . trim($uri, '/');
}

function json_out($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

/** 05-May-2025 — matches the date style printed on TUV EAST documents. */
function fmt_date($d, string $format = 'd-M-Y'): string
{
    if (!$d || $d === '0000-00-00') {
        return '';
    }
    $ts = strtotime((string) $d);
    return $ts ? date($format, $ts) : '';
}

function fmt_datetime($d): string
{
    return fmt_date($d, 'd-M-Y H:i');
}

function time_ago($d): string
{
    if (!$d) return '';
    $s = time() - strtotime($d);
    if ($s < 60) return 'just now';
    if ($s < 3600) return floor($s / 60) . 'm ago';
    if ($s < 86400) return floor($s / 3600) . 'h ago';
    if ($s < 604800) return floor($s / 86400) . 'd ago';
    return fmt_date($d);
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    return strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
}

function badge(string $status, ?string $label = null): string
{
    $tone = Workflow::tone($status);
    $label ??= Workflow::label($status);
    return '<span class="badge badge-' . $tone . '"><i></i>' . e($label) . '</span>';
}

function icon(string $name, string $class = ''): string
{
    return '<svg class="ic ' . e($class) . '" aria-hidden="true"><use href="' . url('assets/img/icons.svg') . '#' . e($name) . '"/></svg>';
}

function selected($a, $b): string { return (string) $a === (string) $b ? ' selected' : ''; }
function checked(bool $c): string  { return $c ? ' checked' : ''; }

function is_active_nav(string $prefix): bool
{
    $p = trim(request_path(), '/');
    $prefix = trim($prefix, '/');
    return $p === $prefix || str_starts_with($p, $prefix . '/');
}

/** Simple paginator over a SELECT without LIMIT. Returns [rows, meta]. */
function paginate(string $sql, array $params = [], int $perPage = 20): array
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $total = (int) DB::value("SELECT COUNT(*) FROM ($sql) t", $params);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $pages);
    $rows = DB::all($sql . ' LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage), $params);
    return [$rows, ['page' => $page, 'pages' => $pages, 'total' => $total, 'per' => $perPage]];
}

/**
 * Stream rows as CSV — or as an Excel workbook when ?export=xlsx — and exit. $columns = ['db_key' => 'Header'].
 * For Excel, column types are inferred from the data (numbers, dates and date-times stay typed in Excel).
 */
function csv_export(string $filename, array $rows, array $columns): void
{
    if (($_GET['export'] ?? '') === 'xlsx') {
        $types = [];
        foreach (array_keys($columns) as $k) {
            $vals = array_values(array_filter(array_column($rows, $k), fn ($v) => $v !== null && $v !== ''));
            $sample = array_slice($vals, 0, 50);
            $t = 'text';
            if ($sample && count(array_filter($sample, fn ($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v))) === count($sample)) $t = 'date';
            elseif ($sample && count(array_filter($sample, fn ($v) => preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', (string) $v))) === count($sample)) $t = 'datetime';
            elseif ($sample && count(array_filter($sample, fn ($v) => is_numeric($v) && !preg_match('/^0\d/', (string) $v))) === count($sample) && !preg_match('/(_no|code|phone|vat)$/i', $k)) $t = 'int';
            $types[$columns[$k]] = $t;
        }
        $data = array_map(fn ($r) => array_map(fn ($k) => $r[$k] ?? null, array_keys($columns)), $rows);
        $title = ucwords(str_replace('-', ' ', $filename));
        $x = new Xlsx();
        $x->sheet($title, $types, $data, ['title' => 'TUV EAST · ' . $title, 'subtitle' => 'Exported ' . date('d-M-Y H:i') . ' · ' . count($rows) . ' rows']);
        Audit::log('export.xlsx', 'report', null, ['file' => $filename, 'rows' => count($rows)]);
        $x->download($filename);
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array_values($columns));
    foreach ($rows as $r) {
        fputcsv($out, array_map(fn ($k) => $r[$k] ?? '', array_keys($columns)));
    }
    fclose($out);
    Audit::log('export.csv', 'report', null, ['file' => $filename, 'rows' => count($rows)]);
    exit;
}

/** Validate + store an uploaded file. Returns relative storage path or throws. */
function store_upload(array $file, string $folder, ?array $mimes = null): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Upload failed. Please choose a file.');
    }
    $max = config('upload_max_mb') * 1024 * 1024;
    if ($file['size'] > $max) {
        throw new InvalidArgumentException('File is larger than ' . config('upload_max_mb') . ' MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $mimes ??= config('upload_mimes');
    if (!in_array($mime, $mimes, true)) {
        throw new InvalidArgumentException('File type not allowed.');
    }
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'][$mime] ?? 'bin';
    $dir = rtrim(config('upload_dir'), '/') . '/' . $folder . '/' . date('Y/m');
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Could not save file.');
    }
    return [
        'path' => $folder . '/' . date('Y/m') . '/' . $name,
        'mime' => $mime,
        'size' => (int) $file['size'],
        'name' => mb_substr(basename($file['name']), 0, 190),
    ];
}

function upload_abs(string $rel): string
{
    return rtrim(config('upload_dir'), '/') . '/' . $rel;
}

/** Inline data URI for an uploaded image (used in documents so PDFs embed it). */
function upload_data_uri(?string $rel): ?string
{
    if (!$rel) return null;
    $f = upload_abs($rel);
    if (!is_file($f)) return null;
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f);
    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($f));
}

function validate(array $data, array $rules): array
{
    $errors = [];
    foreach ($rules as $field => $ruleStr) {
        $v = $data[$field] ?? null;
        $v = is_string($v) ? trim($v) : $v;
        foreach (explode('|', $ruleStr) as $rule) {
            [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
            $empty = $v === null || $v === '' || $v === [];
            if ($name === 'required' && $empty) { $errors[$field] = 'This field is required.'; break; }
            if ($empty) continue;
            if ($name === 'email' && !filter_var($v, FILTER_VALIDATE_EMAIL)) { $errors[$field] = 'Enter a valid email address.'; break; }
            if ($name === 'max' && mb_strlen((string) $v) > (int) $arg) { $errors[$field] = "Maximum $arg characters."; break; }
            if ($name === 'min' && mb_strlen((string) $v) < (int) $arg) { $errors[$field] = "Minimum $arg characters."; break; }
            if ($name === 'date' && !strtotime((string) $v)) { $errors[$field] = 'Enter a valid date.'; break; }
            if ($name === 'int' && !ctype_digit((string) $v)) { $errors[$field] = 'Invalid value.'; break; }
            if ($name === 'in' && !in_array($v, explode(',', $arg), true)) { $errors[$field] = 'Invalid option.'; break; }
            if ($name === 'phone' && !preg_match('/^[0-9+\-\s()]{6,20}$/', (string) $v)) { $errors[$field] = 'Enter a valid phone number.'; break; }
        }
    }
    return $errors;
}

function nullable($v)
{
    $v = is_string($v) ? trim($v) : $v;
    return ($v === '' || $v === null) ? null : $v;
}

/** Invisible first row that fixes column widths (mm) in documents — works in browsers and Dompdf. */
function doc_sizer(array $mm): string
{
    return '<tr class="sz">' . implode('', array_map(fn ($w) => '<td style="width:' . round($w, 2) . 'mm"></td>', $mm)) . '</tr>';
}
