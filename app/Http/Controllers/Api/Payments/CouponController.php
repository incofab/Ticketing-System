<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Event;
use Illuminate\Http\Request;

/**
 * @group Payments
 * @subgroup Coupon
 */
class CouponController extends Controller
{
  public function __construct()
  {
    $this->middleware('admin');
  }

  /**
   * Display a listing of the resource.
   */
  public function index(Event $event, Request $request)
  {
    return $this->apiRes(paginateFromRequest($event->coupons()));
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request, Event $event)
  {
    $validated = $request->validate(Coupon::createRule());

    $coupon = $event->coupons()->create(
      collect($validated)
        ->except('event_package_ids')
        ->toArray()
    );
    $coupon->eventPackages()->sync($request->input('event_package_id', []));

    return $this->apiRes($coupon);
  }

  /**
   * Display the specified resource.
   */
  public function show(Event $event, Coupon $coupon)
  {
    return $this->apiRes($coupon);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, Coupon $coupon)
  {
    $validated = $request->validate(Coupon::createRule($coupon));

    $coupon->update(
      collect($validated)
        ->except('event_package_ids')
        ->toArray()
    );

    $coupon->eventPackages()->sync($request->input('event_package_id', []));

    return $this->apiRes($coupon);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Coupon $coupon)
  {
    abort_if(
      $coupon->ticketPayments()->exists(),
      403,
      'Cannot delete coupon with associated payments.'
    );
    $coupon->delete();

    return $this->ok();
  }
}
