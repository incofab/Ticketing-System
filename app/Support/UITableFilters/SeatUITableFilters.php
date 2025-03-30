<?php

namespace App\Support\UITableFilters;

class SeatUITableFilters extends BaseUITableFilter
{
  protected function getSortableColumns(): array
  {
    return [
      'seat_no' => 'title',
      'createdAt' => 'created_at'
    ];
  }

  protected function extraValidationRules(): array
  {
    return [
      'seat_section_id' => ['sometimes', 'integer'],
      'seat_no' => ['sometimes', 'string'],
      'status' => ['sometimes', 'string']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where(
      fn($q) => $q->where('seats.seat_no', 'like', "%$search%")
    );
  }

  protected function directQuery(): static
  {
    $this->baseQuery
      ->when(
        $this->requestGet('seat_section_id'),
        fn($q, $value) => $q->where('seats.seat_section_id', $value)
      )
      ->when(
        $this->requestGet('seat_no'),
        fn($q, $value) => $q->where('seats.seat_no', $value)
      )
      ->when(
        $this->requestGet('status'),
        fn($q, $value) => $q->where('seats.status', $value)
      );

    return $this;
  }
}
