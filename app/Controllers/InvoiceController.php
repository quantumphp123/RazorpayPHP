<?php
namespace App\Controllers;

use App\Core\Database;
use App\Services\ZohoService;
use App\Controllers\Controller;

class InvoiceController extends Controller
{
    public function showInvoice()
    {
        global $config;

        // 1. Get invoice number from URL
        $invoiceNumber = $_GET['invoice_id'] ?? null;
        if (!$invoiceNumber) {
            echo "Invoice number is required.";
            return;
        }

        // 2. Try to fetch from DB first
        $db = $GLOBALS['db'];
        $row = $db->query("SELECT data FROM invoices WHERE invoice_number = ?", [$invoiceNumber])->find();
        $invoice = $row ? json_decode($row['data'], true) : null;

        // 3. If not found in DB, fetch from Zoho
        if (!$invoice) {
            $zoho = new ZohoService($GLOBALS['config']);
            $invoice = $zoho->getInvoiceByNumber($invoiceNumber);
            if (!$invoice) {
                echo "Invoice not found or error fetching from Zoho.";
                return;
            }
            // Store in DB for future
            $this->storeInvoiceInDatabase($invoice);
        }

        // 4. Show success message if paid
        if (isset($_GET['paid']) && $_GET['paid'] == 1) {
            echo '<div style="max-width:700px;margin:2rem auto 0;padding:1em 2em;background:#eaffea;border:1px solid #b2dfdb;color:#388e3c;font-size:1.2em;border-radius:8px;text-align:center;">';
            echo 'Payment Successful! Thank you for your payment.';
            echo '</div>';
        }

        // 5. Show in card
        $this->renderInvoiceCard($invoice);
    }

    public function showPayPalPayment()
    {
        $invoiceNumber = $_GET['invoice_id'] ?? null;
        if (!$invoiceNumber) {
            echo "Invoice number is required.";
            return;
        }

        // Fetch invoice from DB (or Zoho if not found)
        $db = $GLOBALS['db'];
        $row = $db->query("SELECT data FROM invoices WHERE invoice_number = ?", [$invoiceNumber])->find();
        $invoice = $row ? json_decode($row['data'], true) : null;

        if (!$invoice) {
            echo "Invoice not found.";
            return;
        }

        $this->renderPayPalCard($invoice);
    }

    public function handlePayPalSuccess()
    {
        // Get JSON body
        $input = json_decode(file_get_contents('php://input'), true);
        $invoiceId = $input['invoice_id'] ?? null;
        $orderId = $input['order_id'] ?? null;
        $payerId = $input['payer_id'] ?? null;
        $details = $input['details'] ?? null;

        if (!$invoiceId || !$orderId || !$payerId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing data']);
            return;
        }

        $zohoService = new \App\Services\ZohoService($GLOBALS['config']);
        $invoice = $zohoService->fetchInvoiceById($invoiceId);
        if (!$invoice) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Invoice not found']);
            return;
        }
        // Mark invoice as paid in Zoho Books and store Zoho response in its own table if needed
        $zohoApiResponse = $zohoService->recordPaymentForInvoice($invoice, $invoice['total']);

        // Use service to update invoice and insert PayPal payment, passing Zoho response
        $zohoService->updateInvoiceAndInsertPayPalPayment($invoice, $invoiceId, $orderId, $payerId, $details, $zohoApiResponse);

        // Return JSON with redirect URL for frontend to handle
        echo json_encode([
            'success' => true,
            'redirect' => '/zoho-success?invoice_id=' . urlencode($invoiceId)
        ]);
        exit;
    }

    public function showRazorpayPayment()
    {
        $invoiceNumber = $_GET['invoice_id'] ?? null;
        if (!$invoiceNumber) {
            echo "Invoice number is required.";
            return;
        }

        // Fetch invoice from DB (or Zoho if not found)
        $db = $GLOBALS['db'];
        $row = $db->query("SELECT data FROM invoices WHERE invoice_number = ?", [$invoiceNumber])->find();
        $invoice = $row ? json_decode($row['data'], true) : null;

        if (!$invoice) {
            echo "Invoice not found.";
            return;
        }

        $this->renderRazorpayCard($invoice);
    }

    public function handleRazorpaySuccess()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $this->logRazorpayDebug(['step' => 'incoming_data', 'data' => $input]);
        $invoiceId = $input['invoice_id'] ?? null;
        $razorpayPaymentId = $input['razorpay_payment_id'] ?? null;
        $razorpayOrderId = $input['razorpay_order_id'] ?? null;
        $razorpaySignature = $input['razorpay_signature'] ?? null;

        if (!$invoiceId || !$razorpayPaymentId || !$razorpaySignature) {
            $this->logRazorpayDebug(['step' => 'missing_data', 'invoiceId' => $invoiceId, 'razorpayPaymentId' => $razorpayPaymentId, 'razorpaySignature' => $razorpaySignature]);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing data']);
            return;
        }

        try {
            $this->logRazorpayDebug(['step' => 'before_zoho', 'invoiceId' => $invoiceId]);
            $zohoService = new \App\Services\ZohoService($GLOBALS['config']);
            $invoice = $zohoService->fetchInvoiceById($invoiceId);
            $this->logRazorpayDebug(['step' => 'zoho_invoice', 'invoice' => $invoice]);

            if (!$invoice) {
                $this->logRazorpayDebug(['step' => 'invoice_not_found', 'invoiceId' => $invoiceId]);
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Invoice not found']);
                return;
            }
            $zohoApiResponse = $zohoService->recordPaymentForInvoice($invoice, $invoice['total']);
            $this->logRazorpayDebug(['step' => 'zoho_payment_response', 'response' => $zohoApiResponse]);

            $zohoService->updateInvoiceAndInsertRazorpayPayment($invoice, $invoiceId, $razorpayPaymentId, $razorpayOrderId, $razorpaySignature, $zohoApiResponse);
            $this->logRazorpayDebug(['step' => 'payment_success', 'invoiceId' => $invoiceId]);
            echo json_encode([
                'success' => true,
                'redirect' => '/zoho-success?invoice_id=' . urlencode($invoiceId)
            ]);
        } catch (\Exception $e) {
            $this->logRazorpayDebug(['step' => 'exception', 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
        exit;
    }

    public function zohoSuccess()
    {
        $invoiceId = $_GET['invoice_id'] ?? null;
        if (!$invoiceId) {
            echo 'Invoice ID is required.';
            return;
        }
        $zohoService = new ZohoService($GLOBALS['config']);
        $invoice = $zohoService->fetchInvoiceById($invoiceId);
        if (!$invoice) {
            echo 'Invoice not found.';
            return;
        }
        // Record payment in Zoho Books if invoice is paid locally
        if ($invoice['status'] === 'paid') {
            // Optionally, you could check Zoho Books status first to avoid duplicate API calls
            $zohoService->recordPaymentForInvoice($invoice, $invoice['total']);
        }
        $transactionData = $zohoService->prepareTransactionData($invoice);
        $this->view('pages.zoho-success', ['transaction' => $transactionData]);
    }

    private function storeInvoiceInDatabase($invoice)
    {
        // Load DB from global
        $db = $GLOBALS['db'];
        $db->query("INSERT INTO invoices (invoice_id, invoice_number, data) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data)", [
            $invoice['invoice_id'],
            $invoice['invoice_number'],
            json_encode($invoice)
        ]);
    }

    private function renderInvoiceCard($invoice)
    {
        ?>
        <div class="invoice-card-pro">
            <div class="invoice-header-pro">
                <div>
                    <h2>Invoice <span>#<?= htmlspecialchars($invoice['invoice_number']) ?></span></h2>
                    <div class="status-pro <?= htmlspecialchars($invoice['status']) ?>">
                        <?= ucfirst(htmlspecialchars($invoice['status'])) ?>
                    </div>
                </div>
                <!-- Optional: Add your logo here -->
                <!-- <img src="/path/to/logo.png" class="logo-pro" alt="Company Logo"> -->
            </div>
            <div class="invoice-meta-pro">
                <div>
                    <strong>Date:</strong> <?= htmlspecialchars($invoice['date']) ?><br>
                    <strong>Due:</strong> <?= htmlspecialchars($invoice['due_date']) ?>
                </div>
                <div>
                    <strong>Customer:</strong> <?= htmlspecialchars($invoice['customer_name']) ?><br>
                    <strong>Email:</strong> <?= htmlspecialchars($invoice['email']) ?>
                </div>
            </div>
            <div class="amount-summary-pro">
                <div>
                    <span>Total</span>
                    <span><?= htmlspecialchars($invoice['total']) ?> <?= htmlspecialchars($invoice['currency_code']) ?></span>
                </div>
                <div>
                    <span>Paid</span>
                    <span><?= htmlspecialchars($invoice['payment_made'] ?? '0') ?></span>
                </div>
                <div>
                    <span>Balance</span>
                    <span><?= htmlspecialchars($invoice['balance']) ?></span>
                </div>
            </div>
            <h3>Line Items</h3>
            <table class="line-items-pro">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($invoice['line_items'] as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= htmlspecialchars($item['description']) ?></td>
                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                        <td><?= htmlspecialchars($item['rate']) ?> <?= htmlspecialchars($invoice['currency_code']) ?></td>
                        <td><?= htmlspecialchars($item['quantity'] * $item['rate']) ?> <?= htmlspecialchars($invoice['currency_code']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="address-section-pro">
                <?php if (!empty($invoice['billing_address']['address'])): ?>
                <div>
                    <h4>Billing Address</h4>
                    <address>
                        <?= nl2br(htmlspecialchars($invoice['billing_address']['address'])) ?><br>
                        <?= htmlspecialchars($invoice['billing_address']['city'] ?? '') ?><?= !empty($invoice['billing_address']['city']) ? ',' : '' ?>
                        <?= htmlspecialchars($invoice['billing_address']['state'] ?? '') ?><?= !empty($invoice['billing_address']['state']) ? ',' : '' ?>
                        <?= htmlspecialchars($invoice['billing_address']['zip'] ?? '') ?><br>
                        <?= htmlspecialchars($invoice['billing_address']['country'] ?? '') ?>
                    </address>
                </div>
                <?php endif; ?>
                <?php if (!empty($invoice['shipping_address']['address'])): ?>
                <div>
                    <h4>Shipping Address</h4>
                    <address>
                        <?= nl2br(htmlspecialchars($invoice['shipping_address']['address'])) ?><br>
                        <?= htmlspecialchars($invoice['shipping_address']['city'] ?? '') ?><?= !empty($invoice['shipping_address']['city']) ? ',' : '' ?>
                        <?= htmlspecialchars($invoice['shipping_address']['state'] ?? '') ?><?= !empty($invoice['shipping_address']['state']) ? ',' : '' ?>
                        <?= htmlspecialchars($invoice['shipping_address']['zip'] ?? '') ?><br>
                        <?= htmlspecialchars($invoice['shipping_address']['country'] ?? '') ?>
                    </address>
                </div>
                <?php endif; ?>
            </div>
            <div style="display: flex; gap: 1em; margin-top: 2em; justify-content: center;">
                <!-- <a href="/invoice/pay?invoice_id=<?= urlencode($invoice['invoice_number']) ?>" class="pay-btn-pro" style="background: linear-gradient(90deg, #0070ba 60%, #003087 100%);">Pay with PayPal</a> -->
                <a href="/invoice/razorpay-pay?invoice_id=<?= urlencode($invoice['invoice_number']) ?>" class="pay-btn-pro" style="background: linear-gradient(90deg, #f37254 60%, #fa8c68 100%);">Pay with Razorpay</a>
            </div>
        </div>
        <style>
        .invoice-card-pro {
            max-width: 700px;
            margin: 2rem auto;
            padding: 2.5rem 2rem;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(60, 60, 120, 0.10);
            background: linear-gradient(135deg, #f8fafc 80%, #e0e7ef 100%);
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            color: #222;
            transition: box-shadow 0.2s;
        }
        .invoice-card-pro:hover {
            box-shadow: 0 12px 40px rgba(60, 60, 120, 0.18);
        }
        .invoice-header-pro {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e3e8ee;
            padding-bottom: 1.2rem;
            margin-bottom: 1.2rem;
        }
        .invoice-header-pro h2 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            color: #1a237e;
        }
        .invoice-header-pro .status-pro {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.3em 1.2em;
            border-radius: 20px;
            font-size: 1em;
            background: #e3e8ee;
            color: #333;
            font-weight: 500;
            letter-spacing: 0.03em;
        }
        .invoice-header-pro .status-pro.overdue { background: #ffeaea; color: #d32f2f; }
        .invoice-header-pro .status-pro.paid { background: #eaffea; color: #388e3c; }
        .invoice-meta-pro {
            display: flex;
            justify-content: space-between;
            gap: 2em;
            margin-bottom: 1.2em;
            font-size: 1.1em;
        }
        .amount-summary-pro {
            display: flex;
            justify-content: flex-end;
            gap: 2em;
            margin-bottom: 1.5em;
            font-size: 1.15em;
        }
        .amount-summary-pro > div {
            background: #f1f5fb;
            border-radius: 8px;
            padding: 0.7em 1.2em;
            min-width: 110px;
            text-align: center;
            box-shadow: 0 1px 4px #e3e8ee;
        }
        .amount-summary-pro span:first-child {
            display: block;
            font-size: 0.95em;
            color: #888;
        }
        .amount-summary-pro span:last-child {
            font-size: 1.2em;
            font-weight: 600;
            color: #1a237e;
        }
        .line-items-pro {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5em 0;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 4px #e3e8ee;
        }
        .line-items-pro th, .line-items-pro td {
            border: 1px solid #e3e8ee;
            padding: 0.7em 0.6em;
            text-align: left;
        }
        .line-items-pro th {
            background: #f1f5fb;
            font-weight: 600;
            color: #1a237e;
        }
        .address-section-pro {
            display: flex;
            gap: 3em;
            margin-top: 1.5em;
            font-size: 1.05em;
        }
        .address-section-pro h4 {
            margin-bottom: 0.3em;
            color: #1a237e;
        }
        .pay-btn-pro {
            display: block;
            width: 100%;
            margin: 2.5em auto 0 auto;
            padding: 1.1em;
            background: linear-gradient(90deg, #0070ba 60%, #003087 100%);
            color: #fff;
            border: none;
            border-radius: 7px;
            font-size: 1.15em;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px #e3e8ee;
            transition: background 0.2s, box-shadow 0.2s;
            text-align: center;
            max-width: 320px;
        }
        .pay-btn-pro:hover {
            background: linear-gradient(90deg, #005fa3 60%, #001f4d 100%);
            box-shadow: 0 4px 16px #b3c6e0;
        }
        .logo-pro {
            height: 48px;
            margin-left: 1.5em;
        }
        @media (max-width: 700px) {
            .invoice-card-pro { padding: 1.2rem 0.5rem; }
            .invoice-meta-pro, .address-section-pro { flex-direction: column; gap: 0.5em; }
            .amount-summary-pro { flex-direction: column; gap: 0.5em; align-items: flex-end; }
        }
        </style>
        <?php
    }

    private function renderPayPalCard($invoice)
    {
        $isINR = (strtoupper($invoice['currency_code']) === 'INR');
        ?>
        <div class="paypal-card">
            <h2>Pay Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?></h2>
            <p>Amount Due: <strong><?= htmlspecialchars($invoice['total']) ?> <?= htmlspecialchars($invoice['currency_code']) ?></strong></p>
            <?php if ($isINR): ?>
                <div style="color: #d32f2f; font-weight: bold; margin: 2em 0;">PayPal is not available for INR payments. Please use another payment method.</div>
            <?php else: ?>
                <div id="paypal-button-container"></div>
                <script src="https://www.paypal.com/sdk/js?client-id=Af_ZElfXyykFheDA3DYOLmP0Js4Vv-OUyhkY4OJmsaO4RNbWMDTMn_TsM6PZuyturr3HomXpaTHDD8Ci&currency=<?= htmlspecialchars($invoice['currency_code']) ?>"></script>
                <script>
                paypal.Buttons({
                    style: {
                        label: 'pay', // Button will say "Pay Now"
                        color: 'blue',
                        shape: 'rect',
                        height: 45
                    },
                    createOrder: function(data, actions) {
                        return actions.order.create({
                            purchase_units: [{
                                amount: {
                                    value: '<?= htmlspecialchars($invoice['total']) ?>'
                                }
                            }]
                        });
                    },
                    onApprove: function(data, actions) {
                        return actions.order.capture().then(function(details) {
                            // Call backend to update payment status
                            fetch('/invoice/paypal-success', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({
                                    invoice_id: '<?= htmlspecialchars($invoice['invoice_id']) ?>',
                                    order_id: data.orderID,
                                    payer_id: data.payerID,
                                    details: details
                                })
                            }).then(res => res.json()).then(resp => {
                                if (resp.success && resp.redirect) {
                                    window.location.href = resp.redirect;
                                } else {
                                    alert('Payment update failed.');
                                }
                            });
                        });
                    }
                }).render('#paypal-button-container');
                </script>
            <?php endif; ?>
        </div>
        <style>
        .paypal-card {
            max-width: 500px;
            margin: 3rem auto;
            padding: 2rem 1.5rem;
            border-radius: 14px;
            box-shadow: 0 6px 24px rgba(60, 60, 120, 0.10);
            background: #f8fafc;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            color: #222;
            text-align: center;
        }
        .paypal-card h2 {
            color: #1a237e;
            margin-bottom: 1.2em;
        }
        .paypal-card p {
            font-size: 1.2em;
            margin-bottom: 2em;
        }
        </style>
        <?php
    }

    private function renderRazorpayCard($invoice)
    {
        ?>
        <div class="razorpay-card">
            <h2>Pay Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?></h2>
            <p>Amount Due: <strong><?= htmlspecialchars($invoice['total']) ?> <?= htmlspecialchars($invoice['currency_code']) ?></strong></p>
            <div id="razorpay-button-container"></div>
            <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
            <script>
            function renderRazorpayButton() {
                var button = document.createElement('button');
                button.innerHTML = '<span style="display:flex;align-items:center;justify-content:center;gap:0.5em;width:100%;"><img src="https://razorpay.com/favicon.png" alt="Razorpay" style="height:1.5em;vertical-align:middle;"> <span style="font-size:1.15em;font-weight:600;">Pay with <b style="font-family:sans-serif;letter-spacing:0.5px;">Razorpay</b></span></span>';
                button.className = 'pay-btn-pro';
                button.style.background = '#0070ba';
                button.style.color = '#fff';
                button.style.maxWidth = '100%';
                button.style.margin = '0 auto';
                button.style.display = 'block';
                button.style.borderRadius = '8px';
                button.style.fontWeight = '600';
                button.style.fontSize = '1.15em';
                button.style.boxShadow = '0 2px 8px #e3e8ee';
                button.style.border = 'none';
                button.style.padding = '1.1em';
                button.style.textAlign = 'center';
                button.style.width = '100%';
                button.onclick = function(e){
                    var options = {
                        "key": "rzp_live_E0MCMrm6gH6X9l",
                        "amount": "<?= intval($invoice['total'] * 100) ?>",
                        "currency": "<?= htmlspecialchars($invoice['currency_code']) ?>",
                        "name": "Quantum IT Innovation",
                        
                        "description": "Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?>",
                        "handler": function (response){
                            fetch('/invoice/razorpay-success', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({
                                    invoice_id: '<?= htmlspecialchars($invoice['invoice_id']) ?>',
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_order_id: response.razorpay_order_id,
                                    razorpay_signature: response.razorpay_signature
                                })
                            }).then(res => res.json()).then(resp => {
                                if (resp.success && resp.redirect) {
                                    window.location.href = resp.redirect;
                                } else {
                                    alert('Payment update failed.');
                                }
                            });
                        },
                        "theme": {
                            "color": "#3399cc"
                        }
                    };
                    var rzp1 = new Razorpay(options);
                    rzp1.open();
                    e.preventDefault();
                };
                document.getElementById('razorpay-button-container').appendChild(button);
            }
            renderRazorpayButton();
            </script>
        </div>
        <style>
        .razorpay-card {
            max-width: 500px;
            margin: 3rem auto;
            padding: 2rem 1.5rem;
            border-radius: 14px;
            box-shadow: 0 6px 24px rgba(60, 60, 120, 0.10);
            background: #f8fafc;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            color: #222;
            text-align: center;
        }
        .razorpay-card h2 {
            color: #1a237e;
            margin-bottom: 1.2em;
        }
        .razorpay-card p {
            font-size: 1.2em;
            margin-bottom: 2em;
        }
        </style>
        <?php
    }

    // Powerful logging for Razorpay debugging
    private function logRazorpayDebug($data) {
        $logFile = __DIR__ . '/../../../storage/logs/razorpay_debug.txt';
        $entry = "[" . date('Y-m-d H:i:s') . "] " . print_r($data, true) . "\n";
        file_put_contents($logFile, $entry, FILE_APPEND);
    }
} 