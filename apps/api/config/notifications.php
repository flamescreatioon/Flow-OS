<?php

// Notification delivery channels (ADR 0037). Each channel maps to a driver;
// `log` is the safe default for SMS and WhatsApp until a provider is selected
// and credentialed. Provider adapters register as new drivers without
// changing this contract.
return [
    'channels' => [
        'email' => [
            'driver' => env('NOTIFICATION_EMAIL_DRIVER', 'mail'),
        ],
        'sms' => [
            'driver' => env('NOTIFICATION_SMS_DRIVER', 'log'),
        ],
        'whatsapp' => [
            'driver' => env('NOTIFICATION_WHATSAPP_DRIVER', 'log'),
        ],
    ],
];
