<?php

namespace App\Services;

use EmailService;
use Exception;
use Razorpay\Api\Api;

class PaymentProcessor
{
    private $api;
    private $db;
    private $emailService;

    public function __construct()
    {
        $this->api = new Api(
            $GLOBALS['config']->get('payment')['key_id'],
            $GLOBALS['config']->get('payment')['key_secret']
        );

        $this->db = $GLOBALS['db'];

        // $this->emailService = new EmailService($config['email']);
    }

    public function createOrder($amount, $currency)
    {
        try {
            // Validate amount and currency before creating order
            $this->validateAmount($amount);
            $this->validateCurrency($currency);

            $order = $this->api->order->create([
                'amount' => $amount * 100, // Convert to smallest currency unit
                'currency' => $currency,
                'payment_capture' => 1,
                'notes' => [
                    'currency_original' => $currency,
                    'amount_original' => $amount
                ]
            ]);

            // Log order creation
            $this->logOrder($order->id, $amount, $currency);

            return $order;
        } catch (Exception $e) {
            $this->logError('Order Creation Error', $e->getMessage());
            throw new Exception("Error creating order: " . $e->getMessage());
        }
    }

    public function verifyPayment($paymentId, $orderId, $signature)
    {
        try {
            $attributes = [
                'razorpay_payment_id' => $paymentId,
                'razorpay_order_id' => $orderId,
                'razorpay_signature' => $signature
            ];

            $this->api->utility->verifyPaymentSignature($attributes);

            // Fetch payment details from Razorpay
            $paymentDetails = $this->api->payment->fetch($paymentId);

            // Send email notification
            // $emailData = [
            //     'status' => 'success',
            //     'payment_id' => $paymentId,
            //     'order_id' => $orderId,
            //     'name' => $paymentData['name'],
            //     'email' => $paymentData['email'],
            //     'amount' => $payment['amount'],
            //     'currency' => $payment['currency'],
            //     'payment_method' => $paymentDetails->method,
            // ];

            // $this->emailService->sendPaymentNotification($emailData);

            return [
                'verified' => true,
                'payment_details' => [
                    'method' => $paymentDetails->method,
                    'card' => isset($paymentDetails->card) ? [
                        'network' => $paymentDetails->card->network,
                        'last4' => $paymentDetails->card->last4,
                        'issuer' => $paymentDetails->card->issuer,
                        'type' => $paymentDetails->card->type,
                        'international' => $paymentDetails->card->international
                    ] : null,
                    'bank' => $paymentDetails->bank,
                    'wallet' => $paymentDetails->wallet,
                    'vpa' => $paymentDetails->vpa
                ]
            ];
        } catch (Exception $e) {
            $this->logError('Payment Verification Error', $e->getMessage());
            return [
                'verified' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function saveTransaction($data)
    {
        try {
            // Sanitize and validate data
            $sanitizedData = $this->sanitizeTransactionData($data);

            // Insert main transaction record
            $sql = "INSERT INTO payment_transactions (
                payment_id, order_id, name, email, tel,
                address, address2, city, state, zip_code, country,
                amount, currency_type, status, payment_method,
                card_network, card_last4, card_issuer, card_type,
                bank_name, wallet_type, vpa, error_message
            ) VALUES (
                :payment_id, :order_id, :name, :email, :tel,
                :address, :address2, :city, :state, :zip_code, :country,
                :amount, :currency_type, :status, :payment_method,
                :card_network, :card_last4, :card_issuer, :card_type,
                :bank_name, :wallet_type, :vpa, :error_message
            )";

            $this->db->query($sql, $sanitizedData);

            return $this->db->getLastInsertId();
        } catch (Exception $e) {
            $this->logError('Transaction Save Error', $e->getMessage());
            throw new Exception("Error saving transaction: " . $e->getMessage());
        }
    }

    private function sanitizeTransactionData($data)
    {
        $rules = [
            'payment_id' => ['required', 'string'],
            'order_id' => ['required', 'string'],
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'tel' => ['string'],
            'address' => ['string'],
            'address2' => ['string'],
            'city' => ['string'],
            'state' => ['string'],
            'zip_code' => ['string'],
            'country' => ['string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency_type' => ['required', 'string', 'in:INR,AUD,USD,GBP,CAD,EUR,SGD'],
            'status' => ['required', 'string'],
            'payment_method' => ['required', 'string'],
            'card_network' => ['string'],
            'card_last4' => ['string'],
            'card_issuer' => ['string'],
            'card_type' => ['string'],
            'bank_name' => ['string'],
            'wallet_type' => ['string'],
            'vpa' => ['string'],
            'error_message' => ['string']
        ];
        // Validate and sanitize
        $validator = validate($data, $rules);

        if (!$validator->validate()) {
            // Log validation errors
            $errors = $validator->errors();
            $errorMessage = "Validation failed: " . json_encode($errors);
            logPaymentError($errorMessage);

            // Throw exception or handle errors as needed
            throw new \InvalidArgumentException($errorMessage);
        }

        // Get sanitized data
        return $validator->sanitized();
    }

    public function logPaymentDetails($eventType, $data)
    {
        // Log raw response for debugging
        $sql = "INSERT INTO payment_logs (
            log_type, data
        ) VALUES (
            :log_type, :data
        )";

        $this->db->query($sql, [
            'log_type' => $eventType,
            'data' => json_encode($data)
        ]);

        return $this->db->getLastInsertId();
    }


    public function logOrder($orderId, $amount, $currency)
    {
        try {
            $sql = "INSERT INTO order_logs (order_id, amount, currency)
                VALUES (:order_id, :amount, :currency)";

            $this->db->query($sql, [
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => $currency,
            ]);

        } catch (Exception $e) {
            // Just log the error but don't throw - this is non-critical
            $this->logError('Order Logging Error', $e->getMessage());
        }
    }

    public function logError($type, $message)
    {
        try {
            $sql = "INSERT INTO error_logs (error_type, error_message)
                VALUES (:error_type, :error_message)";

            $this->db->query($sql, [
                'error_type' => $type,
                'error_message' => $message,
            ]);
        } catch (Exception $e) {
            // If we can't even log the error, write to error_log
            logPaymentError("Payment System Error ($type): $message");
        }
    }

    private function validateAmount($amount)
    {
        if (!is_numeric($amount) || $amount <= 0) {
            throw new Exception("Invalid amount: Amount must be greater than 0");
        }
    }

    private function validateCurrency($currency)
    {
        $allowedCurrencies = ['INR', 'USD', 'EUR', 'GBP', 'SGD', 'AUD', 'CAD'];
        if (!in_array($currency, $allowedCurrencies)) {
            throw new Exception("Invalid currency: {$currency}");
        }
    }

    public function getTransactionDetails($paymentId)
    {
        try {
            // Fetch the transaction details by payment_id
            $sql = "SELECT * FROM payment_transactions WHERE payment_id = :payment_id LIMIT 1";
            $this->db->query($sql, ['payment_id' => $paymentId]);
            $transaction = $this->db->find(); // Use find() to get the first result

            if (!$transaction) {
                // Log the error if transaction is not found
                $this->logError('Transaction Details Error', "Transaction with payment_id {$paymentId} not found.");
                return false;
            }

            return [
                'transaction' => $transaction,
            ];
        } catch (Exception $e) {
            $this->logError('Transaction Details Error', $e->getMessage());
            return false;
        }
    }

    public function refundPayment($paymentId, $amount = null)
    {
        try {
            $refund = $this->api->refund->create([
                'payment_id' => $paymentId,
                'amount' => $amount ? $amount * 100 : null // Optional partial refund
            ]);

            // Save refund details
            $this->saveRefund($paymentId, $refund);

            return $refund;
        } catch (Exception $e) {
            $this->logError('Refund Error', $e->getMessage());
            throw new Exception("Error processing refund: " . $e->getMessage());
        }
    }

    private function saveRefund($paymentId, $refundData)
    {
        try {
            $sql = "INSERT INTO payment_refunds (
                payment_id, refund_id, amount, status, created_at
            ) VALUES (
                :payment_id, :refund_id, :amount, :status, NOW()
            )";

            $this->db->query($sql, [
                'payment_id' => $paymentId,
                'refund_id' => $refundData->id,
                'amount' => $refundData->amount / 100,
                'status' => $refundData->status
            ]);

            // Update original transaction status
            $this->updateTransactionStatus($paymentId, 'refunded');
        } catch (Exception $e) {
            $this->logError('Refund Save Error', $e->getMessage());
            throw new Exception("Error saving refund details: " . $e->getMessage());
        }
    }

    private function updateTransactionStatus($paymentId, $status)
    {
        $sql = "UPDATE payment_transactions
                SET status = :status, updated_at = NOW()
                WHERE payment_id = :payment_id";

        $this->db->query($sql, [
            'status' => $status,
            'payment_id' => $paymentId
        ]);
    }

    public function getPaymentStatistics($startDate = null, $endDate = null)
    {
        try {
            $params = [];
            $dateCondition = "";

            if ($startDate && $endDate) {
                $dateCondition = "WHERE created_at BETWEEN :start_date AND :end_date";
                $params['start_date'] = $startDate;
                $params['end_date'] = $endDate;
            }

            $sql = "SELECT
                    COUNT(*) as total_transactions,
                    SUM(amount) as total_amount,
                    currency_type,
                    status,
                    COUNT(CASE WHEN status = 'success' THEN 1 END) as successful_transactions,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_transactions,
                    AVG(CASE WHEN status = 'success' THEN amount END) as avg_transaction_amount
                FROM payment_transactions
                $dateCondition
                GROUP BY currency_type, status";

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            $this->logError('Statistics Error', $e->getMessage());
            throw new Exception("Error generating payment statistics: " . $e->getMessage());
        }
    }
}
