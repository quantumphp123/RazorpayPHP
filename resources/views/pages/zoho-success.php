<?php
$pageTitle = "Zoho Payment Successful";
include VIEW_PATH . 'layouts/layout.php';
?>

<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-md mx-auto p-5 font-sans">
        <!-- Success Header -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto mb-5 bg-green-500 rounded-full flex items-center justify-center">
                <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-semibold text-gray-800 mb-2">Payment Successful</h1>
            <p class="text-gray-600">Your Zoho invoice has been paid successfully.</p>
        </div>

        <!-- Transaction Info Card -->
        <div class="mb-6">
            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                <div class="flex justify-between items-center py-3 border-b border-gray-100">
                    <span class="text-gray-600 font-medium text-sm">Invoice ID</span>
                    <span class="text-gray-800 font-semibold text-sm"><?php echo htmlspecialchars($data['transaction']['invoice_id'] ?? $data['transaction']['zoho_invoice_id'] ?? ''); ?></span>
                </div>
                <div class="flex justify-between items-center py-3 border-b border-gray-100">
                    <span class="text-gray-600 font-medium text-sm">Transaction ID</span>
                    <span class="text-gray-800 font-semibold text-sm"><?php echo htmlspecialchars($data['transaction']['payment_id'] ?? ''); ?></span>
                </div>
                <div class="flex justify-between items-center py-3">
                    <span class="text-gray-600 font-medium text-sm">Transaction Date</span>
                    <span class="text-gray-800 font-semibold text-sm"><?php echo isset($data['transaction']['created_at']) ? date('d M Y, h:i A', strtotime($data['transaction']['created_at'])) : ''; ?></span>
                </div>
            </div>
        </div>

        <!-- Payment Details Card -->
        <div class="mb-6">
            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Payment Details</h3>

                <div class="text-center my-6 p-5 bg-green-50 rounded-lg border border-green-200">
                    <div class="text-3xl font-bold text-green-600 leading-tight">
                        <?php echo htmlspecialchars(($data['transaction']['currency_type'] ?? '') . ' ' . ($data['transaction']['amount'] ?? '')); ?>
                    </div>
                    <div class="text-sm text-gray-600 mt-1">Amount Paid</div>
                </div>

                <div class="space-y-0">
                    <div class="flex justify-between items-center py-3 border-b border-gray-100">
                        <span class="text-gray-600 font-medium text-sm">Payment Method</span>
                        <span class="text-gray-800 font-semibold text-sm">
                            <?php echo !empty($data['transaction']['payment_method']) ? htmlspecialchars(ucwords($data['transaction']['payment_method'])) : 'Zoho'; ?>
                            <?php if (!empty($data['transaction']['card_network'])): ?>
                                <span class="ml-2 px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs"><?php echo htmlspecialchars($data['transaction']['card_network']); ?></span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if (!empty($data['transaction']['transaction_fee']) && $data['transaction']['transaction_fee'] > 0): ?>
                        <div class="flex justify-between items-center py-3 border-b border-gray-100">
                            <span class="text-gray-600 font-medium text-sm">Transaction Fee</span>
                            <span class="text-gray-800 font-semibold text-sm">
                                <?php echo htmlspecialchars($data['transaction']['transaction_fee'] . ' ' . ($data['transaction']['currency'] ?? '')); ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="flex justify-between items-center py-3">
                        <span class="text-gray-600 font-medium text-sm">Status</span>
                        <span class="text-gray-800 font-semibold text-sm">
                            <span class="inline-block px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                                <?php echo htmlspecialchars(ucfirst($data['transaction']['status'] ?? 'Paid')); ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 mt-8">
            <button onclick="window.print()" class="flex items-center justify-center px-5 py-3 bg-white text-blue-600 border border-blue-600 rounded-lg font-semibold text-sm hover:bg-blue-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                    </path>
                </svg>
                Print Receipt
            </button>

            <a href="/download-receipt/pdf/<?php echo urlencode($data['transaction']['invoice_id'] ?? $data['transaction']['zoho_invoice_id'] ?? ''); ?>"
                class="flex items-center justify-center px-5 py-3 bg-white text-blue-600 border border-blue-600 rounded-lg font-semibold text-sm hover:bg-blue-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                Download Receipt
            </a>

            <a href="/" class="flex items-center justify-center px-5 py-3 bg-blue-600 text-white rounded-lg font-semibold text-sm hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                    </path>
                </svg>
                Return Home
            </a>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style media="print">
    .min-h-screen {
        min-height: auto !important;
    }

    .shadow-sm {
        box-shadow: none !important;
    }

    button,
    .print-hide {
        display: none !important;
    }
</style>