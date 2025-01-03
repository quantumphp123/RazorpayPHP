<?php
// src/Controllers/OrderController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\PaymentProcessor;
use App\Core\Validator;
use Exception;

class OrderController
{

    public function create(): void
    {
        try {
            global $session;

            // Validate CSRF
            if (!$session->validateCSRFToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }

            $rules = [
                'amount' => ['required', 'numeric', 'min:0.01'],
                'currency' => ['required', 'string', 'in:INR,AUD,USD,GBP,CAD,EUR,SGD'],
                'name' => ['required', 'string', 'min:2'],
                'email' => ['required', 'email'],
                'address1' => ['required', 'string'],
                'country' => ['required', 'string'],
                'zipcode' => ['required', 'string'],
                'state' => ['required', 'string'],
                'city' => ['required', 'string']
            ];

            $validator = new Validator($_POST, $rules);

            if (!$validator->validate()) {
                $errors = $validator->errors();
                $firstError = reset($errors)[0];
                throw new Exception($firstError);
            }

            $input = $validator->sanitized();

            $order = (new PaymentProcessor)->createOrder(
                $input['amount'],
                $input['currency']
            );

            jsonResponse([
                'success' => true,
                'order_id' => $order->id,
                'amount' => $input['amount'] * 100,
                'currency' => $input['currency'],
                'key' => $_ENV['RAZORPAY_KEY_ID']
            ]);

        } catch (Exception $e) {
            (new PaymentProcessor)->logError('Order Creation', $e->getMessage());

            jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
