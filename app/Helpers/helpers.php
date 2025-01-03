<?php

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
    http_response_code($code); // Set HTTP response code
    require __DIR__ . '/../templates/http-response/' . $code . '.php';
    die();  // Stop script execution
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
    $logFile = __DIR__ . '/../logs/payments.log';
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
    $logFile = __DIR__ . '/../logs/app.log';
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
