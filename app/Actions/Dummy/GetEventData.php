<?php
namespace App\Actions\Dummy;

use App\Actions\GenericExport;
use App\Models\Event;
use App\Models\Ticket;

class GetEventData
{
  function __construct(private Event $event)
  {
  }

  function run()
  {
    $tickets = Ticket::query()
      ->join('event_packages', 'tickets.event_package_id', 'event_packages.id')
      ->where('event_packages.event_id', $this->event->id)
      ->with(
        'eventPackage',
        'eventAttendee',
        'ticketVerification',
        'ticketPayment.paymentReferences',
        'seat.seatSection'
      )
      ->get();
    $formattedTickets = [];
    foreach ($tickets as $key => $ticket) {
      $formattedTickets[] = [
        'Package' => $ticket->eventPackage->title,
        'Attendee name' => $ticket->eventAttendee?->name,
        'Attendee phone' => $ticket->eventAttendee?->phone,
        'Attendee email' => $ticket->eventAttendee?->email,
        'Paid by Name' => $ticket->ticketPayment->name,
        'Paid by Email' => $ticket->ticketPayment->email,
        'Paid by Phone' => $ticket->ticketPayment->phone,
        'Verified At' => $ticket->ticketVerification?->created_at,
        'Seat No' => $ticket->seat->seat_no,
        'Seat Section' => $ticket->seat->seatSection->title,
        'Payment Merchant' => $ticket->ticketPayment->paymentReferences?->first()
          ?->merchant->value
      ];
    }
    if (empty($formattedTickets)) {
      return 'No tickets found';
    }
    return (new GenericExport(
      $formattedTickets,
      "event-report-{$this->event->id}-{$this->event->title}.xlsx"
    ))->download();
  }
}
