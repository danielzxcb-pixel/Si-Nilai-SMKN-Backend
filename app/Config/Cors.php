<?php

namespace App\Config;

class Cors
{
    /**
     * List of explicitly allowed origins
     */
    public static function getAllowedOrigins(): array
    {
        $origins = [
            // Local Development
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            'http://localhost:8000',
            'http://127.0.0.1:8000',
            // Default Vercel production domain
            'https://sinilaifrontend.vercel.app',
        ];

        // Read dynamically from environment variables
        $envOrigins = getenv('CORS_ALLOWED_ORIGINS') ?: getenv('FRONTEND_URL') ?: ($_ENV['CORS_ALLOWED_ORIGINS'] ?? $_ENV['FRONTEND_URL'] ?? '');
        if (!empty($envOrigins)) {
            $split = array_map('trim', explode(',', $envOrigins));
            foreach ($split as $o) {
                if (!empty($o) && !in_array($o, $origins, true)) {
                    $origins[] = rtrim($o, '/');
                }
            }
        }

        return $origins;
    }

    /**
     * Check whether an origin is allowed
     */
    public static function isOriginAllowed(string $origin): bool
    {
        if (empty($origin)) {
            return false;
        }

        $allowed = self::getAllowedOrigins();
        if (in_array($origin, $allowed, true)) {
            return true;
        }

        // Allow any Vercel deployment (*.vercel.app) - production or preview
        if (preg_match('#^https://[a-z0-9._-]+\.vercel\.app$#i', $origin)) {
            return true;
        }

        // Allow local network IP addresses for school / LAN testing
        if (preg_match('#^https?://(localhost|127\.0\.0\.1|192\.168\.\d+\.\d+|10\.\d+\.\d+\.\d+)(:\d+)?$#', $origin)) {
            return true;
        }

        return false;
    }

    /**
     * Handle CORS headers and preflight OPTIONS request
     */
    public static function handle(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (!empty($origin)) {
            if (self::isOriginAllowed($origin)) {
                header("Access-Control-Allow-Origin: {$origin}");
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept, X-Requested-With, Origin');
                header('Access-Control-Max-Age: 86400');
                header('Vary: Origin');
            } else if ($method === 'OPTIONS') {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 403,
                    'error' => 'Forbidden',
                    'message' => 'CORS Origin tidak diizinkan.'
                ]);
                exit;
            }
        }

        // Preflight OPTIONS for allowed origin or same-origin must be answered with 204 No Content
        if ($method === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
