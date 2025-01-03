// When the payment page is loaded, auto-fill the address if available in localStorage
window.addEventListener('load', function () {
    const savedAddress = localStorage.getItem('userAddress');

    if (savedAddress) {
        const address = JSON.parse(savedAddress);

        document.getElementById('address1').value = address.address1 || '';
        document.getElementById('address2').value = address.address2 || '';
        document.getElementById('city').value = address.city || '';
        document.getElementById('state').value = address.state || '';
        document.getElementById('zipcode').value = address.zipcode || '';
        document.getElementById('country').value = address.country || '';
    }
});

$(document).ready(function () {
    // Detect user's region and set currency
    fetch('https://ipapi.co/json/')
        .then(response => response.json())
        .then(data => {
            const currencyMap = {
                'IN': 'INR',
                'US': 'USD',
                'GB': 'GBP',
                'EU': 'EUR',
                'AU': 'AUD',
                'CA': 'CAD',
                'SG': 'SGD',
            };
            const currency = currencyMap[data.country] || 'INR';
            $('#currency').val(currency);
        });

    $('#payment-form').on('submit', function (e) {
        e.preventDefault();

        // Disable submit button to prevent double submission
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true);

        if (!validateInput()) {
            submitButton.prop('disabled', false);
            return;
        }

        // Save user address in localStorage for future
        saveAddress();

        // Show loading state
        const loadingOverlay = showLoadingOverlay();

        $.ajax({
            url: '/create-order',
            method: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                console.log(response);
                if (response.error) {
                    console.log('Error in create_order');
                    handleError(response.error);
                    submitButton.prop('disabled', false);
                    hideLoadingOverlay(loadingOverlay);
                    return;
                }
                console.log('No error in create_order');
                const options = {
                    key: response.key,
                    amount: response.amount,
                    currency: response.currency,
                    order_id: response.order_id,
                    name: 'Quantum IT Innovation',
                    description: 'IT Services Payment',
                    handler: function (response) {
                        console.log("handling verify payment");
                        verifyPayment(response, submitButton, loadingOverlay);
                    },
                    modal: {
                        ondismiss: function () {
                            submitButton.prop('disabled', false);
                            hideLoadingOverlay(loadingOverlay);
                            logPaymentEvent('modal_closed', {
                                order_id: response.order_id
                            });
                        }
                    },
                    prefill: {
                        name: $('#name').val(),
                        email: $('#email').val(),
                    },
                    notes: {
                        address: $('#address1').val() + $('#city').val() + $('#state').val() + $('#country').val() + $('#zipcode').val(),
                        currency: $('#currency').val()
                    },
                    theme: {
                        color: '#2563EB'
                    }
                };

                const rzp = new Razorpay(options);

                rzp.on('payment.failed', function (response) {
                    handlePaymentFailure(response);
                    submitButton.prop('disabled', false);
                    hideLoadingOverlay(loadingOverlay);
                });

                rzp.open();
            },
            error: function (xhr, status, error) {
                handleError('Error creating order: ' + error);
                submitButton.prop('disabled', false);
                hideLoadingOverlay(loadingOverlay);
            }
        });
    });

    function verifyPayment(response, submitButton, loadingOverlay) {
        const formData = {
            payment_id: response.razorpay_payment_id,
            order_id: response.razorpay_order_id,
            signature: response.razorpay_signature,
            name: $('#name').val(),
            email: $('#email').val(),
            address1: $('#address1').val(),
            address2: $('#address2').val(),
            city: $('#city').val(),
            state: $('#state').val(),
            zipcode: $('#zipcode').val(),
            country: $('#country').val(),
            amount: $('#amount').val(),
            currency: $('#currency').val(),
            // Include payment method details from Razorpay response
            payment_method: response.method,
            card_network: response.card ? response.card.network : null,
            card_last4: response.card ? response.card.last4 : null
        };

        $.ajax({
            url: '/verify-payment',
            method: 'POST',
            data: formData,
            success: function (data) {
                if (data.success) {
                    logPaymentEvent('payment_success', {
                        transaction_id: response.transaction_id,
                        payment_id: response.razorpay_payment_id,
                        amount: formData.amount,
                        currency: formData.currency
                    });
                    window.location.href = '/success?payment_id=' +
                        response.razorpay_payment_id;
                } else {
                    handleError('Payment verification failed: ' + (data.error || 'Unknown error'));
                    submitButton.prop('disabled', false);
                }
            },
            error: function (xhr, status, error) {
                handleError('Error verifying payment: ' + error);
                submitButton.prop('disabled', false);
            },
            complete: function () {
                hideLoadingOverlay(loadingOverlay);
            }
        });
    }

    function validateInput() {
        // Clear previous errors
        $('.error-message').remove();
        $('.border-red-500').removeClass('border-red-500');

        const requiredFields = document.getElementById('payment-form').querySelectorAll('[required]');
        let isValid = true;
        let errors = [];

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('border-red-500');
                $(field).after(`<div class="error-message text-red-500 text-sm mt-1">This field is required</div>`);
                errors.push(`${field.name} is required`);
            } else {
                // Additional validation based on field type
                if (field.type === 'email' && !isValidEmail(field.value)) {
                    isValid = false;
                    field.classList.add('border-red-500');
                    $(field).after(`<div class="error-message text-red-500 text-sm mt-1">Invalid email format</div>`);
                    errors.push('Invalid email format');
                }
                if (field.name === 'amount' && (!isValidAmount(field.value))) {
                    isValid = false;
                    field.classList.add('border-red-500');
                    $(field).after(`<div class="error-message text-red-500 text-sm mt-1">Invalid amount</div>`);
                    errors.push('Invalid amount');
                }
            }
        });

        return isValid;
    }

    // Helper functions
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function isValidAmount(amount) {
        return !isNaN(amount) && parseFloat(amount) > 0;
    }

    function showLoadingOverlay() {
        const overlay = $('<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"><div class="bg-white p-4 rounded-lg">Processing payment...</div></div>');
        $('body').append(overlay);
        return overlay;
    }

    function hideLoadingOverlay(overlay) {
        overlay.remove();
    }

    function handleError(message) {
        const errorDiv = $('<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"></div>').text(message);
        $('#payment-form').prepend(errorDiv);
        setTimeout(() => errorDiv.fadeOut('slow', function () { $(this).remove(); }), 20000);

        logPaymentEvent('payment_error', {
            error_message: message
        });
    }

    function handlePaymentFailure(response) {
        console.log('razorpay options error');
        const errorMessage = `Payment failed: ${response.error.description} (Code: ${response.error.code})`;
        handleError(errorMessage);

        logPaymentEvent('payment_failed', {
            error_code: response.error.code,
            error_message: response.error.description,
            order_id: response.error.metadata.order_id
        });
    }

    function logPaymentEvent(event_type, data) {
        $.ajax({
            url: '/log-payment-event',
            method: 'POST',
            data: {
                event_type: event_type,
                ...data
            }
        });
    }

    function saveAddress() {
        const saveAddressCheckbox = document.getElementById('save-address'); // Save address checkbox

        const address = {
            address1: document.getElementById('address1').value,
            address2: document.getElementById('address2').value,
            city: document.getElementById('city').value,
            state: document.getElementById('state').value,
            zipcode: document.getElementById('zipcode').value,
            country: document.getElementById('country').value
        };

        // Save to localStorage
        if (saveAddressCheckbox.checked) {
            const existingAddress = localStorage.getItem('userAddress');

            // Compare the current address with the existing one
            if (existingAddress) {
                const parsedExistingAddress = JSON.parse(existingAddress);

                // Only save if the address is different
                if (JSON.stringify(parsedExistingAddress) !== JSON.stringify(address)) {
                    localStorage.setItem('userAddress', JSON.stringify(address));
                    console.log('Address saved locally.');
                }
            } else {
                // If no address is saved, store it
                localStorage.setItem('userAddress', JSON.stringify(address));
                console.log('Address saved locally.');
            }
        }

    }
});
