<?php
declare(strict_types=1);

/**
 * Application bootstrap: configuration, autoloading, session, language and error handling.
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');

$GLOBALS['__config'] = require BASE_PATH . '/config/config.php';

date_default_timezone_set($GLOBALS['__config']['app']['timezone'] ?? 'Asia/Dhaka');
mb_internal_encoding('UTF-8');

spl_autoload_register(static function (string $class): void {
    foreach (['core', 'services', 'models', 'controllers'] as $dir) {
        $file = APP_PATH . "/{$dir}/{$class}.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

require APP_PATH . '/helpers.php';

$debug = (bool)config('app.debug');
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $e): void {
    ErrorHandler::handle($e);
});

if (PHP_SAPI !== 'cli') {
    Session::start();
}
