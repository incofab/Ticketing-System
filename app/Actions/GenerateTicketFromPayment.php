<?php
namespace App\Actions;

use App\Mail\TicketPurchaseMail;
use App\Models\EventPackage;
use App\Models\PaymentReference;
use App\Models\SeatSection;
use App\Models\Ticket;
use App\Models\TicketPayment;
use DB;
use Illuminate\Support\Collection;
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
   * @param int[] $seatIds,
   * @param PaymentReference $paymentReference
   * @param array{
   *  seat_id: int,
   *  attendee: array{
   *   name: string,
   *   phone: string,
   *   email: string,
   *   address: string,
   * }
   * }[] $seatExtraData
   */
  function __construct(
    private PaymentReference $paymentReference,
    private array $seatIds,
    private array $seatExtraData = []
  ) {
    $this->ticketPayment = $paymentReference->paymentable;
    $this->ticketPayment->load('eventPackage.seatSection');
    $this->eventPackage = $this->ticketPayment->eventPackage;
    $this->seatSection = $this->eventPackage->seatSection;

    $numOfTicketsGenerated = $this->eventPackage->tickets()->count(); //$this->eventPackage->quantity_sold;
    $this->numOfTicketsToGenerate = count($seatIds);
    abort_if(
      $this->seatSection->capacity <
        $numOfTicketsGenerated + $this->numOfTicketsToGenerate,
      // $this->eventPackage->quantity_sold + $this->numOfTicketsToGenerate,
      401,
      'Not enough available seats'
    );
  }

  /**
   * @return Ticket[]|Collection<int, Ticket> $tickets
   */
  static function generateFromPaymentReference(
    PaymentReference $paymentReference
  ) {
    /** @var TicketPayment $ticketPayment */
    $ticketPayment = $paymentReference->paymentable;
    $existingTicketsGenerated = $ticketPayment->tickets()->count();
    $remainingSeats = $ticketPayment->quantity - $existingTicketsGenerated;

    if ($remainingSeats < 1) {
      return $ticketPayment
        ->tickets()
        ->with([
          'seat',
          'eventPackage.seatSection',
          'eventPackage.event.eventSeason',
          'ticketPayment'
        ])
        ->get();
    }

    $seatIds = GetAvailableSeats::run($ticketPayment->eventPackage)
      ->take($ticketPayment->quantity)
      ->pluck('seats.id')
      ->toArray();

    return (new self($paymentReference, $seatIds))->run();
  }

  /**
   * @return Ticket[] $tickets
   */
  function run()
  {
    if ($this->ticketPayment->processing) {
      return [];
    }
    try {
      $this->ticketPayment->markProcessing(true);
      return $this->generateTickets();
    } catch (\Throwable $th) {
      info(
        'GenerateTicketFromPayment: Error generation tickets: ' .
          $th->getMessage()
      );
    } finally {
      $this->ticketPayment->markProcessing(false);
    }
    return [];
  }

  function generateTickets()
  {
    $tickets = [];

    DB::beginTransaction();
    foreach ($this->seatIds as $key => $seatId) {
      $ticketReference = Str::orderedUuid();
      $generatedTicket = $this->ticketPayment->tickets()->firstOrCreate(
        [
          'seat_id' => $seatId,
          'event_package_id' => $this->ticketPayment->event_package_id
        ],
        [
          'reference' => $ticketReference,
          'qr_code' => QrCode::format('svg')->generate(
            "$ticketReference|{$this->ticketPayment->id}"
          ),
          'user_id' => $this->ticketPayment->user_id
        ]
      );
      $generatedTicket->load(
        'seat',
        'eventPackage.seatSection',
        'eventPackage.event.eventSeason',
        'ticketPayment'
      );

      $this->recordAttendee($seatId, $generatedTicket);

      $tickets[] = $generatedTicket;
      if ($this->ticketPayment->email) {
        Mail::to($this->ticketPayment->email)->queue(
          new TicketPurchaseMail($generatedTicket->fresh())
        );
      }
    }
    DB::commit();
    return $tickets;
  }

  private function recordAttendee($seatId, Ticket $ticket)
  {
    $attendeeData = [];
    foreach ($this->seatExtraData as $key => $item) {
      if ($item['seat_id'] == $seatId) {
        $attendeeData = $item;
        break;
      }
    }
    if (empty($attendeeData['attendee'])) {
      return;
    }
    RecordAttendee::run($ticket, $attendeeData['attendee']);
  }
}
