<?php

namespace Database\Factories;

use App\Models\EventPackage;
use App\Models\Seat;
use App\Models\TicketPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

class TicketFactory extends Factory
{
  public function definition(): array
  {
    return [
      'ticket_payment_id' => TicketPayment::factory()->paymentReference(),
      'event_package_id' => EventPackage::factory(),
      'seat_id' => Seat::factory(),
      'user_id' => User::factory(),
      'reference' => Str::orderedUuid(),
      'qr_code' => fake()->imageUrl()
    ];
  }

  function eventPackage(EventPackage $eventPackage = null)
  {
    $eventPackage = $eventPackage ?? EventPackage::factory()->create();
    return $this->state(
      fn($attr) => [
        'event_package_id' => $eventPackage->id
        // 'seat_id' => Seat::factory()->create([
        //   'seat_section_id' => $eventPackage->seat_section_id
        // ])
      ]
    );
  }

  function ticketPayment(TicketPayment $ticketPayment = null)
  {
    $ticketPayment = $ticketPayment ?? TicketPayment::factory()->create();
    return $this->state(
      fn($attr) => [
        'event_package_id' => $ticketPayment->event_package_id,
        'ticket_payment_id' => $ticketPayment->id
      ]
    );
  }
}
