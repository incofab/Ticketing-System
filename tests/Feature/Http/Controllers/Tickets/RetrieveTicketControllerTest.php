<?php

use App\Models\User;
use App\Models\PaymentReference;
use App\Models\Ticket;

use function Pest\Laravel\getJson;

beforeEach(function () {
  $this->admin = User::factory()->create();
  $this->paymentReference = PaymentReference::factory()
    ->ticketPayment()
    ->create([
      'reference' => 'valid_reference'
    ]);
  $this->ticketPayment = $this->paymentReference->paymentable;
  $this->requestData = [
    'reference' => $this->paymentReference->reference,
    'email' => $this->ticketPayment->email
  ];
});

it('returns 403 for invalid reference and/or email', function () {
  getJson(
    route('api.tickets.retrieve', [
      'reference' => 'invalid_reference',
      'email' => 'invalid_email@example.com'
    ])
  )->assertStatus(403);
  getJson(
    route('api.tickets.retrieve', [
      'reference' => $this->paymentReference->reference,
      'email' => 'invalid_email@example.com'
    ])
  )->assertStatus(403);
  getJson(
    route('api.tickets.retrieve', [
      'reference' => 'invalid_reference',
      'email' => $this->ticketPayment->email
    ])
  )->assertStatus(403);
});

it('returns tickets and payment for a valid reference and email', function () {
  Ticket::factory(2)
    ->ticketPayment($this->ticketPayment)
    ->create();
  getJson(route('api.tickets.retrieve', $this->requestData))
    ->assertOk()
    ->assertJsonStructure([
      'data' => [
        'tickets' => [
          'data' => [
            '*' => ['seat_id', 'seat', 'qr_code']
          ]
        ],
        'payment' => ['quantity']
      ]
    ]);
});
