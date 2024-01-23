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
      'ticket_payment_id' => ['sometimes', 'integer'],
      'event_package_id' => ['sometimes', 'integer'],
      'seat_id' => ['sometimes', 'integer']
    ];
  }

  protected function generalSearch(string $search)
  {
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
      );
    return $this;
  }
}
