<?php
use App\Actions\GenerateTicketFromPayment;
use App\Models\PaymentReference;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\TicketPayment;

it('aborts if not enough seats', function () {
  $paymentReference = PaymentReference::factory()
    ->ticketPayment()
    ->confirmed()
    ->create();
  /** @var TicketPayment $ticketPayment */
  $ticketPayment = $paymentReference->paymentable;
  $eventPackage = $ticketPayment->eventPackage;
  $seatSection = $eventPackage->seatSection;

  $seatSection->fill(['capacity' => 1])->save();

  $seats = Seat::factory(2)->create();
  $seatIds = $seats->pluck('id')->toArray();
  $generateTicket = new GenerateTicketFromPayment($paymentReference, $seatIds);
  // Should abort here
  $tickets = $generateTicket->run();
});

it('generates tickets from payment', function () {
  $paymentReference = PaymentReference::factory()
    ->ticketPayment()
    ->confirmed()
    ->create();
  /** @var TicketPayment $ticketPayment */
  $ticketPayment = $paymentReference->paymentable;
  $eventPackage = $ticketPayment->eventPackage;
  $seatSection = $eventPackage->seatSection;

  $seats = Seat::factory(2)->create();

  $seatIds = $seats->pluck('id')->toArray();
  $generateTicket = new GenerateTicketFromPayment($paymentReference, $seatIds);
  $tickets = $generateTicket->run();

  // Assertions
  expect($tickets)->toHaveCount($seats->count());
  //   info(json_encode($tickets, JSON_PRETTY_PRINT));
  //   dd($tickets);
  foreach ($tickets as $ticket) {
    expect($ticket)->toBeInstanceOf(Ticket::class);
    expect($ticket->reference . '')->toBeString();
    expect($ticket->qr_code)
      ->not()
      ->toBeEmpty();
    expect($ticket->seat_id)->toBeIn($seatIds);
    expect($ticket->user_id)->toBe($ticketPayment->user_id);
    expect($ticket->event_package_id)->toBe($ticketPayment->event_package_id);
  }

  // Check if quantity_sold is updated in EventPackage
  $eventPackage->refresh();
  expect($eventPackage->quantity_sold)->toBe($seats->count());
});