<?php

use App\Controllers\OrderController;
use App\Controllers\PagesController;
use App\Controllers\PaymentController;
use App\Controllers\ReceiptController;
use App\Controllers\InvoiceController;



// Return a closure that registers the routes
return function ($router) {
    $router->get('/', [PagesController::class, 'index']);
    // $router->get('/', [PagesController::class, 'index']);

    $router->post('/create-order', [OrderController::class, 'create']);
    $router->post('/verify-payment', [PaymentController::class, 'verifyPayment']);
    $router->post('/log-payment-event', [PaymentController::class, 'logPaymentEvent']);
    $router->get('/success', [PaymentController::class, 'success']);
    $router->get('/error', [PaymentController::class, 'error']);
    $router->get('/pay', [InvoiceController::class, 'showInvoice']);
    $router->get('/invoice/pay', [InvoiceController::class,'showPayPalPayment']);


    $router->post('/invoice/paypal-success', [InvoiceController::class,'handlePayPalSuccess']);
    
    $router->get('/invoice/razorpay-pay', [InvoiceController::class,'showRazorpayPayment']);
    $router->post('/invoice/razorpay-success', [InvoiceController::class,'handleRazorpaySuccess']);

    $router->get('/zoho-success', [InvoiceController::class, 'zohoSuccess']);

    $router->get('/policy', [PagesController::class, 'policy']);
    $router->get('/download-receipt/html/{payment_id}', [ReceiptController::class, 'downloadHTML']);
    $router->get('/download-receipt/pdf/{payment_id}', [ReceiptController::class, 'downloadPDF']);
};
