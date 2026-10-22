<?php
declare(strict_types=1);

/**
 * Base controller. Every form posts through success()/fail(), which answer
 * AJAX requests with JSON and normal requests with flash + redirect, so all
 * pages keep working without JavaScript.
 */
abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'app'): void
    {
        echo View::render($view, $data, $layout);
    }

    /** For live filters: return only the partial when ?partial=1 is requested via AJAX. */
    protected function wantsPartial(): bool
    {
        return isset($_GET['partial']) && $this->isAjax();
    }

    protected function partial(string $view, array $data = []): void
    {
        $this->json(['ok' => true, 'html' => View::partial($view, $data)]);
    }

    protected function json(array $payload, int $status = 200): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        exit;
    }

    protected function redirect(string $route, array $params = []): void
    {
        $this->redirectTo(url($route, $params));
    }

    protected function redirectTo(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }

    protected function backUrl(string $fallback = 'dashboard/index'): string
    {
        $ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        if ($ref !== '' && $host !== '' && parse_url($ref, PHP_URL_HOST) === parse_url('http://' . $host, PHP_URL_HOST)) {
            $path = (string)parse_url($ref, PHP_URL_PATH);
            if (str_ends_with($path, '/index.php') || str_ends_with($path, '/')) {
                $query = parse_url($ref, PHP_URL_QUERY);
                return 'index.php' . ($query ? '?' . $query : '');
            }
        }
        return url($fallback);
    }

    protected function isAjax(): bool
    {
        return request_is_ajax();
    }

    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    /** Ensures POST + valid CSRF token (form field _token or X-CSRF-Token header). */
    protected function requirePost(): void
    {
        if (!$this->isPost()) {
            throw new HttpException(405, t('This action requires a form submission.'));
        }
        $token = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!Session::verifyCsrf(is_string($token) ? $token : null)) {
            throw new HttpException(419, t('Your session form token expired. Please refresh the page and try again.'));
        }
    }

    protected function auth(): array
    {
        $user = Auth::user();
        if ($user) {
            return $user;
        }
        if ($this->isAjax()) {
            $this->json(['ok' => false, 'message' => t('You must be logged in to continue.'), 'redirect' => url('auth/login')], 401);
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            Session::set('intended', $_SERVER['REQUEST_URI'] ?? '');
        }
        Session::flash('warning', t('Please log in to continue.'));
        $this->redirect('auth/login');
        return [];
    }

    protected function role(string ...$roles): array
    {
        $user = $this->auth();
        if (!in_array($user['role'], $roles, true)) {
            throw new HttpException(403, t('You are not authorized to access this page.'));
        }
        return $user;
    }

    protected function input(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_array($value) ? $default : trim((string)$value);
    }

    protected function int(string $key, int $default = 0): int
    {
        $value = $this->input($key, (string)$default);
        return is_numeric($value) ? (int)$value : $default;
    }

    protected function success(string $message, ?string $redirect = null, array $data = []): void
    {
        if ($this->isAjax()) {
            if ($redirect) {
                Session::flash('success', $message);
            }
            $this->json(['ok' => true, 'message' => $message, 'redirect' => $redirect] + $data);
        }
        Session::flash('success', $message);
        $this->redirectTo($redirect ?? $this->backUrl());
    }

    protected function fail(string $message, array $errors = [], ?string $redirect = null, int $status = 422, array $data = []): void
    {
        if ($this->isAjax()) {
            $this->json(['ok' => false, 'message' => $message, 'errors' => $errors] + $data, $status);
        }
        Session::keepInput($_POST, $errors);
        Session::flash('error', $message);
        $this->redirectTo($redirect ?? $this->backUrl());
    }
}
