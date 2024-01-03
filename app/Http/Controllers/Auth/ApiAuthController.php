<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Authentication
 */
class ApiAuthController extends Controller
{
  public function register(Request $request)
  {
    $data = $request->validate(User::generalRule());
    $user = User::create([...$data, 'password' => bcrypt($data['password'])]);

    $token = $user->createToken(User::API_ACCESS_TOKEN_NAME)->accessToken;

    return $this->ok(['token' => $token]);
  }

  public function login(Request $request)
  {
    $data = $request->validate([
      'phone' => ['required', 'string'],
      'password' => ['required', 'string']
    ]);

    if (!Auth::attempt($data)) {
      return $this->message('Unauthenticated', 401);
    }

    $token = currentUser()->createToken(User::API_ACCESS_TOKEN_NAME)
      ->accessToken;
    return $this->ok(['token' => $token]);
  }

  public function logout(Request $request)
  {
    $request
      ->user()
      ->token()
      ->revoke();
    return $this->message('Successfully logged out');
  }
}
