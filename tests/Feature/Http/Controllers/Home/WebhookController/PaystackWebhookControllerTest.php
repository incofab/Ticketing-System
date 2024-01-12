<?php

use App\Models\PaymentReference;

use function Pest\Laravel\postJson;

beforeEach(function () {
  // Perform any setup tasks
});

it('handles Paystack webhook successfully', function () {
  $paymentReference = PaymentReference::factory()
    ->ticketPayment()
    ->create();
  // Mock the necessary data
  $eventData = [
    'event' => 'charge.success',
    'data' => [
      'status' => 'success',
      'customer' => [
        'email' => 'test@example.com',
        'amount' => 5000 // Replace with your desired amount
      ],
      'reference' => $paymentReference->reference
    ]
  ];

  // Make a POST request to the route with mock data
  postJson(route('webhook.paystack'), $eventData)
    ->dump()
    ->assertOk()
    ->assertJsonStructure(['success', 'message']);
});

// Add more tests as needed

afterEach(function () {
  // Perform any teardown tasks
});
