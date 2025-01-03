<?php

$pageTitle = "Payment Successful";
include VIEW_PATH . 'layouts/layout.php';
?>

<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">
        <!-- Success Animation -->
        <div class="bg-gradient-to-r from-green-300 to-green-700 p-6">
            <div class="flex justify-center">
                <div class="rounded-full bg-white p-3">
                    <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
            <h1 class="text-2xl font-bold text-white text-center mt-4">Transaction Successful</h1>
        </div>

        <!-- Transaction Details -->
        <div class="p-8">
            <div class="space-y-6">
                <!-- Status Card -->
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Transaction ID</p>
                            <p class="font-mono text-sm"><?php echo htmlspecialchars($input['payment_id']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Status</p>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <?php echo htmlspecialchars($transactionData['transaction']['status']); ?>
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Amount Paid</p>
                            <p class="font-semibold">
                                <?php echo htmlspecialchars($transactionData['transaction']['amount']); ?>
                                <?php echo htmlspecialchars($transactionData['transaction']['currency_type']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Payment Method</p>
                            <p class="font-semibold">
                                <?php echo htmlspecialchars($transactionData['transaction']['payment_method']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-center space-x-4">
                    <button onclick="window.print()"
                        class="inline-flex items-center px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                            </path>
                        </svg>
                        Print
                    </button>
                    <a href="download-receipt/pdf/<?php echo urlencode($input['payment_id']); ?>"
                        class="inline-flex items-center px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        Download Receipt
                    </a>
                    <a href="https://quantumitinnovation.com"
                        class="inline-flex items-center px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 hover:scale-105 shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Home
                    </a>
                </div>

                <!-- Return Link -->
                <div class="text-center">
                    <a href="/" class="text-sm text-gray-500 hover:text-gray-700">
                        Return to Payment Page
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style media="print">
    .min-h-screen {
        min-height: auto !important;
    }

    .shadow-lg {
        box-shadow: none !important;
    }

    button,
    .print-hide {
        display: none !important;
    }
</style>

<?php include VIEW_PATH . 'layouts/layout.php'; ?>
