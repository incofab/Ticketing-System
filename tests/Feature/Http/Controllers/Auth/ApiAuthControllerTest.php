<?php
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

it('can register a user', function () {
  $data = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'phone' => '1234567890',
    'email' => 'john@example.com',
    'password' => 'password123'
  ];

  postJson(route('api.register'), $data)
    ->assertStatus(200)
    ->assertJsonStructure(['token']);
});

it('can log in a user', function () {
  $user = User::factory()->create([
    'phone' => '1234567890',
    'password' => bcrypt('password123')
  ]);

  $data = [
    'phone' => '1234567890',
    'password' => 'password123'
  ];

  $response = postJson(route('api.login'), $data);

  $response->assertStatus(200)->assertJsonStructure(['token']);
});

it('can log out a user', function () {
  $user = User::factory()->create();
  $token = $user->createToken(User::API_ACCESS_TOKEN_NAME)->plainTextToken;

  $response = actingAs($user)->getJson(route('api.logout'), [
    'Authorization' => 'Bearer ' . $token
  ]);

  $response
    ->assertStatus(200)
    ->assertJson(['message' => 'Successfully logged out']);
});

it('cannot log in with invalid credentials', function () {
  $data = [
    'phone' => 'invalid_phone',
    'password' => 'invalid_password'
  ];

  postJson(route('api.login'), $data)
    ->assertStatus(401)
    ->assertJson(['message' => 'Unauthenticated']);
});
