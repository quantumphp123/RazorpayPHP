<?php

return [
    'smtp' => [
        'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
        'port' => $_ENV['MAIL_PORT'] ?? 587,
        'username' => $_ENV['MAIL_USERNAME'] ?? 'your-email@example.com',
        'password' => $_ENV['MAIL_PASSWORD'] ?? 'your-app-specific-password'
    ],
    'from' => [
        'email' => $_ENV['MAIL_FROM'] ?? 'noreply@yourcompany.com',
        'name' => $_ENV['COMPANY_NAME'] ?? 'Your Company Name'
    ],
    'subject_prefix' => '[' . $_ENV['COMPANY_NAME'] ?? 'Payment System' . ']'
];
