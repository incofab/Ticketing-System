<?php

return [
  /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

  'mailgun' => [
    'domain' => env('MAILGUN_DOMAIN'),
    'secret' => env('MAILGUN_SECRET'),
    'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net')
  ],

  'postmark' => [
    'token' => env('POSTMARK_TOKEN')
  ],

  'ses' => [
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1')
  ],
  'jwt' => [
    'secret-key' => env('JWT_SECRET_KEY')
  ],
  'paystack' => [
    'public-key' => env('PAYSTACK_PUBLIC_KEY'),
    'secret-key' => env('PAYSTACK_SECRET_KEY'),
    'ticket-subaccount' => env('PAYSTACK_TICKET_SUBACCOUNT')
  ],
  'airvend' => [
    'merchant-key' => env('AIRVEND_MERCHANT_KEY'),
    'secret-key' => env('AIRVEND_SECRET_KEY')
  ],
  'paydestal' => [
    'public-key' => env('PAYDESTAL_PUBLIC_KEY'),
    'secret-key' => env('PAYDESTAL_SECRET_KEY')
  ],
  'maildrip' => ['api_key' => env('MAIL_DRIP_API_KEY')],
  'pdf-gen-url' => env('PDF_GEN_URL', '')
];
