<?php
/**
 * AgriRent – Agricultural Equipment Rental and Management System
 * Front controller: every page is served as index.php?r=controller/action
 */
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

try {
    Lang::set((string)(Session::get('lang') ?: Settings::get('default_language', 'en')));
    Router::dispatch((string)($_GET['r'] ?? 'home/index'));
} catch (Throwable $e) {
    ErrorHandler::handle($e);
}
