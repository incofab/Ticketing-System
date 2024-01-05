<?php

use App\Enums\PaymentMerchantType;
use App\Models\EventPackage;

use function Pest\Laravel\postJson;

beforeEach(function () {
  Http::fake([
    'https://api.paystack.co/transaction/initialize' => Http::response(
      [
        'status' => true,
        'data' => [
          'authorization_url' => 'https://redirecturl',
          'reference' => 'dummy-response'
        ]
      ],
      200
    )
  ]);
});

it('can initiate a ticket purchase', function () {
  $eventPackage = EventPackage::factory()->create();
  postJson(route('api.tickets.init-payment', [$eventPackage]), [
    'merchant' => PaymentMerchantType::Paystack->value,
    'callback_url' => 'https://example.com/callback',
    'quantity' => 2,
    'name' => 'John Doe',
    'phone' => '123456789',
    'email' => 'john@example.com'
  ])
    ->assertSuccessful()
    ->assertJsonStructure(['redirect_url']);

  $this->assertDatabaseHas('ticket_payments', [
    'event_package_id' => $eventPackage->id,
    'quantity' => 2,
    'name' => 'John Doe',
    'phone' => '123456789',
    'email' => 'john@example.com'
  ]);
});
