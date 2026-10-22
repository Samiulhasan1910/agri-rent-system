<?php
declare(strict_types=1);

/* ------------------------------------------------------------------
 * Configuration & environment
 * ------------------------------------------------------------------ */
function config(string $key, $default = null)
{
    $value = $GLOBALS['__config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function setting(string $key, $default = '')
{
    return Settings::get($key, $default);
}

function base_url(): string
{
    $configured = rtrim((string)config('app.url', ''), '/');
    if ($configured !== '') {
        return $configured;
    }
    if (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])) {
        return 'http://localhost/agri_rent_system';
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = rtrim($dir, '/');
    return ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $dir;
}

/** Relative application URL (all pages go through index.php). */
function url(string $route = '', array $params = []): string
{
    // routes only contain [a-z0-9/-], so they stay readable (index.php?r=booking/show&id=5)
    $query = $route !== '' ? 'r=' . $route : '';
    if ($params) {
        $query .= ($query !== '' ? '&' : '') . http_build_query($params);
    }
    return 'index.php' . ($query !== '' ? '?' . $query : '');
}

/** Absolute URL, used in emails and OAuth redirects. */
function abs_url(string $route = '', array $params = []): string
{
    return base_url() . '/' . url($route, $params);
}

function asset(string $path): string
{
    $file = BASE_PATH . '/' . ltrim($path, '/');
    $version = is_file($file) ? (string)filemtime($file) : '1';
    return ltrim($path, '/') . '?v=' . $version;
}

/* ------------------------------------------------------------------
 * Output helpers
 * ------------------------------------------------------------------ */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function t(string $key, array $params = []): string
{
    return Lang::translate($key, $params);
}

function money($amount, bool $decimals = false): string
{
    $amount = (float)$amount;
    $digits = ($decimals || floor($amount) != $amount) ? 2 : 0;
    return '৳' . number_format($amount, $digits);
}

function fmt_date(?string $date, string $format = 'd M Y'): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '—';
}

function fmt_datetime(?string $date): string
{
    return fmt_date($date, 'd M Y, h:i A');
}

function time_ago(?string $date): string
{
    if (!$date) {
        return '';
    }
    $diff = time() - strtotime($date);
    if ($diff < 60) {
        return t('just now');
    }
    if ($diff < 3600) {
        return t(':n min ago', ['n' => (int)floor($diff / 60)]);
    }
    if ($diff < 86400) {
        return t(':n hours ago', ['n' => (int)floor($diff / 3600)]);
    }
    if ($diff < 86400 * 7) {
        return t(':n days ago', ['n' => (int)floor($diff / 86400)]);
    }
    return fmt_date($date);
}

function str_limit(?string $text, int $limit = 80): string
{
    $text = (string)$text;
    return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit - 1)) . '…' : $text;
}

function icon(string $name, string $class = ''): string
{
    return Icon::svg($name, $class);
}

/** Status label + colour used by badges across the app. */
function status_meta(string $status): array
{
    static $map = [
        // bookings
        'pending' => ['Pending', 'amber'], 'confirmed' => ['Confirmed', 'blue'], 'delivered' => ['Delivered', 'green'],
        'returned' => ['Returned', 'purple'], 'completed' => ['Completed', 'green'], 'cancelled' => ['Cancelled', 'red'],
        'rejected' => ['Rejected', 'red'], 'active_rental' => ['Active', 'green'],
        // payments
        'paid' => ['Paid', 'green'], 'failed' => ['Failed', 'red'], 'refunded' => ['Refunded', 'gray'],
        'unpaid' => ['Unpaid', 'amber'], 'partial' => ['Partially Paid', 'amber'],
        // deliveries
        'assigned' => ['Assigned', 'blue'], 'in_progress' => ['In Progress', 'blue'],
        // equipment & health
        'available' => ['Available', 'green'], 'unavailable' => ['Not Available', 'red'], 'maintenance' => ['Maintenance', 'amber'],
        'inactive' => ['Inactive', 'gray'], 'good' => ['Good', 'green'], 'needs_maintenance' => ['Needs Maintenance', 'red'],
        'under_maintenance' => ['Under Maintenance', 'amber'], 'unknown' => ['Data Missing', 'gray'],
        // maintenance
        'scheduled' => ['Scheduled', 'blue'], 'open' => ['Open', 'red'], 'resolved' => ['Resolved', 'green'],
        // complaints
        'in_review' => ['In Review', 'blue'], 'closed' => ['Closed', 'gray'],
        'low' => ['Low', 'green'], 'medium' => ['Medium', 'amber'], 'high' => ['High', 'red'],
        // users
        'active' => ['Active', 'green'], 'suspended' => ['Suspended', 'red'], 'deleted' => ['Deleted', 'gray'],
        'approved' => ['Approved', 'green'], 'verified' => ['Verified', 'green'], 'unverified' => ['Unverified', 'amber'],
        // inspection
        'damaged' => ['Damaged', 'red'], 'needs_service' => ['Needs Service', 'amber'],
        // seasons
        'normal' => ['Normal Season', 'gray'], 'harvest' => ['Harvest Season', 'amber'], 'off_season' => ['Off-season', 'blue'],
    ];
    $meta = $map[$status] ?? [ucwords(str_replace('_', ' ', $status)), 'gray'];
    return [t($meta[0]), $meta[1]];
}

function badge(?string $status, ?string $label = null): string
{
    $status = (string)$status;
    [$text, $color] = status_meta($status);
    return '<span class="badge badge-' . $color . '" data-status="' . e($status) . '">' . e($label ?? $text) . '</span>';
}

/* ------------------------------------------------------------------
 * Forms, session & auth helpers
 * ------------------------------------------------------------------ */
function csrf_token(): string
{
    return Session::csrf();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Session::csrf()) . '">';
}

function old(string $key, $default = '')
{
    return Session::old($key, $default);
}

function field_error(string $key): string
{
    $message = Session::error($key);
    return '<small class="field-error" data-error-for="' . e($key) . '">' . e($message ?? '') . '</small>';
}

function auth(): ?array
{
    return Auth::user();
}

function is_role(string ...$roles): bool
{
    return Auth::is(...$roles);
}

function selected($a, $b): string
{
    return (string)$a === (string)$b ? ' selected' : '';
}

function checked($value): string
{
    return $value ? ' checked' : '';
}

/* ------------------------------------------------------------------
 * Domain presentation helpers
 * ------------------------------------------------------------------ */
function equipment_image(?array $row): string
{
    $image = (string)($row['image'] ?? '');
    if ($image !== '' && (preg_match('#^https?://#', $image) || is_file(BASE_PATH . '/' . $image))) {
        return $image;
    }
    $icon = $row['category_icon'] ?? $row['icon'] ?? 'tractor';
    return 'assets/img/equipment/' . (preg_match('/^[a-z]+$/', (string)$icon) ? $icon : 'tractor') . '.svg';
}

function initials(?string $name): string
{
    $parts = preg_split('/\s+/', trim((string)$name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $letters ?: 'U';
}

function avatar(?array $user, string $size = ''): string
{
    $class = 'avatar' . ($size ? ' avatar-' . $size : '');
    if (!empty($user['profile_photo']) && is_file(BASE_PATH . '/' . $user['profile_photo'])) {
        return '<img class="' . $class . '" src="' . e($user['profile_photo']) . '?v=' . filemtime(BASE_PATH . '/' . $user['profile_photo']) . '" alt="' . e($user['name'] ?? '') . '">';
    }
    $hue = crc32((string)($user['name'] ?? 'user')) % 360;
    return '<span class="' . $class . ' avatar-initials" style="--hue:' . $hue . '">' . e(initials($user['name'] ?? '')) . '</span>';
}

function stars(float $rating, bool $showNumber = true): string
{
    $html = '<span class="stars" title="' . e(number_format($rating, 1)) . '">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<span class="' . ($rating >= $i - 0.25 ? 'on' : ($rating >= $i - 0.75 ? 'half' : '')) . '">★</span>';
    }
    return $html . '</span>' . ($showNumber ? ' <b class="rating-number">' . number_format($rating, 1) . '</b>' : '');
}

function role_label(string $role): string
{
    return t(ucfirst($role));
}

/** Pagination meta: ['page','pages','offset','per','total']. */
function paginate(int $total, int $page, int $perPage = 10): array
{
    $pages = max(1, (int)ceil($total / max(1, $perPage)));
    $page = min(max(1, $page), $pages);
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage, 'per' => $perPage, 'total' => $total];
}

function page_url(int $page): string
{
    $query = $_GET;
    $query['page'] = $page;
    unset($query['partial']);
    return 'index.php?' . http_build_query($query);
}

/* ------------------------------------------------------------------
 * Request utilities
 * ------------------------------------------------------------------ */
function request_is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
}

function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function user_agent(): string
{
    return substr((string)($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 255);
}

function random_digits(int $length = 6): string
{
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= (string)random_int(0, 9);
    }
    return $code;
}

function is_uuid(?string $value): bool
{
    return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string)$value);
}

function valid_date(?string $value): bool
{
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return false;
    }
    $d = DateTime::createFromFormat('!Y-m-d', $value);
    return $d && $d->format('Y-m-d') === $value;
}

function today(): string
{
    return date('Y-m-d');
}

function days_between(string $start, string $end): int
{
    return (int)(new DateTime($start))->diff(new DateTime($end))->format('%r%a') + 1;
}

/** Streams a UTF-8 CSV download (formula-like cells are neutralised for spreadsheet safety). */
function send_csv(string $filename, array $header, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header);
    foreach ($rows as $row) {
        fputcsv($out, array_map(static fn($v) => preg_match('/^[=+\-@\t\r]/', (string)$v) ? "'" . $v : $v, array_values($row)));
    }
    fclose($out);
    exit;
}

/** Sidebar navigation for each role: [route, label, icon]. */
function nav_items(string $role): array
{
    $items = [
        'farmer' => [
            ['dashboard/index', 'Dashboard', 'home'],
            ['equipment/index', 'Browse Equipment', 'tractor'],
            ['smart/index', 'Smart Match', 'sparkles'],
            ['booking/index', 'My Bookings', 'calendar-check'],
            ['payment/history', 'Payments', 'credit-card'],
            ['wishlist/index', 'Wishlist', 'heart'],
            ['booking/history', 'History', 'history'],
            ['emergency/index', 'Emergency', 'siren'],
            ['complaint/index', 'Complaints', 'alert'],
            ['chat/index', 'Chat', 'message'],
            ['profile/index', 'Profile', 'user'],
        ],
        'employee' => [
            ['dashboard/index', 'Dashboard', 'home'],
            ['maintenance/index', 'Schedule Maintenance', 'calendar'],
            ['equipment/create', 'Add Equipment', 'plus'],
            ['equipment/manage', 'Equipment List', 'tractor'],
            ['rental/index', 'Rental Requests', 'clipboard-check'],
            ['inspection/index', 'Return Inspection', 'history'],
            ['maintenance/health', 'Equipment Health', 'activity'],
            ['verification/index', 'Farmer Verification', 'user-check'],
            ['chat/index', 'Chat With Farmers', 'message'],
            ['complaint/index', 'Complaints', 'alert'],
            ['emergency/index', 'Emergency Requests', 'siren'],
            ['profile/index', 'Profile', 'user'],
        ],
        'admin' => [
            ['dashboard/index', 'Dashboard', 'home'],
            ['equipment/manage', 'Manage Equipment', 'tractor'],
            ['category/index', 'Categories', 'grid'],
            ['rental/index', 'Rentals', 'calendar-check'],
            ['payment/admin', 'Payment & Invoices', 'receipt'],
            ['delivery/index', 'Delivery', 'truck'],
            ['maintenance/health', 'Maintenance & Health', 'wrench'],
            ['inspection/index', 'Return Inspection', 'clipboard-check'],
            ['analytics/index', 'Reports / Analytics', 'chart'],
            ['user/index', 'Users', 'users'],
            ['verification/index', 'Verification', 'user-check'],
            ['complaint/index', 'Complaints', 'alert'],
            ['emergency/index', 'Emergency', 'siren'],
            ['chat/index', 'Messages', 'message'],
            ['settings/index', 'Settings', 'settings'],
        ],
    ];
    return $items[$role] ?? [];
}
