<?php

// Front Controller & Security Hardening Router for SiNilai SMK
require_once __DIR__ . '/../vendor/autoload.php';

// =====================================================================
// Load Environment Variables from .env file (Railway / Local Backend)
// The .env file MUST be placed OUTSIDE the public/ directory.
// On Railway: credentials are injected automatically as system env vars.
// =====================================================================
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$key, $val] = array_map('trim', explode('=', $line, 2));
            if (!empty($key) && !isset($_ENV[$key])) {
                $_ENV[$key] = $val;
                putenv("{$key}={$val}");
            }
        }
    }
}

// Disable error display in production to prevent leaking sensitive system paths
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// 1. OWASP Recommended Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
header('Content-Security-Policy: default-src \'none\'; frame-ancestors \'none\'');
header('Content-Type: application/json; charset=UTF-8');

// 2. Production CORS Configuration
// Handled by App\Config\Cors (supports local dev, Vercel production & preview, and custom env domains)
// Handles preflight OPTIONS immediately before any authentication filter is run.
App\Config\Cors::handle();

// 3. Routing
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $handled = App\Config\Routes::dispatch($method, $uri);

    if (!$handled) {
        // 404 Route Not Found
        http_response_code(404);
        echo json_encode([
            'status' => 404,
            'error' => 'Not Found',
            'message' => "Endpoint API '{$uri}' tidak ditemukan."
        ]);
    }
} catch (\Throwable $e) {
    // Production Error Handling: Never leak stack trace, file path, or DB schema details
    error_log("Unhandled exception [{$e->getCode()}]: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}");
    http_response_code(500);
    echo json_encode([
        'status' => 500,
        'error' => 'Internal Server Error',
        'message' => 'Terjadi kesalahan internal pada server. Silakan hubungi administrator.'
    ]);
}
