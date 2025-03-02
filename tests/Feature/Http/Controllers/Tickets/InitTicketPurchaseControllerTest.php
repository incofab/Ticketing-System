<?php

use App\Enums\PaymentMerchantType;
use App\Models\Event;
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

it('can initiate a ticket purchase for Paystack', function () {
  $expiredEvent = Event::factory()->create(['start_time' => now()->subDays(2)]);
  $activeEvent = Event::factory()->create(['start_time' => now()]);
  $expiredEventPackage = EventPackage::factory()
    ->event($expiredEvent)
    ->create();
  $activeEventPackage = EventPackage::factory()
    ->event($activeEvent)
    ->create();
  $requestData = [
    'merchant' => PaymentMerchantType::Paystack->value,
    'callback_url' => 'https://example.com/callback',
    'quantity' => 2,
    'name' => 'John Doe',
    'phone' => '123456789',
    'email' => 'john@example.com'
  ];
  postJson(
    route('api.tickets.init-payment', [$expiredEventPackage]),
    $requestData
  )->assertForbidden();
  postJson(
    route('api.tickets.init-payment', [$activeEventPackage]),
    $requestData
  )
    ->assertSuccessful()
    ->assertJsonStructure(['redirect_url']);

  $this->assertDatabaseHas('ticket_payments', [
    'event_package_id' => $activeEventPackage->id,
    'quantity' => 2,
    'name' => 'John Doe',
    'phone' => '123456789',
    'email' => 'john@example.com'
  ]);
});

it('can initiate a ticket purchase for Bank Deposit', function () {
  $eventPackage = EventPackage::factory()->create();
  postJson(route('api.tickets.init-payment', [$eventPackage]), [
    'merchant' => PaymentMerchantType::BankDeposit->value,
    'quantity' => 2,
    'name' => 'John Doe',
    'phone' => '123456789',
    'email' => 'john@example.com'
  ])->assertSuccessful();

  $this->assertDatabaseHas('ticket_payments', [
    'event_package_id' => $eventPackage->id,
    'quantity' => 2,
    'name' => 'John Doe',
    'phone' => '123456789',
    'email' => 'john@example.com'
  ]);
});

it(
  'cannot initiate a ticket purchase when there are not enough available seats',
  function () {
    /** @var EventPackage $eventPackage */
    $eventPackage = EventPackage::factory()->create();
    // $seatSection = $eventPackage->seatSection;
    $eventPackage->fill(['quantity_sold' => $eventPackage->capacity])->save();
    $postData = [
      'merchant' => PaymentMerchantType::BankDeposit->value,
      'quantity' => 1,
      'name' => 'John Doe',
      'phone' => '123456789',
      'email' => 'john@example.com'
    ];
    postJson(
      route('api.tickets.init-payment', [$eventPackage]),
      $postData
    )->assertJsonValidationErrorFor('quantity');
    $eventPackage
      ->fill(['quantity_sold' => $eventPackage->capacity - 1])
      ->save();
    postJson(route('api.tickets.init-payment', [$eventPackage]), [
      ...$postData,
      'quantity' => 2
    ])->assertJsonValidationErrorFor('quantity');
    $eventPackage
      ->fill(['quantity_sold' => $eventPackage->capacity - 1])
      ->save();

    postJson(
      route('api.tickets.init-payment', [$eventPackage]),
      $postData
    )->assertOk('quantity');

    $this->assertDatabaseHas('ticket_payments', [
      'event_package_id' => $eventPackage->id,
      'quantity' => 1,
      'name' => 'John Doe',
      'phone' => '123456789',
      'email' => 'john@example.com'
    ]);
  }
);
