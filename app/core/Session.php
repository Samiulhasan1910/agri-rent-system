<?php
declare(strict_types=1);

/** Session wrapper: flash messages, old input, validation errors and CSRF. */
final class Session
{
    private static array $carried = [];

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $dir = getenv('AGRIRENT_SESSION_DIR') ?: STORAGE_PATH . '/sessions';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        session_save_path($dir);
        session_name('AGRIRENTSESSID');
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', '86400');
        session_start();

        // Data written for "the next request" (old input, field errors) becomes readable now.
        self::$carried = $_SESSION['_next'] ?? [];
        unset($_SESSION['_next']);
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function pull(string $key, $default = null)
    {
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        return $value;
    }

    /* Flash (toast) messages */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = [$type, $message];
    }

    public static function flashes(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }

    /* Old input & validation errors (available on the following request) */
    public static function keepInput(array $input, array $errors = []): void
    {
        unset($input['_token'], $input['password'], $input['password_confirmation'], $input['current_password'], $input['card_number'], $input['cvc'], $input['pin']);
        $_SESSION['_next'] = ['old' => $input, 'errors' => $errors];
    }

    public static function old(string $key, $default = '')
    {
        return self::$carried['old'][$key] ?? $default;
    }

    public static function error(string $key): ?string
    {
        return self::$carried['errors'][$key] ?? null;
    }

    public static function errors(): array
    {
        return self::$carried['errors'] ?? [];
    }

    /* CSRF */
    public static function csrf(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        return !empty($_SESSION['_csrf']) && is_string($token) && hash_equals($_SESSION['_csrf'], $token);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
        }
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }
}
