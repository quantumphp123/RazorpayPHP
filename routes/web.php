<?php

use App\Controllers\OrderController;
use App\Controllers\PagesController;
use App\Controllers\PaymentController;
use App\Controllers\ReceiptController;



// Return a closure that registers the routes
return function ($router) {
    $router->get('/', [PagesController::class, 'index']);
    // $router->get('/', [PagesController::class, 'index']);

    $router->post('/create-order', [OrderController::class, 'create']);
    $router->post('/verify-payment', [PaymentController::class, 'verifyPayment']);
    $router->post('/log-payment-event', [PaymentController::class, 'logPaymentEvent']);
    $router->get('/success', [PaymentController::class, 'success']);

    $router->get('/policy', [PagesController::class, 'policy']);
    $router->get('/download-receipt/html/{payment_id}', [ReceiptController::class, 'downloadHTML']);
    $router->get('/download-receipt/pdf/{payment_id}', [ReceiptController::class, 'downloadPDF']);
};
