<?php

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventSeason;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
  $this->admin = User::factory()->create();
  actingAs($this->admin);
});

test('it can list event categories', function () {
  EventCategory::factory()
    ->count(3)
    ->create();

  $response = getJson(route('api.event-categories.index'));

  $response->assertStatus(200)->assertJsonCount(3, 'data.data');
});

test('it can create event category', function () {
  $data = [
    'title' => 'Football',
    'description' => 'This is a football event category'
  ];

  $response = postJson(route('api.event-categories.store'), $data);

  $response->assertStatus(200)->assertJsonFragment($data);

  $this->assertDatabaseHas('event_categories', $data);
});

test('it cannot create event category with duplicate title', function () {
  $eventCategory = EventCategory::factory()->create(['title' => 'Football']);

  $data = [
    'title' => 'Football',
    'description' => 'This is a football event category'
  ];

  $response = postJson(route('api.event-categories.store'), $data);

  $response->assertStatus(422)->assertJsonValidationErrors('title');

  $this->assertDatabaseCount('event_categories', 1);
});

test('it can update event category', function () {
  $eventCategory = EventCategory::factory()->create();

  $data = [
    'title' => 'Basketball',
    'description' => 'This is a basketball event category'
  ];

  $response = putJson(
    route('api.event-categories.update', $eventCategory),
    $data
  );

  $response->assertStatus(200)->assertJsonFragment($data);

  $this->assertDatabaseHas('event_categories', $data);
});

test('it cannot update event category with duplicate title', function () {
  EventCategory::factory()->create(['title' => 'Football']);
  $eventCategory = EventCategory::factory()->create(['title' => 'Basketball']);

  $data = [
    'title' => 'Football',
    'description' => 'This is a basketball event category'
  ];

  $response = putJson(
    route('api.event-categories.update', $eventCategory),
    $data
  );

  $response->assertStatus(422)->assertJsonValidationErrors('title');

  $this->assertDatabaseMissing('event_categories', $data);
});

test('it can delete event category', function () {
  $eventCategory = EventCategory::factory()->create();

  deleteJson(
    route('api.event-categories.destroy', $eventCategory)
  )->assertStatus(200);

  $this->assertDatabaseMissing('event_categories', [
    'id' => $eventCategory->id
  ]);
});

test('it cannot delete event category with event seasons', function () {
  $eventCategory = EventCategory::factory()->create();
  EventSeason::factory()
    ->for($eventCategory)
    ->create();

  deleteJson(
    route('api.event-categories.destroy', $eventCategory)
  )->assertStatus(403);
  $this->assertDatabaseHas('event_categories', ['id' => $eventCategory->id]);
});

test('it validate title is required', function () {
  $response = postJson(route('api.event-categories.store'), [
    'description' => 'test'
  ]);
  $response->assertStatus(422)->assertJsonValidationErrors('title');
});

test('it validate title is string', function () {
  $response = postJson(route('api.event-categories.store'), [
    'title' => 1234,
    'description' => 'test'
  ]);
  $response->assertStatus(422)->assertJsonValidationErrors('title');
});

test('it validate title max length', function () {
  $response = postJson(route('api.event-categories.store'), [
    'title' => str_repeat('a', 256),
    'description' => 'test'
  ]);
  $response->assertStatus(422)->assertJsonValidationErrors('title');
});

test('it validate description is string', function () {
  $response = postJson(route('api.event-categories.store'), [
    'title' => 'test',
    'description' => 1234
  ]);
  $response->assertStatus(422)->assertJsonValidationErrors('description');
});
