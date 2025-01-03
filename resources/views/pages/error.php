<?php
include VIEW_PATH . 'layouts/layout.php';
?>

<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">
        <!-- Header with gradient -->
        <div class="bg-gradient-to-r from-red-300 to-red-700 p-6">
            <div class="flex justify-center mt-2">
                <div class="flex justify-center items-center w-24 h-24 bg-gray-100 rounded-full animate-bounce">
                    <div class="relative w-20 h-20 bg-white text-red-400 flex justify-center items-center rounded-full shadow-lg border-red-600 border-2">
                        <!-- Credit Card Icon -->
                        <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h.01M11 15h2M7 15a2 2 0 100-4 2 2 0 000 4z M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z" />
                        </svg>
                        <!-- X Mark Overlay -->
                        <div class="absolute -top-2 -right-2 bg-red-600 rounded-full p-1">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <h1 class="text-2xl font-bold text-white text-center mt-2 animate-fade-in">
                Payment Failed
            </h1>
        </div>

        <div class="p-8">
            <div class="space-y-6">
                <!-- Error Details -->
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 animate-slide-up">
                    <div class="grid grid-cols-1 gap-4">
                        <!-- Error Code -->
                        <div class="animate-fade-in text-center">
                            <p class="text-3xl font-bold text-red-600 mb-2">
                                <?php echo !empty($error_code) ? htmlspecialchars($error_code) : 'Payment Error'; ?>
                            </p>
                            <p class="text-lg text-gray-700">
                                <?php echo !empty($error_description) ? htmlspecialchars($error_description) : 'Your payment could not be processed.'; ?>
                            </p>
                        </div>

                        <!-- Important Notice -->
                        <div class="mt-4 p-4 bg-yellow-50 border-l-4 border-yellow-400 animate-fade-in">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Important Notice</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>If you're unsure whether your payment went through or if you see a charge on your account, please:</p>
                                        <ul class="list-disc ml-5 mt-2">
                                            <li>Check your email for a payment confirmation</li>
                                            <li>Wait a few minutes and check your bank statement</li>
                                            <li>Do not attempt to pay again immediately</li>
                                            <li>Contact our support team with your order reference for assistance</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transaction Details -->
                        <?php if (!empty($payment_details)): ?>
                        <div class="mt-6 bg-white rounded-lg border border-gray-200 p-4 animate-fade-in delay-300">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Transaction Details</h3>
                            <div class="space-y-3 text-sm">
                                <?php if (!empty($payment_details['order_id'])): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Order ID:</span>
                                    <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($payment_details['order_id']); ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($payment_details['amount'])): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Amount:</span>
                                    <span class="text-gray-900 font-medium">
                                        <?php echo htmlspecialchars($payment_details['currency'] . ' ' . number_format($payment_details['amount'], 2)); ?>
                                    </span>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($payment_details['timestamp'])): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Time:</span>
                                    <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($payment_details['timestamp']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-center space-x-4 animate-slide-up delay-500">
                    <button onclick="window.history.back()" class="inline-flex items-center px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-300 hover:scale-105">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Try Again
                    </button>
                    <a href="/contact" class="inline-flex items-center px-4 py-2 text-sm text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-300 hover:scale-105">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Contact Support
                    </a>
                </div>

                <!-- Support Information -->
                <div class="text-center animate-fade-in delay-700">
                    <p class="text-sm text-gray-500">
                        Need assistance? Contact our support team at
                        <a href="mailto:<?php echo htmlspecialchars($GLOBALS['config']->get('app')['support']); ?>" class="text-red-600 hover:text-red-700 ml-1">
                            <?php echo htmlspecialchars($GLOBALS['config']->get('app')['support']); ?>
                        </a>
                    </p>
                    <p class="text-sm text-gray-500 mt-2">
                        Please include your Order ID when contacting support for faster assistance.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include VIEW_PATH . 'layouts/footer.php'; ?>
