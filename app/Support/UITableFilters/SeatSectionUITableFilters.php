<?php

namespace App\Support\UITableFilters;

class SeatSectionUITableFilters extends BaseUITableFilter
{
  protected function getSortableColumns(): array
  {
    return [
      'title' => 'title',
      'capacity' => 'capacity',
      'createdAt' => 'created_at'
    ];
  }

  protected function extraValidationRules(): array
  {
    return [
      'event' => ['sometimes', 'integer']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where(
      fn($q) => $q->where('seat_sections.title', 'like', "%$search%")
    );
  }

  private function joinEventPackage(): static
  {
    $this->callOnce(
      'joinEventPackage',
      fn() => $this->baseQuery->join(
        'event_packages',
        'event_packages.seat_section_id',
        'seat_sections.id'
      )
    );
    return $this;
  }

  protected function directQuery(): static
  {
    $this->when(
      $this->requestGet('event'),
      fn($q, $value) => $this->joinEventPackage()->baseQuery->when(
        $this->requestGet('event'),
        fn($q, $value) => $q->where('event_packages.event_id', $value)
      )
    );

    return $this;
  }
}
