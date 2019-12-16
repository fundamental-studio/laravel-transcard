<?php

return [

    'merchantId' => env('TRANSCARD_MERCHANT_ID'),
    'production' => env('TRANSCARD_PRODUCTION'),
    'privateKey' => env('TRANSCARD_PRIVATE_KEY'),
    'privateKeyPass' => env('TRANSCARD_PRIVATE_KEY_PASS'),
    'publicKey' => env('TRANSCARD_PUBLIC_KEY'),
    'returnUrl' => env('TRANSCARD_RETURN_URL'),
    'generateInvoice' => env('TRANSCARD_GENERATE_INVOICE')

];