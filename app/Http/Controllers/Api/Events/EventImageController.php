<?php

namespace App\Http\Controllers\Api\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventImage;
use Illuminate\Http\Request;
use Storage;

/**
 * @group Event Images
 */
class EventImageController extends Controller
{
  function index(Event $event)
  {
    return $this->apiRes(
      paginateFromRequest($event->eventImages()->getQuery())
    );
  }

  public function store(Event $event, Request $request)
  {
    $data = $request->validate([
      'images' => ['required', 'array', 'min:1', 'max:5'],
      // 'images.*.event_id' => ['required', 'exists:events,id'],
      'images.*.image' => ['required', 'file'],
      'images.*.reference' => [
        'required',
        'string',
        'unique:event_images,reference'
      ]
    ]);
    foreach ($data['images'] as $key => $image) {
      $eventImage = $event
        ->eventImages()
        ->create([...$image, 'user_id' => currentUser()?->id, 'image' => null]);
      $imagePath = $image['image']->store("event_{$event->id}", 's3_public');
      $publicUrl = Storage::disk('s3_public')->url($imagePath);
      $eventImage->fill(['image' => $publicUrl])->save();
    }
    return $this->apiRes($eventImage);
  }

  public function destroy(EventImage $eventImage)
  {
    $eventImage->delete();
    return $this->message('Event image deleted successfully');
  }
}
