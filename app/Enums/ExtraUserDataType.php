<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum ExtraUserDataType: string
{
  use EnumToArray;

  case Text = 'text';
  case LongText = 'long-text';
  case Integer = 'integer';
  case Float = 'float';
}
