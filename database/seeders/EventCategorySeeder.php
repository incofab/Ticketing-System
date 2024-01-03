<?php
namespace Database\Seeders;

use App\Models\EventCategory;
use Illuminate\Database\Seeder;

class EventCategorySeeder extends Seeder
{
  const EVENT_CATEGORIES = [['title' => 'football', 'description' => null]];
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    foreach (self::EVENT_CATEGORIES as $key => $category) {
      EventCategory::query()->firstOrCreate(
        ['title' => $category['title']],
        $category
      );
    }
  }
}
