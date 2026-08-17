<?php

return [
    'notifications' => [
        'email_recipients' => array_values(array_filter(array_map('trim', explode(',', (string) env('VILOCARE_ALERT_EMAILS', ''))))),
        'sms_recipients' => array_values(array_filter(array_map('trim', explode(',', (string) env('VILOCARE_ALERT_SMS', ''))))),
    ],

    'sms' => [
        'driver' => env('VILOCARE_SMS_DRIVER', 'auto'),
        'appointment_reminder_days_before' => (int) env('VILOCARE_APPOINTMENT_REMINDER_DAYS_BEFORE', 1),
    ],

    'payments' => [
        'simulate_gateways' => (bool) env('VILOCARE_SIMULATE_PAYMENT_GATEWAYS', true),
    ],
];
