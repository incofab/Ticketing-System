<?php
use App\Actions\GenerateTicketFromPayment;
use App\Models\PaymentReference;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\TicketPayment;

beforeEach(function () {
  Mail::fake();
});

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

  try {
    $generateTicket = new GenerateTicketFromPayment(
      $paymentReference,
      $seatIds
    );

    $this->expectExceptionCode(401);
  } catch (\Throwable $th) {
    Mail::assertNothingQueued();
    info('Catch error' . get_class($th));
  }
  // Should abort here
  // $tickets = $generateTicket->run();
});

it('aborts if not enough seats 2', function () {
  $paymentReference = PaymentReference::factory()
    ->ticketPayment()
    ->confirmed()
    ->create();
  /** @var TicketPayment $ticketPayment */
  $ticketPayment = $paymentReference->paymentable;
  $eventPackage = $ticketPayment->eventPackage;
  $seatSection = $eventPackage->seatSection;
  $seatSection->fill(['capacity' => 10])->save();
  $quantitySold = 8;
  Ticket::factory($quantitySold)
    ->eventPackage($eventPackage)
    ->create();
  $eventPackage->fill(['quantity_sold' => $quantitySold])->save();

  $seats = Seat::factory(3)->create();
  $seatIds = $seats->pluck('id')->toArray();

  try {
    $generateTicket = new GenerateTicketFromPayment(
      $paymentReference,
      $seatIds
    );

    $this->expectExceptionCode(401);
  } catch (\Throwable $th) {
    Mail::assertNothingQueued();
    info('Catch error' . get_class($th));
  }
  // Should abort here
  // $tickets = $generateTicket->run();
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
  Mail::assertQueuedCount($seats->count());
  // expect($eventPackage->quantity_sold)->toBe($seats->count());
});

it('generates nothing when the ticketpayment is processing', function () {
  $paymentReference = PaymentReference::factory()
    ->ticketPayment()
    ->confirmed()
    ->create();
  /** @var TicketPayment $ticketPayment */
  $ticketPayment = $paymentReference->paymentable;
  $ticketPayment->markProcessing(true);

  $seats = Seat::factory(2)->create();
  $seatIds = $seats->pluck('id')->toArray();
  $generateTicket = new GenerateTicketFromPayment($paymentReference, $seatIds);
  $tickets = $generateTicket->run();

  // Assertions
  expect($tickets)->toHaveCount(0);
});
