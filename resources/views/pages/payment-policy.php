<?php
$pageTitle = "Payments | Quantum IT Innovation";
include VIEW_PATH . 'layouts/layout.php';
?>

<div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6 mt-6">
    <h2 class="text-xl font-bold mb-4 text-center">Payment Policy</h2>
    <p class="text-sm text-gray-700 mb-4">
        By proceeding with the payment, you agree to the following terms and conditions. Please read this policy
        carefully before making your payment.
    </p>

    <h3 class="text-lg font-semibold text-gray-900 mb-2">1. Payment Process</h3>
    <p class="text-sm text-gray-700 mb-4">
        Payments are processed securely through Razorpay, a trusted and secure payment gateway. We accept various
        payment methods including credit cards, debit cards, and wallets.
    </p>

    <h3 class="text-lg font-semibold text-gray-900 mb-2">2. Currency and Amount</h3>
    <p class="text-sm text-gray-700 mb-4">
        The amount you are paying will be charged in the currency selected during checkout. Please ensure that the
        amount is correct before submitting your payment.
    </p>

    <h3 class="text-lg font-semibold text-gray-900 mb-2">3. Personal Information</h3>
    <p class="text-sm text-gray-700 mb-4">
        By submitting this form, you agree to provide accurate personal and billing information, including your name,
        email address, billing address, and payment details.
    </p>

    <h3 class="text-lg font-semibold text-gray-900 mb-2">4. Refund Policy</h3>
    <p class="text-sm text-gray-700 mb-4">
        All payments made are non-refundable unless specified otherwise by the terms of service or applicable law. If
        you have any issues with your payment, please contact customer support for assistance.
    </p>

    <h3 class="text-lg font-semibold text-gray-900 mb-2">5. Privacy and Data Security</h3>
    <p class="text-sm text-gray-700 mb-4">
        We take the privacy and security of your personal data seriously. Your information will be encrypted and stored
        securely. We do not share your personal information with third parties, except for payment processing via
        Razorpay.
    </p>

    <h3 class="text-lg font-semibold text-gray-900 mb-2">6. Save Address for Future Use</h3>
    <p class="text-sm text-gray-700 mb-4">
        If you choose to save your address for future use, your address will be stored in a secure local storage on your
        browser. This allows for faster checkouts in the future. You can opt-out at any time.
    </p>

    <h3 class="text-lg font-semibold text-gray-900 mb-2">7. Contact Us</h3>
    <p class="text-sm text-gray-700 mb-4">
        If you have any questions or concerns regarding this payment policy, feel free to reach out to our customer
        support team at <a href="mailto:support@yourcompany.com" class="text-blue-600">support@yourcompany.com</a>.
    </p>

    <!-- <div class="mt-4 text-center">
        <button class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
            <a href="javascript:void(0)" onclick="document.getElementById('payment-form').submit()">Agree and Proceed to
                Payment</a>
        </button>
    </div> -->
</div>

<?php include VIEW_PATH . 'layouts/footer.php'; ?>
