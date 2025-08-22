<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketVerification;
use Exception;
use Illuminate\Http\Request;

/**
 * @group Tickets
 */
class VerifyTicketController extends Controller
{
  const SLUG_INVALID_TICKET = 'invalid_ticket';
  const SLUG_VERIFIED = 'verified';
  const SLUG_ALREADY_VERIFIED = 'already_verified';

  public function __invoke(Request $request)
  {
    $data = $request->validate([
      'ticket_payment_id' => ['required', 'integer'],
      'hash' => ['required', 'string'],
      'reference' => [
        'required',
        'string',
        'unique:ticket_verifications,reference'
      ],
      'device_no' => ['required', 'string'],
      'event_id' => ['required', 'integer']
    ]);

    $ticket = Ticket::query()
      ->where('ticket_payment_id', $request->ticket_payment_id)
      ->where('reference', $request->hash)
      ->with('eventPackage')
      ->first();

    if (!$ticket || $ticket->eventPackage->event_id !== $data['event_id']) {
      return $this->res(false, self::SLUG_INVALID_TICKET, []);
    }

    /** @var TicketVerification $existingVerification */
    $existingVerification = TicketVerification::query()
      ->where('ticket_id', $ticket->id)
      ->with('user')
      ->first();

    if ($existingVerification) {
      if (
        $existingVerification->isVerificationStillValid(
          $data['device_no'],
          $data['reference']
        )
      ) {
        return $this->res(true, self::SLUG_VERIFIED, $existingVerification);
      }
      return $this->res(
        false,
        self::SLUG_ALREADY_VERIFIED,
        $existingVerification
      );
    }

    $ticketVerification = currentUser()
      ->ticketVerifications()
      ->create(
        collect([...$data, 'ticket_id' => $ticket->id])
          ->except('hash', 'ticket_payment_id', 'event_id')
          ->toArray()
      );

    return $this->res(true, self::SLUG_VERIFIED, $ticketVerification);
  }

  private function res($success, $slug, ?TicketVerification $data = null)
  {
    $title = '';
    $message = '';
    if ($slug === self::SLUG_VERIFIED) {
      $title = 'Ticket Verified';
      $message = 'This ticket is valid and You\'re good to go!';
    } elseif ($slug === self::SLUG_ALREADY_VERIFIED) {
      $title = 'Already Checked-In';
      $message =
        'This ticket has already been verified. Please check with the event team.';
    } elseif ($slug === self::SLUG_INVALID_TICKET) {
      $title = 'Invalid Ticket';
      $message =
        'This ticket is not recognized. Please try again or scan a valid QR code.';
    } else {
      throw new Exception("Invalid slug: $slug");
    }
    $data?->load(
      'ticket.eventPackage.event',
      'ticket.seat.seatSection',
      'ticket.ticketPayment'
    );
    return $this->ok([
      'success' => $success,
      'slug' => $slug,
      'data' => $data,
      'title' => $title,
      'message' => $message
    ]);
  }
}
