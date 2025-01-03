<?php

use App\Core\Config;
use App\Core\Database;
use App\Core\Router;

require __DIR__ . '/vendor/autoload.php';
define('VIEW_PATH', __DIR__ . '/resources/views/');


try {
    // Load environment variables
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

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

    // Initialize router with services
    $router = new Router();

    // Load routes
    $routes = require __DIR__ . '/routes/web.php';
    $routes($router);
    $GLOBALS['db'] = $db;
    $GLOBALS['config'] = $config;

    return $router;

    // // Initialize payment processor
    // $paymentProcessor = new App\PaymentProcessor($config['razorpay'], $db);

    // Make objects available globally (you might want to use dependency injection in a larger application)
    // $GLOBALS['paymentProcessor'] = $paymentProcessor;
} catch (Exception $e) {
    error_log("Bootstrap Error: " . $e->getMessage());
    die('An error occurred during application startup. Please check the logs.');
}
