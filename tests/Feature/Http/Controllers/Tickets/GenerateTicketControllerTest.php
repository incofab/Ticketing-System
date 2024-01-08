<?php

use App\Models\PaymentReference;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\TicketPayment;

use function Pest\Laravel\postJson;

beforeEach(function () {
  $this->url = route('api.tickets.generate');

  $this->ticketPayment = TicketPayment::factory()->create(['quantity' => 1]);
  $this->paymentReference = PaymentReference::factory()
    ->ticketPayment($this->ticketPayment)
    ->confirmed()
    ->create();
  $this->eventPackage = $this->ticketPayment->eventPackage;
  $this->seats = Seat::factory(3)->create([
    'seat_section_id' => $this->eventPackage->seat_section_id
  ]);
});

it('fails to generate tickets for an invalid payment reference', function () {
  postJson($this->url, [
    'reference' => 'invalid_reference',
    'seat_ids' => [1]
  ])->assertStatus(404);
});

it('fails to generate tickets with invalid seat ids', function () {
  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'seat_ids' => ['invalid_seat_id']
  ])
    ->assertStatus(422)
    ->assertJsonValidationErrors(['seat_ids.0']);

  // Check that seat exist in the right section
  $this->eventPackage = $this->ticketPayment->eventPackage;
  $seat = Seat::factory()->create();

  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'seat_ids' => [$seat->id]
  ])
    ->assertStatus(422)
    ->assertJsonValidationErrors(['seat_ids.0']);
});

it('fails to generate tickets with already booked seats', function () {
  [$seat1] = $this->seats;
  Ticket::factory()
    ->eventPackage($this->eventPackage)
    ->create(['seat_id' => $seat1->id]);

  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'seat_ids' => [$seat1->id]
  ])
    ->assertStatus(422)
    ->assertJsonValidationErrors(['seat_ids.0']);
});

it('generates tickets for a valid payment reference and seat ids', function () {
  $seatIds = $this->seats->pluck('id')->toArray();
  // Will fail is payment quantity is not enough
  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'seat_ids' => $seatIds
  ])->assertForbidden();

  $this->ticketPayment->fill(['quantity' => 4])->save();

  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'seat_ids' => $seatIds
  ])
    ->assertOk()
    ->assertJsonStructure([
      'data' => [
        '*' => [
          'id',
          'seat_id',
          'seat' => ['seat_no'],
          'event_package' => [
            'id',
            'seat_section' => ['title', 'capacity'],
            'event' => [
              'id',
              'title',
              'start_time',
              'event_season' => ['id', 'title']
            ]
          ]
        ]
      ]
    ]);
  expect(
    Ticket::whereIn('seat_id', $seatIds)
      ->get()
      ->count()
  )->toBe(count($seatIds));
  // dd(json_encode(Ticket::query()->first()));
});
