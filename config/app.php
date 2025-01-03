<?php
return [
    'environment' => $_ENV['APP_ENV'] ?? 'production',

    'name' => $_ENV['COMPANY_NAME'] ?? 'Payment System',

    'url' => $_ENV['APP_URL'] ?? 'http://localhost',

    'logo' => $ENV['COMPANY_LOGO'] ?? null,

    'address' => $_ENV['COMPANY_ADDRESS'] ?? null,

    'email' => $_ENV['COMPANY_EMAIL'] ?? null,

    'support' => $_ENV['SUPPORT_EMAIL'] ?? 'support@company.com',



    // 'logo' => $ENV['COMPANY_LOGO'] ?? (isset($_ENV['COMPANY_NAME'])
    //     ? "https://ui-avatars.com/api/?name=" . urlencode($_ENV['COMPANY_NAME']) . "&background=random"
    //     : "https://ui-avatars.com/api/?name=Payment%20System&background=random"),
];
