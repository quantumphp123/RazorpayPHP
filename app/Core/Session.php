<?php
namespace App\Core;

class Session
{
    private const SESSION_LIFETIME = 1800; // 30 minutes
    private const PAYMENT_SESSION_KEY = 'payment_session';

    public function __construct()
    {
        $this->configureSession();
        $this->startSession();
        $this->validateSession();
    }

    private function configureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Only set session cookie params if the session is not started
            session_set_cookie_params([
                'lifetime' => self::SESSION_LIFETIME,
                'path' => '/',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function validateSession(): void
    {
        if ($this->isExpired()) {
            $this->regenerateSession();
        }
        $_SESSION['last_activity'] = time();
    }

    private function isExpired(): bool
    {
        return isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity'] > self::SESSION_LIFETIME);
    }

    private function regenerateSession(): void
    {
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }

    public function startPaymentSession(array $paymentData): void
    {
        $_SESSION[self::PAYMENT_SESSION_KEY] = [
            'started_at' => time(),
            'payment_data' => $paymentData,
            'order_id' => null,
            'status' => 'initiated'
        ];
    }

    public function updatePaymentSession(string $orderId): void
    {
        if (isset($_SESSION[self::PAYMENT_SESSION_KEY])) {
            $_SESSION[self::PAYMENT_SESSION_KEY]['order_id'] = $orderId;
            $_SESSION[self::PAYMENT_SESSION_KEY]['status'] = 'order_created';
        }
    }

    public function getPaymentData(): ?array
    {
        return $_SESSION[self::PAYMENT_SESSION_KEY]['payment_data'] ?? null;
    }

    public function getOrderId(): ?string
    {
        return $_SESSION[self::PAYMENT_SESSION_KEY]['order_id'] ?? null;
    }

    public function endPaymentSession(string $status = 'completed'): void
    {
        if (isset($_SESSION[self::PAYMENT_SESSION_KEY])) {
            $_SESSION[self::PAYMENT_SESSION_KEY]['status'] = $status;
            $_SESSION[self::PAYMENT_SESSION_KEY]['completed_at'] = time();

            // Optionally log the session data before clearing
            // $this->logPaymentSession();

            // Clear payment session data
            unset($_SESSION[self::PAYMENT_SESSION_KEY]);
        }
    }

    private function logPaymentSession(): void
    {
        if (isset($_SESSION[self::PAYMENT_SESSION_KEY])) {
            $logData = [
                'session_id' => session_id(),
                'payment_data' => $_SESSION[self::PAYMENT_SESSION_KEY],
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'timestamp' => date('Y-m-d H:i:s')
            ];

            // Log to database or file
            global $db;
            // $db->insert('payment_session_logs', $logData);
        }
    }

    public function generateCSRFToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    public function getCSRFToken()
    {
        return $_SESSION['csrf_token'] ?? null;
    }

    public function validateCSRFToken(?string $token): bool
    {
        // If token is null or the session token is not set, return false
        if (is_null($token) || !isset($_SESSION['csrf_token'])) {
            return false;
        }

        // Otherwise, compare the session token with the provided token
        return hash_equals($_SESSION['csrf_token'], $token);
    }

}
