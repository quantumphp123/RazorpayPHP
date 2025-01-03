<?php

namespace App\Controllers;

use App\Core\Redirect;
use App\Core\Validator;
use App\Services\PaymentProcessor;
use Exception;

class PaymentController extends Controller
{
    public function logPaymentEvent()
    {
        try {

            $allowedEvents = [
                'payment_success',
                'payment_error',
                'payment_failed',
                'modal_closed'
            ];

            $rules = [
                'event_type' => ['required', 'string', 'in:' . implode(',', $allowedEvents)],
                'payment_id' => ['string'],
                'order_id' => ['string'],
                'amount' => ['numeric',],
                'currency' => ['string'],
                'error_code' => ['string'],
                'error_description' => ['string']
            ];

            $validator = new Validator($_POST, $rules);

            if (!$validator->validate()) {
                $errors = $validator->errors();
                $firstError = reset($errors)[0];
                throw new Exception($firstError);
            }

            $input = $validator->sanitized();

            $filteredData = array_filter($input, function ($value) {
                return $value !== null;
            });

            $logId = (new PaymentProcessor)->logPaymentDetails(
                $input['event_type'],
                $filteredData
            );

            jsonResponse([
                'success' => true,
                'log_id' => $logId
            ]);

        } catch (Exception $e) {
            jsonResponse([
                'success' => false,
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 400);
        }
    }

    public function verifyPayment()
    {
        try {
            global $session;

            // Validate CSRF
            if (!$session->validateCSRFToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }

            $rules = [
                'payment_id' => ['required', 'string'],
                'order_id' => ['required', 'string'],
                'signature' => ['required', 'string'],
            ];

            $validator = new Validator($_POST, $rules);

            if (!$validator->validate()) {
                $errors = $validator->errors();
                $firstError = reset($errors)[0];
                throw new Exception($firstError);
            }

            $input = $validator->sanitized();

            // Verify payment using the payment processor
            $payment = (new PaymentProcessor)->verifyPayment(
                $input['payment_id'],
                $input['order_id'],
                $input['signature']
            );

            if ($payment['verified']) {
                // Prepare the transaction data
                $transactionData = $this->prepareTransactionData($payment, $_POST);

                // Save the transaction and respond
                $savedPaymentId = (new PaymentProcessor)->saveTransaction($transactionData);

                jsonResponse([
                    'success' => true,
                    'transaction_id' => $savedPaymentId,
                    'razorpay_payment_id' => $input['payment_id']
                ]);
            } else {

                jsonResponse([
                    'success' => false,
                    'error' => 'Payment verification failed'
                ], 400);
            }
        } catch (Exception $e) {
            jsonResponse([
                'success' => false,
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);

        }
    }

    public function success()
    {
        $rules = [
            'payment_id' => ['required', 'string']
        ];
        $validator = new Validator($_GET, $rules);

        if (!$validator->validate()) {
            Redirect::to($GLOBALS['config']->get('app')['url']);
        }

        $input = $validator->sanitized();

        $transactionData = (new PaymentProcessor)->getTransactionDetails($input['payment_id']);

        if (!$transactionData) {
            Redirect::to($GLOBALS['config']->get('app')['url']);
        }

        $this->view('pages.success', [
            'transactionData' => $transactionData,
            'input' => $input
        ]);
    }
    
    public function error()
    {
        global $db;

        $rules = [
            'log_id' => ['required', 'string']
        ];
        $validator = new Validator($_GET, $rules);

        if (!$validator->validate()) {
            Redirect::to($GLOBALS['config']->get('app')['url']);
        }

        $input = $validator->sanitized();

        $logId = $input['log_id'];
        $error_code = null;
        $error_description = null;
        $payment_details = null;

        if ($logId) {
            // Fetch the log entry
            $sql = "SELECT data FROM payment_logs WHERE id = :id";
            $log = $db->query($sql, ['id' => $logId])->find();

            if ($log) {
                $logData = json_decode($log['data'], true);
                $error_code = $logData['error_code'] ?? null;
                $error_description = $logData['error_description'] ?? null;
                $payment_details = $logData['details'] ?? null;
            }
        }
        $this->view('pages.error', [
            'error_code' => $error_code,
            'error_description' => $error_description,
            'payment_details' => $payment_details
        ]);
    }

    protected function prepareTransactionData($payment, $postData)
    {
        return [
            'payment_id' => $postData['payment_id'],
            'order_id' => $postData['order_id'],
            'name' => $postData['name'],
            'email' => $postData['email'],
            'address' => $postData['address1'],
            'address2' => $postData['address2'],
            'city' => $postData['city'],
            'state' => $postData['state'],
            'country' => $postData['country'],
            'zip_code' => $postData['zipcode'],
            'amount' => $postData['amount'],
            'currency_type' => $postData['currency'],
            'status' => 'success',
            'payment_method' => $payment['payment_details']['method'],
            'card_network' => $payment['payment_details']['card']['network'] ?? null,
            'card_last4' => $payment['payment_details']['card']['last4'] ?? null,
            'card_issuer' => $payment['payment_details']['card']['issuer'] ?? null,
            'card_type' => $payment['payment_details']['card']['type'] ?? null,
            'bank_name' => $payment['payment_details']['bank'] ?? null,
            'wallet_type' => $payment['payment_details']['wallet'] ?? null,
            'vpa' => $payment['payment_details']['vpa'] ?? null,
        ];
    }
}
