<?php
$pageTitle = "Zoho Payment Successful";
include VIEW_PATH . 'layouts/layout.php';
?>

<div class="mobile-payment-success">
    <!-- Success Header -->
    <div class="success-header">
        <div class="success-icon">
            <svg class="checkmark" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h1>Zoho Payment Successful</h1>
        <p>Your Zoho invoice has been paid successfully.</p>
    </div>

    <!-- Transaction Info Card -->
    <div class="info-section">
        <div class="info-card">
            <div class="info-item">
                <span class="info-label">Invoice ID</span>
                <span class="info-value"><?php echo htmlspecialchars($data['transaction']['invoice_id'] ?? $data['transaction']['zoho_invoice_id'] ?? ''); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Transaction ID</span>
                <span class="info-value"><?php echo htmlspecialchars($data['transaction']['payment_id'] ?? ''); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Transaction Date</span>
                <span class="info-value"><?php echo isset($data['transaction']['created_at']) ? date('d M Y, h:i A', strtotime($data['transaction']['created_at'])) : ''; ?></span>
            </div>
        </div>
    </div>

    <!-- Payment Details Card -->
    <div class="payment-section">
        <div class="payment-card">
            <h3 class="section-title">Payment Details</h3>

            <div class="amount-highlight">
                <div class="amount-value">
                    <?php echo htmlspecialchars(($data['transaction']['currency_type'] ?? '') . ' ' . ($data['transaction']['amount'] ?? '')); ?>
                </div>
                <div class="amount-label">Amount Paid</div>
            </div>

            <div class="payment-info">
                <div class="payment-item">
                    <span class="payment-label">Payment Method</span>
                    <span class="payment-value">
                        <?php echo !empty($data['transaction']['payment_method']) ? htmlspecialchars(ucwords($data['transaction']['payment_method'])) : 'Zoho'; ?>
                        <?php if (!empty($data['transaction']['card_network'])): ?>
                            <span class="card-network"><?php echo htmlspecialchars($data['transaction']['card_network']); ?></span>
                        <?php endif; ?>
                    </span>
                </div>

                <?php if (!empty($data['transaction']['transaction_fee']) && $data['transaction']['transaction_fee'] > 0): ?>
                    <div class="payment-item">
                        <span class="payment-label">Transaction Fee</span>
                        <span class="payment-value">
                            <?php echo htmlspecialchars($data['transaction']['transaction_fee'] . ' ' . ($data['transaction']['currency'] ?? '')); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="payment-item">
                    <span class="payment-label">Status</span>
                    <span class="payment-value">
                        <span class="status-badge">
                            <?php echo htmlspecialchars(ucfirst($data['transaction']['status'] ?? 'Paid')); ?>
                        </span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <button onclick="window.print()" class="btn btn-outline">
            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                </path>
            </svg>
            Print Receipt
        </button>

        <a href="/download-receipt/pdf/<?php echo urlencode($data['transaction']['invoice_id'] ?? $data['transaction']['zoho_invoice_id'] ?? ''); ?>"
            class="btn btn-outline">
            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            Download Receipt
        </a>

        <a href="/" class="btn btn-primary">
            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                </path>
            </svg>
            Return Home
        </a>
    </div>
</div>

<!-- Mobile-First Styles -->
<style>
    /* Copy all styles from success.php here for consistency */
    <?php include VIEW_PATH . 'pages/success.php'; ?>
</style> 