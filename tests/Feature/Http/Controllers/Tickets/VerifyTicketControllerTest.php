<?php

use App\Http\Controllers\Api\Tickets\VerifyTicketController;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

beforeEach(function () {
  $this->admin = User::factory()->create();
  $this->ticket = Ticket::factory()->create();
  $reference = 'unique_reference-' . rand(10000, 99999);
  $this->requestData = [
    'ticket_payment_id' => $this->ticket->ticket_payment_id,
    'hash' => $this->ticket->reference,
    'reference' => $reference,
    'device_no' => 'device123',
    'event_id' => $this->ticket->eventPackage->event_id
  ];
});

it('returns validation error if required fields are missing', function () {
  actingAs($this->admin)
    ->postJson(route('api.tickets.verify'), [])
    ->assertJsonValidationErrors([
      'reference',
      'hash',
      'device_no',
      'ticket_payment_id',
      'event_id'
    ]);
});

it(
  'returns invalid ticket when ticket is not for the supplied event',
  function () {
    $event = Event::factory()->create();
    actingAs($this->admin)
      ->postJson(route('api.tickets.verify'), [
        ...$this->requestData,
        'event_id' => $event->id
      ])
      ->assertOk()
      ->assertJson([
        'success' => false,
        'slug' => VerifyTicketController::SLUG_INVALID_TICKET
      ]);
  }
);

it('can verify a ticket', function () {
  actingAs($this->admin)
    ->postJson(route('api.tickets.verify'), $this->requestData)
    ->assertOk()
    ->assertJsonStructure(['success', 'slug', 'data' => ['id']])
    ->assertJson(['success' => true, 'slug' => 'verified']);

  // Assert that the ticket verification record exists in the database
  $this->assertDatabaseHas(
    'ticket_verifications',
    collect([...$this->requestData, 'ticket_id' => $this->ticket->id])
      ->except('hash', 'ticket_payment_id', 'event_id')
      ->toArray()
  );
});

it('ensures ticket hash is valid', function () {
  actingAs($this->admin)
    ->postJson(route('api.tickets.verify'), [
      ...$this->requestData,
      'hash' => 'invalid hash'
    ])
    ->assertOk()
    ->assertJson([
      'success' => false,
      'slug' => VerifyTicketController::SLUG_INVALID_TICKET
    ]);
  actingAs($this->admin)
    ->postJson(route('api.tickets.verify'), $this->requestData)
    ->assertOk()
    ->assertJson([
      'success' => true,
      'slug' => VerifyTicketController::SLUG_VERIFIED
    ]);

  // Assert that the ticket verification record exists in the database
  $this->assertDatabaseHas(
    'ticket_verifications',
    collect([...$this->requestData, 'ticket_id' => $this->ticket->id])
      ->except('hash', 'ticket_payment_id', 'event_id')
      ->toArray()
  );
});

it('returns already verified if the ticket is already verified', function () {
  actingAs($this->admin)
    ->postJson(route('api.tickets.verify'), $this->requestData)
    ->assertOk();

  travelTo(now()->addSeconds(2));

  // Verification still valid if called from same device and within time allowance
  actingAs($this->admin)
    ->postJson(route('api.tickets.verify'), [
      ...$this->requestData,
      'reference' => 'refA'
    ])
    ->assertOk()
    ->assertJson(['success' => true]);

  // Verification NOT valid if called from different device
  actingAs($this->admin)
    ->postJson(route('api.tickets.verify'), [
      ...$this->requestData,
      'reference' => 'refB',
      'device_no' => 'device123B'
    ])
    ->assertOk()
    ->assertJson(['success' => false]);

  travelTo(now()->addSeconds(70));

  // Verification NOT valid if time allowance has elapsed
  actingAs($this->admin)
    ->postJson(route('api.tickets.verify'), [
      ...$this->requestData,
      'reference' => 'refC'
    ])
    ->assertOk()
    ->assertJson(['success' => false]);
});
