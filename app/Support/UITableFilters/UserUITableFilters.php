<?php

namespace App\Support\UITableFilters;

class UserUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'firstName' => 'first_name',
    'lastName' => 'last_name',
    'email' => 'email',
    'createdAt' => 'created_at'
  ];

  protected function extraValidationRules(): array
  {
    return [
      'first_name' => ['sometimes', 'string'],
      'last_name' => ['sometimes', 'string'],
      'name' => ['sometimes', 'string'],
      'email' => ['sometimes', 'string']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->baseQuery->where(
      fn($q) => $q
        ->where('users.last_name', 'like', "%$search%")
        ->orWhere('users.first_name', 'like', "%$search%")
        ->orWhere('users.other_names', 'like', "%$search%")
        ->orWhere('users.email', 'like', "%$search%")
        ->orWhere('users.phone', 'like', "%$search%")
    );
  }

  protected function directQuery()
  {
    $this->baseQuery
      ->when(
        $this->requestGet('first_name'),
        fn($q, $value) => $q->where('users.first_name', 'like', "%$value%")
      )
      ->when(
        $this->requestGet('last_name'),
        fn($q, $value) => $q->where('users.last_name', 'like', "%$value%")
      )
      ->when(
        $this->requestGet('name'),
        fn($q, $value) => $q
          ->where('users.last_name', 'like', "%$value%")
          ->orWhere('users.first_name', 'like', "%$value%")
      )
      ->when(
        $this->requestGet('email'),
        fn($q, $value) => $q->where('users.email', 'like', "%$value%")
      );

    return $this;
  }
}
