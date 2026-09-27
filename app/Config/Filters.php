<?php

namespace App\Config;

use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

class Filters
{
    /**
     * List of public route patterns that do NOT require JWT authentication
     */
    public static array $publicRoutes = [
        'GET'  => [
            '#^/api/health$#',
        ],
        'POST' => [
            '#^/api/login$#',
        ],
    ];

    /**
     * Check if the current request is public (no authentication needed)
     */
    public static function isPublicRoute(string $method, string $uri): bool
    {
        // OPTIONS preflight is ALWAYS public and never requires authentication
        if ($method === 'OPTIONS') {
            return true;
        }

        if (isset(self::$publicRoutes[$method])) {
            foreach (self::$publicRoutes[$method] as $pattern) {
                if (preg_match($pattern, $uri)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Apply authentication filter if the route is protected
     */
    public static function applyAuth(): ?array
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (self::isPublicRoute($method, $uri)) {
            return null;
        }

        return AuthMiddleware::authenticate();
    }
}
