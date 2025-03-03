<?php

namespace App\Http\Controllers\Api\Tickets;

use App\Actions\RecordAttendee;
use App\Http\Controllers\Controller;
use App\Models\EventAttendee;
use App\Models\Ticket;
use Illuminate\Http\Request;

/**
 * @group Tickets
 */
class EventAttendeeController extends Controller
{
  /**
   * @queryParam event int No-example
   */
  public function index(Request $request)
  {
    $query = EventAttendee::query()->eventId($request->event);
    return $this->apiRes(paginateFromRequest($query));
  }

  public function store(Request $request, Ticket $ticket)
  {
    $data = $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'max:255', 'email'],
      'phone' => ['nullable', 'string', 'max:15'],
      'address' => ['nullable', 'string', 'max:255']
    ]);

    $ret = RecordAttendee::run($ticket, $data);

    return $this->apiRes($ret);
  }
}
