<?php

use App\Models\Event;
use App\Models\EventPackage;
use App\Models\PaymentReference;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketPayment;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;

beforeEach(function () {
  seed(RoleSeeder::class);
  $this->admin = User::factory()
    ->role()
    ->create();
  $this->ticket = Ticket::factory()->create();
});

it('shows ticket by reference', function () {
  getJson(route('api.tickets.show.reference', [$this->ticket->reference]))
    ->assertOk()
    ->assertJsonStructure([
      'data' => [
        'ticket' => [
          'seat' => ['id', 'seat_no', 'seat_section' => ['title']],
          'qr_code',
          'event_package' => ['price', 'event' => ['title']]
        ]
      ]
    ]);
});

it('allows admin to delete a ticket', function () {
  $event = Event::factory()->create();
  $eventPackage = EventPackage::factory()->create([
    'event_id' => $event->id,
    'quantity_sold' => 5
  ]);
  $ticketPayment = TicketPayment::factory()
    ->for($eventPackage)
    ->paymentReference()
    ->create();

  // Create ticket
  $ticket = Ticket::factory()
    ->eventPackage($eventPackage)
    ->ticketPayment($ticketPayment)
    ->create();
  $ticketPayment = $ticket->ticketPayment;
  $paymentReference = $ticketPayment->paymentReferences()->first();

  actingAs($this->admin);

  postJson(route('api.tickets.delete', $ticket))->assertOk();

  // Ticket deleted
  expect(Ticket::find($ticket->id))->toBeNull();

  // Payment + references deleted
  expect(TicketPayment::find($ticketPayment->id))->toBeNull();
  expect(PaymentReference::find($paymentReference->id))->toBeNull();

  // Event package quantity_sold decreased
  $eventPackage->refresh();
  expect($eventPackage->quantity_sold)->toBe(4);
});
