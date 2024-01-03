<?php

use App\Models\EventCategory;
use App\Models\EventSeason;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
  $this->admin = User::factory()->create();
});

it('can get a list of event seasons', function () {
  $eventCategory = EventCategory::factory()->create();
  $eventSeasons = EventSeason::factory(5)->create([
    'event_category_id' => $eventCategory->id
  ]);

  $response = getJson(
    route('api.event-seasons.index', ['eventCategory' => $eventCategory->id])
  );
  $response->assertOk()->assertJsonCount(5, 'data.data');
});

it('can get a list of upcoming event seasons', function () {
  $eventCategory = EventCategory::factory()->create();
  $eventSeasons = EventSeason::factory(5)->create([
    'event_category_id' => $eventCategory->id,
    'date_from' => now()->addDays(1)
  ]);

  $response = getJson(
    route('api.event-seasons.upcoming', ['eventCategory' => $eventCategory->id])
  );

  $response->assertOk()->assertJsonCount(5, 'data.data');
});

it('can store a new event season', function () {
  $eventCategory = EventCategory::factory()->create();

  $response = actingAs($this->admin)->postJson(
    route('api.event-seasons.store', ['eventCategory' => $eventCategory->id]),
    [
      'title' => 'New Event Season',
      'description' => 'Event season description',
      'date_from' => now(),
      'date_to' => now()->addDays(5)
    ]
  );

  $response->assertOk()->assertJsonFragment(['title' => 'New Event Season']);

  $this->assertDatabaseHas('event_seasons', [
    'title' => 'New Event Season',
    'event_category_id' => $eventCategory->id
  ]);
});

it('can update an existing event season', function () {
  $eventSeason = EventSeason::factory()->create();
  $response = actingAs($this->admin)->postJson(
    route('api.event-seasons.update', ['eventSeason' => $eventSeason->id]),
    [
      'title' => 'Updated Event Season',
      'description' => 'Updated description',
      'date_from' => now(),
      'date_to' => now()->addDays(7)
    ]
  );
  $response
    ->assertOk()
    ->assertJsonFragment(['title' => 'Updated Event Season']);
});

it('can delete an existing event season', function () {
  $eventSeason = EventSeason::factory()->create();
  $response = actingAs($this->admin)->postJson(
    route('api.event-seasons.destroy', ['eventSeason' => $eventSeason->id])
  );

  $response
    ->assertOk()
    ->assertJson(['message' => 'Event season deleted successfully']);

  $this->assertDatabaseMissing('event_seasons', ['id' => $eventSeason->id]);
});
