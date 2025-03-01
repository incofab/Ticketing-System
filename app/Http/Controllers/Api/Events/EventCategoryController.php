<?php

namespace App\Http\Controllers\Api\Events;

use App\Http\Controllers\Controller;
use App\Models\EventCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Event Category
 */
class EventCategoryController extends Controller
{
  public function index()
  {
    return $this->apiRes(paginateFromRequest(EventCategory::query()));
  }

  public function store(Request $request)
  {
    $data = $request->validate([
      'title' => [
        'required',
        'string',
        'max:255',
        'unique:event_categories,title'
      ],
      'description' => ['nullable', 'string']
    ]);

    $eventCategory = EventCategory::query()->create($data);
    return $this->apiRes($eventCategory);
  }

  public function update(Request $request, EventCategory $eventCategory)
  {
    $data = $request->validate([
      'title' => [
        'required',
        'string',
        'max:255',
        Rule::unique('event_categories', 'title')->ignore(
          $eventCategory->id,
          'id'
        )
      ],
      'description' => ['nullable', 'string']
    ]);

    $eventCategory->fill($data)->save();
    return $this->apiRes($eventCategory);
  }

  public function destroy(EventCategory $eventCategory)
  {
    abort_if(
      $eventCategory->eventSeasons()->exists(),
      403,
      'Cannot delete a Category that contain some event seasons'
    );
    $eventCategory->delete();
    return $this->message('Event Category deleted successfully');
  }
}
