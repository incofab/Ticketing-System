<?php

namespace App\Rules;

use App\Models\Seat;
use App\Models\Ticket;
use App\Models\TicketPayment;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateSeatAvailabilityRule implements ValidationRule
{
  function __construct(private TicketPayment $ticketPayment)
  {
  }

  /**
   * Run the validation rule.
   *
   * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
   */
  public function validate(string $attribute, mixed $value, Closure $fail): void
  {
    $seat = Seat::query()
      ->where('id', $value)
      ->where(
        'seat_section_id',
        $this->ticketPayment->eventPackage->seat_section_id
      )
      ->first();
    if (!$seat) {
      $fail("$attribute not available in this section of payment");
      return;
    }
    // Check if the seats has been booked already
    $existingTicket = Ticket::query()
      ->where('event_package_id', $this->ticketPayment->event_package_id)
      ->where('tickets.seat_id', $value)
      ->first();
    if ($existingTicket) {
      $fail("$attribute has already been booked");
      return;
    }
  }
}
