<?php

use App\DTO\BreadCrumb;
use App\Models\User;
use App\Support\Res;

if (!function_exists('currentUser')) {
  function currentUser(): User|null
  {
    /** @var User */
    $user = auth()->user();
    return $user;
  }
}

if (!function_exists('isProduction')) {
  function isProduction(): bool
  {
    return app()->environment('production');
  }
}

if (!function_exists('isLocal')) {
  function isLocal(): bool
  {
    return app()->environment('local');
  }
}

if (!function_exists('isTesting')) {
  function isTesting(): bool
  {
    return app()->environment('testing');
  }
}

if (!function_exists('removeHyphenAndCapitalize')) {
  function removeHyphenAndCapitalize($string): string
  {
    return ucwords(str_replace('-', ' ', $string));
  }
}

if (!function_exists('paginateFromRequest')) {
  function paginateFromRequest(
    $query,
    $defaultPerPage = 100
  ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
    $perPage = request()->query('perPage', $defaultPerPage);
    $page = request()->query('page');

    return $query->paginate(perPage: (int) $perPage, page: (int) $page);
  }
}

if (!function_exists('failRes')) {
  function failRes($message, array $data = []): Res
  {
    return new Res(['success' => false, 'message' => $message, ...$data]);
  }
}

if (!function_exists('successRes')) {
  function successRes($message = '', array $data = []): Res
  {
    return new Res(['success' => true, 'message' => $message, ...$data]);
  }
}

if (!function_exists('dlog')) {
  /** Helper to log data using json encode with pretty print */
  function dlog($data)
  {
    info(json_encode($data, JSON_PRETTY_PRINT));
  }
}

if (!function_exists('breadCrumb')) {
  function breadCrumb(
    string $title,
    string $route = '',
    string $icon = '',
    bool $active = false
  ) {
    return new BreadCrumb($title, $route, $icon, $active);
  }
}
