<?php
namespace App\Services;

class ZohoService
{
    private $accessToken;
    private $organizationId;
    private $apiBase;
    private $refreshToken;
    private $config;

    public function __construct($config)
    {
         $this->config = $config;
        $this->accessToken = $config->get('zoho.access_token');
        $this->organizationId = $config->get('zoho.organization_id');
        $this->apiBase = $config->get('zoho.api_base') ?? 'https://www.zohoapis.in/invoice/v3';
        $this->refreshToken = $config->get('zoho.refresh_token') ?? null;
    }

    public function getInvoiceByNumber($invoiceNumber)
    {
        // 1. Search by invoice number
        $url = "{$this->apiBase}/invoices?invoice_number={$invoiceNumber}&organization_id={$this->organizationId}";
        $response = $this->makeRequest($url);

        if (!isset($response['invoices'][0]['invoice_id'])) {
            return null;
        }

        $invoiceId = $response['invoices'][0]['invoice_id'];

        // 2. Fetch full invoice details by ID
        $url = "{$this->apiBase}/invoices/{$invoiceId}?organization_id={$this->organizationId}";
        $response = $this->makeRequest($url);

        return $response['invoice'] ?? null;
    }

    public function recordPayment($invoiceId, $amount, $date = null, $paymentMode = 'Paypal', $description = 'Paid via PayPal')
    {
        // Fetch invoice to get customer_id
        $db = $GLOBALS['db'];
        $row = $db->query("SELECT data FROM invoices WHERE invoice_id = ?", [$invoiceId])->find();
        $invoice = $row ? json_decode($row['data'], true) : null;
        if (!$invoice || empty($invoice['customer_id'])) {
            file_put_contents(__DIR__ . '/../../public/zoho_debug.txt', "[recordPayment] ERROR: Missing customer_id for invoice $invoiceId\n", FILE_APPEND);
            return ["error" => "Missing customer_id for invoice $invoiceId"];
        }
        $customerId = $invoice['customer_id'];
        $url = "https://www.zohoapis.in/books/v3/customerpayments?organization_id={$this->organizationId}";
        $payload = [
            'customer_id' => $customerId,
            'payment_mode' => $paymentMode,
            'amount' => $amount,
            'date' => $date ?? date('Y-m-d'),
            'description' => $description,
            'invoices' => [
                [
                    'invoice_id' => $invoiceId,
                    'amount_applied' => $amount
                ]
            ]
        ];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Zoho-oauthtoken {$this->accessToken}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        file_put_contents(__DIR__ . '/../../public/zoho_debug.txt', "[recordPayment] URL: $url\nHTTP Code: $httpCode\nPayload: " . json_encode($payload) . "\nResponse: $response\n", FILE_APPEND);
        curl_close($ch);
        return json_decode($response, true);
    }

    /**
     * Record a payment in Zoho Books and return the API response
     */
    public function recordPaymentForInvoice($invoice, $amount)
    {
        $zohoInvoiceId = $invoice['invoice_id'];
        return $this->recordPayment($zohoInvoiceId, $amount);
    }

    /**
     * Prepare transaction data for the Zoho success page
     */
    public function prepareTransactionData($invoice, $payerId = null)
    {
        // Determine payment method
        $payment_method = 'Zoho';
        $card_network = null;
        $transaction_fee = null;
        $payment_id = '';
        $created_at = date('Y-m-d H:i:s');

        if (!empty($invoice['paypal_payment'])) {
            $payment_method = 'PayPal';
            $payment_id = $invoice['paypal_payment']['payment_id'] ?? '';
            $created_at = $invoice['paypal_payment']['paid_at'] ?? $created_at;
            // Optionally extract more PayPal details if needed
        } elseif (!empty($invoice['razorpay_payment'])) {
            $payment_method = 'Razorpay';
            $payment_id = $invoice['razorpay_payment']['payment_id'] ?? '';
            $created_at = $invoice['razorpay_payment']['paid_at'] ?? $created_at;
            // Optionally extract more Razorpay details if needed
        }

        return [
            'invoice_id' => $invoice['invoice_id'],
            'payment_id' => $payerId ?? $payment_id,
            'created_at' => $created_at,
            'amount' => $invoice['total'],
            'currency_type' => $invoice['currency_code'],
            'payment_method' => $payment_method,
            'status' => $invoice['status'],
            // Add more fields as needed
        ];
    }

    /**
     * Fetch invoice data from the database by invoice_id
     */
    public function fetchInvoiceById($invoiceId)
    {
        $db = $GLOBALS['db'];
        $row = $db->query("SELECT data FROM invoices WHERE invoice_id = ?", [$invoiceId])->find();
        return $row ? json_decode($row['data'], true) : null;
    }

    /**
     * Update invoice and insert PayPal payment record for handlePayPalSuccess
     */
    public function updateInvoiceAndInsertPayPalPayment($invoice, $invoiceId, $orderId, $payerId, $details)
    {
        $invoice['paypal_payment'] = [
            'order_id' => $orderId,
            'payment_id' => $payerId,
            'paypal_response' => $details,
            'paid_at' => date('Y-m-d H:i:s')
        ];
        $invoice['status'] = 'paid';
        $invoice['payment_made'] = $invoice['total'];
        $invoice['balance'] = 0;
        $db = $GLOBALS['db'];
        $db->query("UPDATE invoices SET data = ? WHERE invoice_id = ?", [json_encode($invoice), $invoiceId]);

        // Insert into paypal_payment table with all required fields
        $billing = $invoice['billing_address'] ?? [];
        $customer = $invoice['customer_name'] ?? '';
        $email = $invoice['email'] ?? '';
        $tel = $invoice['phone'] ?? '';
        $address = $billing['address'] ?? '';
        $city = $billing['city'] ?? '';
        $state = $billing['state'] ?? '';
        $zip_code = $billing['zip'] ?? '';
        $country = $billing['country'] ?? '';
        $amount = $invoice['total'] ?? 0;
        $currency_type = $invoice['currency_code'] ?? '';
        $original_amount = $amount;
        $original_currency = $currency_type;
        $bank_ref_no = $details['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;
        $status = $details['status'] ?? 'COMPLETED';
        $payment_method = 'PayPal';
        $card_network = null;
        $transaction_fee = $details['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['value'] ?? null;
        $service_tax = null;
        $error_message = $details['error'] ?? null;
        $paypal_response = json_encode($details);
        $transaction_time = $details['update_time'] ?? date('Y-m-d H:i:s');
        if ($transaction_time && strpos($transaction_time, 'T') !== false) {
            // Convert ISO 8601 to MySQL datetime
            $dt = new \DateTime($transaction_time);
            $transaction_time = $dt->format('Y-m-d H:i:s');
        }
        $now = date('Y-m-d H:i:s');

        $db->query(
            "INSERT INTO paypal_payment (payment_id, order_id, zoho_invoice_id, name, email, tel, address, city, state, zip_code, country, amount, currency_type, original_amount, original_currency, bank_ref_no, status, payment_method, card_network, transaction_fee, service_tax, error_message, paypal_response, zoho_response, transaction_time, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $payerId,
                $orderId,
                $invoice['invoice_id'], // zoho_invoice_id
                $customer,
                $email,
                $tel,
                $address,
                $city,
                $state,
                $zip_code,
                $country,
                $amount,
                $currency_type,
                $original_amount,
                $original_currency,
                $bank_ref_no,
                $status,
                $payment_method,
                $card_network,
                $transaction_fee,
                $service_tax,
                $error_message,
                $paypal_response,
                null, // zoho_response
                $transaction_time,
                $now,
                $now
            ]
        );
    }

    /**
     * Update invoice and insert Razorpay payment record for handleRazorpaySuccess
     */
    public function updateInvoiceAndInsertRazorpayPayment($invoice, $invoiceId, $razorpayPaymentId, $razorpayOrderId, $razorpaySignature, $zohoApiResponse = null)
    {
        $this->logRazorpayDebug(['step' => 'start_updateInvoiceAndInsertRazorpayPayment', 'invoiceId' => $invoiceId, 'razorpayPaymentId' => $razorpayPaymentId, 'razorpayOrderId' => $razorpayOrderId, 'razorpaySignature' => $razorpaySignature, 'zohoApiResponse' => $zohoApiResponse]);
        $invoice['razorpay_payment'] = [
            'payment_id' => $razorpayPaymentId,
            'order_id' => $razorpayOrderId,
            'signature' => $razorpaySignature,
            'paid_at' => date('Y-m-d H:i:s'),
            'razorpay_response' => [
                'payment_id' => $razorpayPaymentId,
                'order_id' => $razorpayOrderId,
                'signature' => $razorpaySignature
            ]
        ];
        $invoice['status'] = 'paid';
        $invoice['payment_made'] = $invoice['total'];
        $invoice['balance'] = 0;
        $db = $GLOBALS['db'];
        $updateResult = $db->query("UPDATE invoices SET data = ? WHERE invoice_id = ?", [json_encode($invoice), $invoiceId]);
        $this->logRazorpayDebug(['step' => 'after_update_invoices', 'query' => 'UPDATE invoices SET data = ? WHERE invoice_id = ?', 'params' => [json_encode($invoice), $invoiceId], 'result' => $updateResult]);

        // Optionally, insert into razorpay_payment table (create if not exists)
        $billing = $invoice['billing_address'] ?? [];
        $customer = $invoice['customer_name'] ?? '';
        $email = $invoice['email'] ?? '';
        $tel = $invoice['phone'] ?? '';
        $address = $billing['address'] ?? '';
        $city = $billing['city'] ?? '';
        $state = $billing['state'] ?? '';
        $zip_code = $billing['zip'] ?? '';
        $country = $billing['country'] ?? '';
        $amount = $invoice['total'] ?? 0;
        $currency_type = $invoice['currency_code'] ?? '';
        $original_amount = $amount;
        $original_currency = $currency_type;
        $status = 'COMPLETED';
        $payment_method = 'Razorpay';
        $transaction_time = date('Y-m-d H:i:s');
        $now = date('Y-m-d H:i:s');
        $insertParams = [
            $razorpayPaymentId,
            $razorpayOrderId,
            $invoice['invoice_id'],
            $customer,
            $email,
            $tel,
            $address,
            $city,
            $state,
            $zip_code,
            $country,
            $amount,
            $currency_type,
            $original_amount,
            $original_currency,
            $status,
            $payment_method,
            $razorpaySignature,
            json_encode($invoice['razorpay_payment']),
            $zohoApiResponse ? json_encode($zohoApiResponse) : null,
            $transaction_time,
            $now,
            $now
        ];
        $insertResult = $db->query(
            "INSERT INTO razorpay_payment (payment_id, order_id, zoho_invoice_id, name, email, tel, address, city, state, zip_code, country, amount, currency_type, original_amount, original_currency, status, payment_method, razorpay_signature, razorpay_response, zoho_response, transaction_time, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            $insertParams
        );
        $this->logRazorpayDebug(['step' => 'after_insert_razorpay_payment', 'query' => 'INSERT INTO razorpay_payment ...', 'params' => $insertParams, 'result' => $insertResult]);
    }

    private function makeRequest($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Zoho-oauthtoken {$this->accessToken}"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        file_put_contents(__DIR__ . '/../../public/zoho_debug.txt', "[makeRequest] URL: $url\nHTTP Code: $httpCode\nResponse: $response\n", FILE_APPEND);
        curl_close($ch);

        // If token expired or unauthorized, try to refresh and retry once
        if ($httpCode === 401 && $this->refreshToken) {
            file_put_contents(__DIR__ . '/../../public/zoho_debug.txt', "[makeRequest] 401 received, attempting token refresh...\n", FILE_APPEND);
            if ($this->refreshAccessToken()) {
                // Retry request with new token
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Zoho-oauthtoken {$this->accessToken}"
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                file_put_contents(__DIR__ . '/../../public/zoho_debug.txt', "[makeRequest] Retried URL: $url\nHTTP Code: $httpCode\nResponse: $response\n", FILE_APPEND);
                curl_close($ch);
            } else {
                file_put_contents(__DIR__ . '/../../public/zoho_debug.txt', "[makeRequest] Token refresh failed.\n", FILE_APPEND);
            }
        }

        return json_decode($response, true);
    }

   
    private function refreshAccessToken()
    {
        // Use Zoho India OAuth endpoint for token refresh
        $clientId = $this->config->get('zoho.client_id');
        $clientSecret = $this->config->get('zoho.client_secret');
        $refreshToken = $this->refreshToken;
        if (!$clientId || !$clientSecret || !$refreshToken) {
            file_put_contents(__DIR__ . '/../../public/zoho_debug.txt', "[refreshAccessToken] Missing client_id, client_secret, or refresh_token.\n", FILE_APPEND);
            return false;
        }
        // India OAuth endpoint for Zoho Books
        $url = 'https://accounts.zoho.in/oauth/v2/token';
        $data = http_build_query([
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'refresh_token',
        ]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        file_put_contents(__DIR__ . '/../../public/zoho_debug.txt', "[refreshAccessToken] Request: $data\nResponse: $response\n", FILE_APPEND);
        curl_close($ch);
        $result = json_decode($response, true);
        if (isset($result['access_token'])) {
            $this->accessToken = $result['access_token'];
            // Save new access token to config file
            $this->updateConfigAccessToken($result['access_token']);
            file_put_contents(__DIR__ . '/../../public/zoho_debug.txt', "[refreshAccessToken] Token refreshed successfully.\n", FILE_APPEND);
            return true;
        }
        file_put_contents(__DIR__ . '/../../public/zoho_debug.txt', "[refreshAccessToken] Failed to refresh token.\n", FILE_APPEND);
        return false;
    }

    private function updateConfigAccessToken($newToken)
    {
        // Update the config file (optional, only if you want to persist the new token)
        $configArray = $this->config->get('zoho');
        $configArray['access_token'] = $newToken;
        $export = var_export($configArray, true);
        $content = "<?php\n\nreturn $export;\n";
        file_put_contents(__DIR__ . '/../../config/zoho.php', $content);

        // Update in-memory config for this instance
        // Note: This only updates the local property, not the global config
        $this->accessToken = $newToken;
    }

    // Add logging helper for Razorpay DB/API actions
    private function logRazorpayDebug($data) {
        $logFile = __DIR__ . '/../../storage/logs/razorpay_debug.txt';
        $entry = "[" . date('Y-m-d H:i:s') . "] " . print_r($data, true) . "\n";
        file_put_contents($logFile, $entry, FILE_APPEND);
    }
} 