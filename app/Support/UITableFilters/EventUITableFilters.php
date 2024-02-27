<?php

namespace App\Support\UITableFilters;

class EventUITableFilters extends BaseUITableFilter
{
  protected function getSortableColumns(): array
  {
    return [
      'title' => 'title',
      'startTime' => 'start_time',
      'endTime' => 'end_time',
      'createdAt' => 'created_at'
    ];
  }

  protected function extraValidationRules(): array
  {
    return [
      'title' => ['sometimes', 'string'],
      'start_time_from' => ['sometimes', 'date'],
      'start_time_to' => ['sometimes', 'date']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where(
      fn($q) => $q->where('events.title', 'like', "%$search%")
    );
  }

  protected function directQuery()
  {
    $this->dateFilter(
      'events.start_time',
      $this->requestGet('start_time_from'),
      $this->requestGet('end_time_from')
    )->baseQuery->when(
      $this->requestGet('title'),
      fn($q, $value) => $q->where('events.title', 'like', "%$value%")
    );

    return $this;
  }
}
