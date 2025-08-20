<?php

namespace App\Support\UITableFilters;

use App\Enums\PaymentReferenceStatus;
use App\Models\TicketPayment;
use App\Support\MorphMap;
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
      'event_id' => ['nullable', 'integer'],
      'email' => ['nullable', 'string'],
      'phone' => ['nullable', 'string'],
      'merchant' => ['nullable', 'string'],
      'reference' => ['nullable', 'string'],
      'referral_code' => ['nullable', 'string']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where(
      fn($q) => $q->where('ticket_payments.email', 'like', "%$search%")
    );
    return $this;
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
          MorphMap::key(TicketPayment::class)
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

  public function directQuery(): static
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
      )
      ->when(
        $this->requestGet('email'),
        fn($q, $value) => $q->where('ticket_payments.email', $value)
      )
      ->when(
        $this->requestGet('phone'),
        fn($q, $value) => $q->where('ticket_payments.phone', $value)
      )
      ->when(
        $this->requestGet('merchant'),
        fn($q, $value) => $q->where('payment_references.merchant', $value)
      )
      ->when(
        $this->requestGet('referral_code'),
        fn($q, $value) => $q->where('payment_references.referral_code', $value)
      );
    return $this;
  }
}
