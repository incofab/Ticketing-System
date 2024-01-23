<?php

namespace App\Support\UITableFilters;

use App\Enums\PaymentReferenceStatus;
use App\Models\TicketPayment;
use Illuminate\Validation\Rules\Enum;

class TicketPaymentUITableFilters extends BaseUITableFilter
{
  protected function getSortableColumns(): array
  {
    return [];
  }

  protected function extraValidationRules(): array
  {
    return [
      'status' => ['nullable', new Enum(PaymentReferenceStatus::class)],
      'event_package_id' => ['nullable', 'integer'], // 'exists:event_package_id,id'],
      'event_id' => ['nullable', 'integer'] //'exists:events,id']
    ];
  }

  protected function generalSearch(string $search)
  {
  }

  private function joinPaymentReference(): static
  {
    $this->callOnce(
      'joinPaymentReference',
      fn() => $this->baseQuery->join('payment_references', function ($q) {
        $q->on(
          'payment_references.paymentable_id',
          'ticket_payments.id'
        )->where(
          'payment_references.paymentable_type',
          (new TicketPayment())->getMorphClass()
        );
      })
    );
    return $this;
  }

  private function joinEventPackage(): static
  {
    $this->callOnce(
      'joinEventPackage',
      fn() => $this->baseQuery->join(
        'event_packages',
        'event_packages.id',
        'ticket_payments.event_package_id'
      )
    );
    return $this;
  }

  protected function directQuery()
  {
    $this->baseQuery->when(
      $this->requestGet('title'),
      fn($q, $value) => $q->where('events.title', 'like', "%$value%")
    );

    return $this;
  }

  public function filterQuery(): static
  {
    $this->joinPaymentReference()->when(
      $this->requestGet('event_id'),
      fn($q, $value) => $q
        ->joinEventPackage()
        ->getQuery()
        ->where('event_packages.event_id', $value)
    );

    $this->baseQuery
      ->when(
        $this->requestGet('status'),
        fn($q, $value) => $q->where('payment_references.status', $value)
      )
      ->when(
        $this->requestGet('event_package_id'),
        fn($q, $value) => $q->where('ticket_payments.event_package_id', $value)
      )
      ->when(
        $this->requestGet('reference'),
        fn($q, $value) => $q->where('payment_references.reference', $value)
      );
    return $this;
  }
}
