<?php
use App\Models\Event;
use App\Models\EventImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->admin = User::factory()->create();
  Storage::fake();
});

it('can store a new event image', function () {
  $event = Event::factory()->create();
  $imageFile = UploadedFile::fake()->image('event_image.jpg');

  $response = actingAs($this->admin)->postJson(
    route('api.event-images.store'),
    [
      'event_id' => $event->id,
      'image' => $imageFile,
      'reference' => 'unique_reference'
    ]
  );
  $response
    ->assertOk()
    ->assertJsonFragment(['reference' => 'unique_reference']);

  $this->assertDatabaseHas('event_images', ['reference' => 'unique_reference']);

  Storage::disk('s3_public')->assertExists(
    "event_{$event->id}/{$imageFile->hashName()}"
  );
});

it('can delete an existing event image', function () {
  $eventImage = EventImage::factory()->create();
  $response = actingAs($this->admin)->postJson(
    route('api.event-images.destroy', ['eventImage' => $eventImage->id])
  );

  $response
    ->assertOk()
    ->assertJson(['message' => 'Event image deleted successfully']);

  $this->assertDatabaseMissing('event_images', ['id' => $eventImage->id]);
  Storage::disk('s3_public')->assertMissing($eventImage->image);
});
