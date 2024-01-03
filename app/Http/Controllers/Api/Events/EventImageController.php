<?php

namespace App\Http\Controllers\Api\Events;

use App\Http\Controllers\Controller;
use App\Models\EventImage;
use Illuminate\Http\Request;
use Storage;

/**
 * @group Event Images
 */
class EventImageController extends Controller
{
  public function store(Request $request)
  {
    $data = $request->validate([
      'event_id' => ['required', 'exists:events,id'],
      'image' => ['required', 'file'],
      'reference' => ['required', 'string', 'unique:event_images,reference']
    ]);
    $eventImage = EventImage::query()->create([...$data, 'image' => null]);
    $imagePath = $request->image->store(
      "event_{$data['event_id']}",
      's3_public'
    );
    $publicUrl = Storage::disk('s3_public')->url($imagePath);
    $eventImage->fill(['image' => $publicUrl])->save();
    return $this->apiRes($eventImage);
  }

  public function destroy(EventImage $eventImage)
  {
    $eventImage->delete();
    return $this->message('Event image deleted successfully');
  }
}
