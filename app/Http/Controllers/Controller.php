<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
  use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

  function apiRes($data = [], string $message = '', $httpStatusCode = 200)
  {
    $arr = [
      'message' => $message,
      'data' => $data
    ];

    return response()->json($arr, $httpStatusCode);
  }

  protected function ok($data = [])
  {
    return response()->json($data);
  }

  protected function message(string $message, $statusCode = 200)
  {
    return response()->json(['message' => $message], $statusCode);
  }
}
