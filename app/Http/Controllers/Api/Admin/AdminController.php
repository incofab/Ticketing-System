<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PaymentMerchantType;
use App\Enums\PaymentReferenceStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventPackage;
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
  function __construct()
  {
    $this->middleware('admin')->only('dashboard');
  }

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

  function eventDashboard(Request $request, Event $event)
  {
    $request->validate([
      'date_from' => ['nullable', 'date'],
      'date_to' => [
        'nullable',
        'date',
        'required_with:date_from',
        'after:date_from'
      ]
    ]);

    $user = currentUser();
    abort_unless(
      $user->isAdmin() || $event->user_id == $user->id,
      403,
      'Access denied'
    );

    $verifiedAttendees = DB::table('tickets')
      ->join('event_packages', 'event_packages.id', 'tickets.event_package_id')
      ->join(
        'ticket_verifications',
        'ticket_verifications.ticket_id',
        'tickets.id'
      )
      ->where('event_packages.event_id', $event->id)
      ->count();

    $totalPackageCapacity = DB::table('event_packages')
      ->where('event_id', $event->id)
      ->sum('capacity');
    $projectedRevenue = EventPackage::query()
      ->where('event_id', $event->id)
      ->sum(DB::raw('price * capacity'));
    $data = [
      'event' => $event,
      ...$this->incomeStat($event, $request->date_from, $request->date_to),
      'packages' => $event->eventPackages()->count(),
      'attendees' => $event->eventAttendees()->count(),
      'verified_attendees' => $verifiedAttendees,
      'seats_count' => EventPackage::query()
        ->where('event_id', $event->id)
        ->sum('capacity'),
      'total_package_capacity' => $totalPackageCapacity,
      'project_revenue' => $projectedRevenue
    ];
    return $this->apiRes($data);
  }

  private function incomeStat(Event $event, $dateFrom, $dateTo)
  {
    // Efficiently calculate total income by considering successful payments
    $query = DB::table('ticket_payments')
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
      ->where('payment_references.status', PaymentReferenceStatus::Confirmed);

    $query = $this->dateFilter(
      $query,
      $dateFrom,
      $dateTo,
      'ticket_payments.created_at'
    );

    $incomeStat = [];
    foreach (PaymentMerchantType::cases() as $key => $type) {
      $merchant = $type->value;
      $incomeStat[$merchant] = [
        'count' => (clone $query)
          ->where('payment_references.merchant', $merchant)
          ->count(),
        'total_amount' => (clone $query)
          ->where('payment_references.merchant', $merchant)
          ->sum('payment_references.amount')
      ];
    }
    return [
      'tickets_sold' => (clone $query)->sum('ticket_payments.quantity'),
      'total_income' => (clone $query)->sum('payment_references.amount'),
      'income_stat' => $incomeStat
    ];
  }

  private function dateFilter(
    \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query,
    $dateFrom,
    $dateTo,
    $createdAt = 'created_at'
  ) {
    return $query
      ->when(
        $dateTo,
        fn($q) => $q->whereBetween($createdAt, [$dateFrom, $dateTo])
      )
      ->when($dateFrom, fn($q) => $q->where($createdAt, '>', $dateFrom));
  }
}
