<?php

namespace App\Http\Middleware;

use App\Enums\RoleType;
use Closure;
use Illuminate\Http\Request;

class VerifyAdmin
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle(Request $request, Closure $next)
  {
    $user = currentUser();

    if (!$user->hasRole(RoleType::Admin)) {
      return $this->eject($request, 'You are not an admin');
    }

    return $next($request);
  }

  private function eject(Request $request, string $message)
  {
    return $request->expectsJson() ? abort(403, $message) : redirect('/');
  }
}
