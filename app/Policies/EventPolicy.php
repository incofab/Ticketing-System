<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
  /**
   * Create a new policy instance.
   */
  public function __construct()
  {
    //
  }

  function update(User $user, Event $event)
  {
    return $user->id === $event->user_id || $user->isAdmin();
  }
}
