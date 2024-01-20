<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketVerification;
use Illuminate\Http\Request;

/**
 * @group Tickets
 */
class VerifyTicketController extends Controller
{
  const SLUG_INVALID_TICKET = 'invalid_ticket';
  const SLUG_VERIFIED = 'verified';
  const SLUG_ALREADY_VERIFIED = 'alredy_verified';

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
      if ($existingVerification->isVerificationStillValid($data['device_no'])) {
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

  function res($success, $slug, $data)
  {
    return $this->ok([
      'success' => $success,
      'slug' => $slug,
      'data' => $data
    ]);
  }
}
