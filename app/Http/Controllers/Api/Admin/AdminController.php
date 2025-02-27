<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PaymentReferenceStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSeason;
use App\Models\Seat;
use App\Models\TicketPayment;
use App\Models\User;
use App\Support\MorphMap;
use DB;
use Illuminate\Http\Request;

/**
 * @group Admin
 */
class AdminController extends Controller
{
  public function dashboard(Request $request)
  {
    $data = [
      'seats_count' => Seat::query()->count(),
      'events_count' => Event::query()->count(),
      'event_seasons_count' => EventSeason::query()->count(),
      'users_count' => User::query()->count()
    ];
    return $this->apiRes($data);
  }

  function eventDashboard(Event $event)
  {
    $ticketPaymentQuery = TicketPayment::query()
      ->join(
        'event_packages',
        'ticket_payments.event_package_id',
        'event_packages.id'
      )
      ->where('event_packages.event_id', $event->id);

    // Efficiently calculate total income by considering successful payments
    $totalIncome = DB::table('ticket_payments')
      ->join(
        'event_packages',
        'ticket_payments.event_package_id',
        '=',
        'event_packages.id'
      )
      ->join('payment_references', function ($join) {
        $join
          ->on('payment_references.paymentable_id', 'ticket_payments.id')
          ->where(
            'payment_references.paymentable_type',
            MorphMap::key(TicketPayment::class)
          );
      })
      ->where('event_packages.event_id', $event->id)
      ->where('payment_references.status', PaymentReferenceStatus::Confirmed)
      ->sum('payment_references.amount');

    $data = [
      'total_income' => $totalIncome,
      'tickets_sold' => $ticketPaymentQuery->sum('ticket_payments.quantity'),
      'packages' => $event->eventPackages()->count()
    ];
    return $this->apiRes($data);
  }
}
