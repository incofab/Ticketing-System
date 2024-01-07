<?php
namespace App\Actions;

use App\Models\EventPackage;
use App\Models\PaymentReference;
use App\Models\SeatSection;
use App\Models\Ticket;
use App\Models\TicketPayment;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Str;

class GenerateTicketFromPayment
{
  private EventPackage $eventPackage;
  private SeatSection $seatSection;
  private TicketPayment $ticketPayment;

  /**
   * @param int[] $seatIds
   */
  function __construct(
    private PaymentReference $paymentReference,
    private array $seatIds
  ) {
    $this->ticketPayment = $paymentReference->paymentable;
    $this->ticketPayment->load('eventPackage.seatSection');
    $this->eventPackage = $this->ticketPayment->eventPackage;
    $this->seatSection = $this->eventPackage->seatSection;

    abort_if(
      $this->seatSection->capacity <
        $this->eventPackage->quantity_sold + $this->ticketPayment->quantity,
      401,
      'Not enough available seats'
    );
  }

  /**
   * @return Ticket[] $tickets
   */
  function run()
  {
    $tickets = [];

    foreach ($this->seatIds as $key => $seatId) {
      $ticketReference = Str::orderedUuid();
      $tickets[] = $this->ticketPayment->tickets()->create([
        'reference' => $ticketReference,
        'qr_code' => QrCode::format('svg')->generate(
          "$ticketReference|{$this->ticketPayment->id}"
        ),
        'seat_id' => $seatId,
        'user_id' => $this->ticketPayment->user_id,
        'event_package_id' => $this->ticketPayment->event_package_id
      ]);
    }

    $this->eventPackage
      ->fill([
        'quantity_sold' =>
          $this->eventPackage->quantity_sold + $this->ticketPayment->quantity
      ])
      ->save();
    return $tickets;
  }
}
