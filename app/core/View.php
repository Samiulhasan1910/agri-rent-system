<?php
declare(strict_types=1);

/** Renders PHP templates from app/views, optionally wrapped in a layout. */
final class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'app'): string
    {
        $content = self::partial($view, $data);
        if ($layout === null) {
            return $content;
        }
        return self::partial('layouts/' . $layout, $data + ['content' => $content]);
    }

    public static function partial(string $view, array $data = []): string
    {
        $file = APP_PATH . '/views/' . $view . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View not found: {$view}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string)ob_get_clean();
    }
}
