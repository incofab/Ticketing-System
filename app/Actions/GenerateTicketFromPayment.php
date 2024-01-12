<?php
namespace App\Actions;

use App\Mail\TicketPurchaseMail;
use App\Models\EventPackage;
use App\Models\PaymentReference;
use App\Models\SeatSection;
use App\Models\Ticket;
use App\Models\TicketPayment;
use DB;
use Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Str;

class GenerateTicketFromPayment
{
  private EventPackage $eventPackage;
  private SeatSection $seatSection;
  private TicketPayment $ticketPayment;
  private int $numOfTicketsToGenerate;

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

    $this->numOfTicketsToGenerate = count($seatIds);
    abort_if(
      $this->seatSection->capacity <
        $this->eventPackage->quantity_sold + $this->numOfTicketsToGenerate,
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

    DB::beginTransaction();
    foreach ($this->seatIds as $key => $seatId) {
      $ticketReference = Str::orderedUuid();
      $generatedTicket = $this->ticketPayment->tickets()->create([
        'reference' => $ticketReference,
        'qr_code' => QrCode::format('svg')->generate(
          "$ticketReference|{$this->ticketPayment->id}"
        ),
        'seat_id' => $seatId,
        'user_id' => $this->ticketPayment->user_id,
        'event_package_id' => $this->ticketPayment->event_package_id
      ]);
      $generatedTicket->load(
        'seat',
        'eventPackage.seatSection',
        'eventPackage.event.eventSeason',
        'ticketPayment'
      );
      $tickets[] = $generatedTicket;
      if ($this->ticketPayment->email) {
        Mail::to($this->ticketPayment->email)->queue(
          new TicketPurchaseMail($generatedTicket)
        );
      }
    }
    DB::commit();
    return $tickets;
  }
}
