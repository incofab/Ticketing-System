<?php

namespace App\Support\UITableFilters;

class TicketUITableFilters extends BaseUITableFilter
{
  protected function getSortableColumns(): array
  {
    return [];
  }

  protected function extraValidationRules(): array
  {
    return [
      'reference' => ['sometimes', 'string'],
      'event_id' => ['sometimes', 'integer'],
      'seat_section_id' => ['sometimes', 'integer'],
      'ticket_payment_id' => ['sometimes', 'integer'],
      'event_package_id' => ['sometimes', 'integer'],
      'seat_id' => ['sometimes', 'integer'],
      'is_verified' => ['sometimes', 'boolean'],
      'is_not_verified' => ['sometimes', 'boolean']
    ];
  }

  protected function generalSearch(string $search): static
  {
    return $this;
  }

  protected function directQuery(): static
  {
    $this->baseQuery->when(
      $this->requestGet('title'),
      fn($q, $value) => $q->where('events.title', 'like', "%$value%")
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
        'tickets.event_package_id'
      )
    );
    return $this;
  }

  private function joinSeat(): static
  {
    $this->callOnce(
      'joinSeat',
      fn() => $this->baseQuery->join('seats', 'seats.id', 'tickets.seat_id')
    );
    return $this;
  }

  public function filterQuery(): static
  {
    $this->joinEventPackage()->when(
      $this->requestGet('seat_section_id'),
      fn($q, $value) => $q
        ->joinSeat()
        ->getQuery()
        ->where('seats.seat_section_id', $value)
    );

    $this->baseQuery
      ->when(
        $this->requestGet('reference'),
        fn($q, $value) => $q->where('tickets.reference', $value)
      )
      ->when(
        $this->requestGet('ticket_payment_id'),
        fn($q, $value) => $q->where('tickets.ticket_payment_id', $value)
      )
      ->when(
        $this->requestGet('event_package_id'),
        fn($q, $value) => $q->where('tickets.event_package_id', $value)
      )
      ->when(
        $this->requestGet('seat_id'),
        fn($q, $value) => $q->where('tickets.seat_id', $value)
      )
      ->when(
        $this->requestGet('event_id'),
        fn($q, $value) => $q->where('event_packages.event_id', $value)
      )
      ->when(
        $this->requestGet('is_verified'),
        fn($q, $value) => $q->whereHas('ticketVerification')
      )
      ->when(
        $this->requestGet('is_not_verified'),
        fn($q, $value) => $q->whereDoesntHave('ticketVerification')
      );
    return $this;
  }
}
