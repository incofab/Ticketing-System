<?php
use App\Actions\GenerateTicketFromPayment;
use App\Models\EventPackage;
use App\Models\PaymentReference;
use App\Models\SeatSection;
use App\Models\Ticket;
use App\Models\TicketPayment;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Str;

it('generates tickets from payment', function () {
  // Create necessary models for testing
  $paymentReference = PaymentReference::factory()->create();
  $eventPackage = EventPackage::factory()->create();
  $seatSection = SeatSection::factory()->create();
  $ticketPayment = TicketPayment::factory()->create([
    'paymentable_type' => get_class($paymentReference),
    'paymentable_id' => $paymentReference->id,
    'event_package_id' => $eventPackage->id
  ]);
  $ticketPayment->load('eventPackage.seatSection');

  // Mock the QrCode facade
  QrCode::shouldReceive('format->generate')->andReturn('mocked_qr_code');

  // Mock the Str::orderedUuid() method
  Str::shouldReceive('orderedUuid')->andReturn('mocked_ordered_uuid');

  // Set the capacity to allow the generation of tickets
  DB::table('seat_sections')
    ->where('id', $seatSection->id)
    ->update(['capacity' => 10]);

  // Initialize the GenerateTicketFromPayment helper class
  $generateTicket = new GenerateTicketFromPayment($paymentReference, [1, 2, 3]);

  // Run the method
  $tickets = $generateTicket->run();

  // Assertions
  expect($tickets)->toHaveCount(3);

  foreach ($tickets as $ticket) {
    expect($ticket)->toBeInstanceOf(Ticket::class);
    expect($ticket->reference)->toBe('mocked_ordered_uuid');
    expect($ticket->qr_code)->toBe('mocked_qr_code');
    expect($ticket->seat_id)->toBeIn([1, 2, 3]);
    expect($ticket->user_id)->toBe($ticketPayment->user_id);
    expect($ticket->event_package_id)->toBe($ticketPayment->event_package_id);
  }

  // Check if quantity_sold is updated in EventPackage
  $eventPackage->refresh();
  expect($eventPackage->quantity_sold)->toBe(3);
});
