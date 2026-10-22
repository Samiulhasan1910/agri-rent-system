<?php
declare(strict_types=1);

/** Converts uncaught exceptions into friendly pages or JSON responses. */
final class ErrorHandler
{
    public static function handle(Throwable $e): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $status = $e instanceof HttpException ? $e->status() : 500;
        $message = $e->getMessage();

        if ($e instanceof PDOException && self::isMissingDatabase($e)) {
            http_response_code(503);
            echo View::render('errors/setup', ['error' => $message], null);
            return;
        }

        if ($status >= 500) {
            error_log('[' . date('Y-m-d H:i:s') . '] ' . get_class($e) . ': ' . $message . ' in ' . $e->getFile() . ':' . $e->getLine());
            $message = config('app.debug') ? $message : t('Something went wrong. Please try again.');
        }

        $defaults = [
            403 => t('You are not authorized to access this page.'),
            404 => t('The page you are looking for could not be found.'),
            405 => t('This action requires a form submission.'),
            419 => t('Your session form token expired. Please refresh the page and try again.'),
        ];
        if ($message === '') {
            $message = $defaults[$status] ?? t('Something went wrong. Please try again.');
        }

        if (PHP_SAPI !== 'cli' && request_is_ajax()) {
            if (!headers_sent()) {
                http_response_code($status);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!headers_sent()) {
            http_response_code($status);
        }
        try {
            $layout = Auth::user() ? 'app' : 'public';
        } catch (Throwable $ignored) {
            $layout = null;
        }
        try {
            echo View::render('errors/error', [
                'title' => (string)$status,
                'status' => $status,
                'message' => $message,
                'trace' => config('app.debug') && $status >= 500 ? $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() : null,
            ], $layout);
        } catch (Throwable $inner) {
            echo '<h1>' . $status . '</h1><p>' . htmlspecialchars($message) . '</p>';
        }
    }

    private static function isMissingDatabase(PDOException $e): bool
    {
        $info = $e->errorInfo[1] ?? null;
        $code = (string)$e->getCode();
        return in_array($info, [1049, 1146], true) || $code === '42S02' || str_contains($e->getMessage(), 'Unknown database')
            || str_contains($e->getMessage(), "Unknown column") || str_contains($e->getMessage(), '2002');
    }
}
