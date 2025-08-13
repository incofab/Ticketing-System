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
   * Store new coupons
   *
   * @bodyParam code string required The coupon code
   * @bodyParam discount_type string required The type of discount. Possible values: percentage, fixed. Example: percentage
   * @bodyParam discount_value float required The value of the discount. Example: 20
   * @bodyParam min_purchase float The minimum purchase amount required to use the coupon. Example: 100.00
   * @bodyParam expires_at date The expiration date of the coupon (Leave blank if you dont want to coupon to expire)
   * @bodyParam usage_limit integer The maximum number of times the coupon can be used. Example: 10
   * @bodyParam usage_count integer The number of times the coupon has been used. Example: 0
   * @bodyParam event_id integer required The ID of the event this coupon is associated with.
   * @bodyParam event_package_ids array An array of event package IDs this coupon applies to. (Leave blank if you want it to apply to all) Example: [1, 2, 3]
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
  public function show(Coupon $coupon)
  {
    return $this->apiRes($coupon);
  }

  /**
   * Updates an existing coupon
   *
   * @bodyParam code string required The coupon code
   * @bodyParam discount_type string required The type of discount. Possible values: percentage, fixed. Example: percentage
   * @bodyParam discount_value float required The value of the discount. Example: 20
   * @bodyParam min_purchase float The minimum purchase amount required to use the coupon. Example: 100.00
   * @bodyParam expires_at date The expiration date of the coupon (Leave blank if you dont want to coupon to expire)
   * @bodyParam usage_limit integer The maximum number of times the coupon can be used. Example: 10
   * @bodyParam usage_count integer The number of times the coupon has been used. Example: 0
   * @bodyParam event_id integer required The ID of the event this coupon is associated with.
   * @bodyParam event_package_ids array An array of event package IDs this coupon applies to. (Leave blank if you want it to apply to all) Example: [1, 2, 3]
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
