<?php

use App\Core\Config;
use App\Core\Database;
use App\Core\Middleware;
use App\Core\Router;
use App\Core\Session;
use App\Middleware\RateLimiter\RateLimiter;

require __DIR__ . '/vendor/autoload.php';
define('VIEW_PATH', __DIR__ . '/resources/views/');


try {
    // Load environment variables
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Initialize session management
    $session = new Session();

    // Load configuration
    $config = new Config([
        'app' => require __DIR__ . '/config/app.php',
        'database' => require __DIR__ . '/config/database.php',
        'payment' => require __DIR__ . '/config/payment.php',
        'mail' => require __DIR__ . '/config/mail.php',
    ]);

    // Configure error reporting
    $config->configureErrorReporting();

    // Initialize database connection
    $db = new Database($config->get('database'));

    // Register rate limiting middleware
    Middleware::register('rateLimit', function () {
        $limiter = new RateLimiter();
        $rateLimitedRoutes = [
            '/',
            '/create-order',
            '/verify-payment',
        ];

        // Get the current request URI
        $currentRoute = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Only apply rate-limiting to specific routes
        if (in_array($currentRoute, $rateLimitedRoutes)) {
            if (!$limiter->check($_SERVER['REMOTE_ADDR'], $currentRoute, 6, 300)) {
                $errorData = [
                    'status' => 429,
                    'message' => 'Too many requests. Please try again in few minutes.'
                ];
                showError($errorData);
                return false;
            }
        }
        return true;
    });

    // Initialize router with services
    $router = new Router();

    // Load routes
    $routes = require __DIR__ . '/routes/web.php';
    $routes($router);
    $GLOBALS['db'] = $db;
    $GLOBALS['config'] = $config;
    $GLOBALS['session'] = $session;

    return $router;
} catch (Exception $e) {
    error_log("Bootstrap Error: " . $e->getMessage());
    die('An error occurred during application startup. Please check the logs.');
}
