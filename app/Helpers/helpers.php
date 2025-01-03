<?php

use App\Core\Session;
use App\Core\Validator;

function dd($value)
{
    echo "<pre>";
    var_dump($value);
    echo "</pre>";

    die();
}

function abort($code = 404)
{
    showError(['status' => $code]);
    die();  // Stop script execution
}

function showError(array $errorData)
{
    // Default to 500 for internal server errors
    http_response_code($errorData['status'] ?? 500);

    // Check if the request expects HTML or JSON
    if (isHtmlRequest()) {
        $filePath = VIEW_PATH . 'http-response/error.php';
        if (file_exists($filePath)) {
            extract([
                'errorCode' => $errorData['status'],
                'errorTitle' => getErrorTitle($errorData['status']),
                'errorDescription' => $errorData['message']
            ]);

            // Include the view file
            include $filePath;
        } else {
            throw new Exception("Error Page not found.");
        }
    } else {
        // Return JSON error response for JSON requests
        jsonResponse([
            'success' => false,
            'error' => $errorData['message'] ?? ''
        ], $errorData['status']);
    }
}

function isHtmlRequest(): bool
{
    return strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false;
}


/**
 * Get error title based on the status code
 *
 * @param int $status
 * @return string
 */
function getErrorTitle(int $status): string
{
    switch ($status) {
        case 404:
            return 'Page Not Found';
        case 500:
            return 'Internal Server Error';
        case 401:
            return 'Unauthorized';
        case 403:
            return 'Forbidden';
        case 405:
            return 'Method Not Allowed';
        case 429:
            return 'Too many requests';
        default:
            return 'An Error Occurred';
    }
}

function validate(array $data, array $rules): Validator
{
    return new Validator($data, $rules);
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function logPaymentError($error)
{
    // Get absolute path to logs directory without using realpath
    $logFile = __DIR__ . '/../../storage/logs/payments.log';
    $logsDir = dirname($logFile);

    // Create logs directory if it doesn't exist
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0777, true);
    }

    // Add timestamp to the log message
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $error\n";

    try {
        if (file_put_contents($logFile, $logMessage, FILE_APPEND) === false) {
            error_log("Failed to write to payment log file: $logFile");
        }
    } catch (Exception $e) {
        error_log("Exception while writing to payment log: " . $e->getMessage());
    }
}

function info($data)
{
    // Get absolute path to logs directory without using realpath
    $logFile = __DIR__ . '/../../storage/logs/app.log';
    $logsDir = dirname($logFile);

    // Create logs directory if it doesn't exist
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0777, true);
    }

    // Convert data to a string
    if (is_array($data) || is_object($data)) {
        $data = json_encode($data, JSON_PRETTY_PRINT);
    }

    // Add timestamp to the log message
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] INFO: $data\n";

    try {
        if (file_put_contents($logFile, $logMessage, FILE_APPEND) === false) {
            error_log("Failed to write to app log file: $logFile");
        }
    } catch (Exception $e) {
        error_log("Exception while writing to app log: " . $e->getMessage());
    }
}

function csrf_token()
{
    return (new Session)->generateCSRFToken();
}
