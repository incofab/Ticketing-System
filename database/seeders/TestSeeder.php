<?php
namespace Database\Seeders;

use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    Ticket::factory(5)->create();
  }
}
