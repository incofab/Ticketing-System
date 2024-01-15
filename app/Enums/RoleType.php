<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum RoleType: string
{
  use EnumToArray;

  case Admin = 'admin';
  case Manager = 'manager';
}
