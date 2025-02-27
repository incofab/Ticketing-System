<?php

use App\Models\EventAttendee;
use App\Models\PaymentReference;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\TicketPayment;

use function Pest\Laravel\postJson;

beforeEach(function () {
  Mail::fake();
  $this->url = route('api.tickets.generate');

  $this->ticketPayment = TicketPayment::factory()->create(['quantity' => 1]);
  $this->paymentReference = PaymentReference::factory()
    ->ticketPayment($this->ticketPayment)
    ->confirmed()
    ->create();
  $this->eventPackage = $this->ticketPayment->eventPackage;
  $this->seats = Seat::factory(4)->create([
    'seat_section_id' => $this->eventPackage->seat_section_id
  ]);
  $this->validStructure = [
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
  ];
});

it('fails to generate tickets for an invalid payment reference', function () {
  postJson($this->url, [
    'reference' => 'invalid_reference',
    'seats' => [['seat_id' => 1]]
  ])->assertStatus(404);
});

it('fails to generate tickets with invalid seat ids', function () {
  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'seats' => [['seat_id' => 'invalid_seat_id']]
  ])
    ->assertStatus(422)
    ->assertJsonValidationErrors(['seats.0.seat_id']);

  // Check that seat exist in the right section
  $this->eventPackage = $this->ticketPayment->eventPackage;
  $seat = Seat::factory()->create();

  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'seats' => [['seat_id' => $seat->id]]
  ])
    ->assertStatus(422)
    ->assertJsonValidationErrors(['seats.0.seat_id']);
});

it('fails to generate tickets with already booked seats', function () {
  [$seat1] = $this->seats;
  Ticket::factory()
    ->eventPackage($this->eventPackage)
    ->create(['seat_id' => $seat1->id]);

  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'seats' => [['seat_id' => $seat1->id]]
  ])
    ->assertStatus(422)
    ->assertJsonValidationErrors(['seats.0.seat_id']);
});

it('generates tickets for a valid payment reference and seat ids', function () {
  $seatIds = $this->seats->pluck('id')->toArray();
  // Will fail is payment quantity is not enough
  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'seats' => $this->seats
      ->map(fn($item) => ['seat_id' => $item->id])
      ->toArray()
  ])->assertForbidden();

  $this->ticketPayment->fill(['quantity' => 4])->save();

  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'seats' => $this->seats
      ->map(
        fn($item) => [
          'seat_id' => $item->id,
          'attendee' => EventAttendee::factory()
            ->make()
            ->toArray()
        ]
      )
      ->toArray()
  ])
    ->assertOk()
    ->assertJsonStructure($this->validStructure);
  expect(Ticket::whereIn('seat_id', $seatIds)->count())->toBe(count($seatIds));
  expect(EventAttendee::query()->count())->toBe(count($seatIds));
  // dd(json_encode(Ticket::query()->first()));
});

it('handles duplicated seat Ids', function () {
  $seatIds = $this->seats->pluck('id')->toArray();
  $this->ticketPayment->fill(['quantity' => count($seatIds) * 2])->save();

  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'seats' => $this->seats
      ->map(fn($item) => ['seat_id' => $item->id])
      ->toArray()
  ])
    ->assertOk()
    ->assertJsonStructure($this->validStructure);
  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'seats' => $this->seats
      ->map(fn($item) => ['seat_id' => $item->id])
      ->toArray()
  ])->assertJsonValidationErrors(
    $this->seats->map(fn($item, $i) => "seats.$i.seat_id")->toArray()
  );

  expect(
    Ticket::whereIn('seat_id', $seatIds)
      ->get()
      ->count()
  )->toBe(count($seatIds));
});

it('generates tickets for seat quantity', function () {
  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'quantity' => 2
  ])->assertForbidden();
  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'quantity' => 1
  ])
    ->assertOk()
    ->assertJsonStructure($this->validStructure);
  expect($this->ticketPayment->tickets()->count())->toBe(1);
});

it('generates tickets for seat ids and quantity', function () {
  $seatData = $this->seats
    ->map(fn($item) => ['seat_id' => $item->id])
    ->toArray();
  $this->ticketPayment->fill(['quantity' => 4])->save();
  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'quantity' => 2,
    'seats' => $seatData
  ])->assertForbidden();

  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'quantity' => 2,
    'seats' => array_splice($seatData, 0, 2)
  ])
    ->assertOk()
    ->assertJsonStructure($this->validStructure);
  expect($this->ticketPayment->tickets()->count())->toBe(4);
});

it('generates tickets for total payment quantity', function () {
  $this->ticketPayment->fill(['quantity' => 4])->save();
  postJson($this->url, [
    'reference' => $this->paymentReference->reference,
    'quantity' => 2
  ])
    ->assertOk()
    ->assertJsonCount(2, 'data');

  postJson($this->url, [
    'reference' => $this->paymentReference->reference
  ])
    ->assertOk()
    ->assertJsonCount(2, 'data');

  postJson($this->url, [
    'reference' => $this->paymentReference->reference
  ])->assertForbidden();

  expect($this->ticketPayment->tickets()->count())->toBe(4);
});
