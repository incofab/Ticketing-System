<?php

namespace App\Support\UITableFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

abstract class BaseUITableFilter
{
  private $calledFns = [];
  private string $table;

  const DEFAULT_SORT_COLUMNS = [
    'createdAt' => 'created_at'
  ];

  public function __construct(
    protected array $requestData,
    protected Builder $baseQuery
  ) {
    $this->validateRequestData();
    $this->table = $baseQuery->getModel()->getTable();
    $this->dateFilter(
      "{$this->table}.created_at",
      $this->requestGet('date_from'),
      $this->requestGet('date_to')
    );
  }

  protected function extraValidationRules(): array
  {
    return [];
  }

  protected function getSortableColumns(): array
  {
    return [];
  }

  public static function make(array $requestData, Builder $baseQuery): static
  {
    return new static($requestData, $baseQuery);
  }

  private function validateRequestData(): static
  {
    $this->requestData = Validator::validate($this->requestData, [
      'sortDir' => [
        'required_with:sortKey',
        'string',
        Rule::in(['ASC', 'DESC', 'asc', 'desc'])
      ],
      'sortKey' => ['required_with:sortDir', 'string'],
      'search' => ['nullable', 'string'],
      'date_from' => ['nullable', 'date'],
      'date_to' => ['nullable', 'date'],
      ...$this->extraValidationRules()
    ]);

    return $this;
  }

  public function sortQuery(): static
  {
    $sortDir = $this->requestData['sortDir'] ?? null;
    $sortKey = $this->requestData['sortKey'] ?? null;
    $columnName =
      [...self::DEFAULT_SORT_COLUMNS, ...$this->getSortableColumns()][
        $sortKey
      ] ?? null;

    if (empty($columnName)) {
      return $this;
    }

    $this->baseQuery->orderBy($columnName, $sortDir);

    return $this;
  }
  public function getQuery()
  {
    return $this->baseQuery;
  }

  /** Handle searches from the url request params */
  abstract protected function directQuery();

  /** Perform a search */
  abstract protected function generalSearch(string $search);

  public function filterQuery(): static
  {
    return $this->directQuery()->when(
      $this->requestGet('search'),
      fn(self $that, $search) => $that->generalSearch($search)
    );
  }

  protected function dateFilter($columnName, $dateFrom, $dateTo)
  {
    // $columnName = "{$this->table}.created_at";
    if ($dateFrom && $dateTo) {
      $this->baseQuery->whereBetween($columnName, [$dateFrom, $dateTo]);
    } elseif ($dateFrom) {
      $this->baseQuery->where($columnName, '>', $dateFrom);
    } elseif ($dateTo) {
      $this->baseQuery->where($columnName, '<', $dateTo);
    }
    return $this;
  }

  protected function requestGet($key)
  {
    return $this->requestData[$key] ?? null;
  }

  protected function when($value, callable $callback): static
  {
    if ($value) {
      $callback($this, $value);
    }
    return $this;
  }

  protected function callOnce($key, callable $callback): static
  {
    if (!in_array($key, $this->calledFns)) {
      $this->calledFns[] = $key;
      $callback();
    }
    return $this;
  }
}
