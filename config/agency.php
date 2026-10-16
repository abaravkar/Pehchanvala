<?php
return [
    'email'=>env('AGENCY_EMAIL','info@pehchanvala.com'),
    'razorpay_key'=>env('RAZORPAY_KEY_ID',''),
    'razorpay_secret'=>env('RAZORPAY_KEY_SECRET',''),
    'razorpay_webhook_secret'=>env('RAZORPAY_WEBHOOK_SECRET',''),
    'payments_enabled'=>(bool)env('PAYMENTS_ENABLED',false),
];
