<?php

return [
    'enabled' => env('SMS_ENABLED', false),
    'driver' => env('SMS_DRIVER', 'twilio'),
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],
];
