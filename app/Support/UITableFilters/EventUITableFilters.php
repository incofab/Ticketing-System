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
      'start_time_to' => ['sometimes', 'date'],
      'event_category' => ['sometimes', 'integer'],
      'venue' => ['sometimes', 'string']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where(
      fn($q) => $q->where('events.title', 'like', "%$search%")
    );
  }

  private function joinEventSeason(): static
  {
    $this->callOnce(
      'joinEventSeason',
      fn() => $this->baseQuery->join(
        'event_seasons',
        'event_seasons.id',
        'events.event_season_id'
      )
    );
    return $this;
  }

  protected function directQuery(): static
  {
    $this->joinEventSeason();
    $this->dateFilter(
      'events.start_time',
      $this->requestGet('start_time_from'),
      $this->requestGet('start_time_to')
    )
      ->baseQuery->when(
        $this->requestGet('title'),
        fn($q, $value) => $q->where('events.title', 'like', "%$value%")
      )
      ->when(
        $this->requestGet('event_category'),
        fn($q, $value) => $q->where(
          'event_seasons.event_category_id',
          "%$value%"
        )
      )
      ->when(
        $this->requestGet('venue'),
        fn($q, $value) => $q->where('events.venue', 'LIKE', "%$value%")
      );

    return $this;
  }
}
