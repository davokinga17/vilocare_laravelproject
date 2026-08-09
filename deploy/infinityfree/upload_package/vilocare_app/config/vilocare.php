<?php

return [
    'notifications' => [
        'email_recipients' => array_values(array_filter(array_map('trim', explode(',', (string) env('VILOCARE_ALERT_EMAILS', ''))))),
        'sms_recipients' => array_values(array_filter(array_map('trim', explode(',', (string) env('VILOCARE_ALERT_SMS', ''))))),
    ],
];
