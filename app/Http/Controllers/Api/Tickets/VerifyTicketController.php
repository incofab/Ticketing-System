<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Http\Controllers\Controller;
use App\Models\TicketVerification;
use Illuminate\Http\Request;

/**
 * @group Tickets
 */
class VerifyTicketController extends Controller
{
  const SLUG_VERIFIED = 'verified';
  const SLUG_ALREADY_VERIFIED = 'alredy_verified';

  public function __invoke(Request $request)
  {
    $data = $request->validate([
      'ticket_id' => ['required', 'integer'],
      'reference' => [
        'required',
        'string',
        'unique:ticket_verifications,reference'
      ],
      'device_no' => ['required', 'string']
    ]);

    /** @var TicketVerification $existingVerification */
    $existingVerification = TicketVerification::query()
      ->where('ticket_id', $data['ticket_id'])
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
      ->create($data);

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
