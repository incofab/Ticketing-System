<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('events', function (Blueprint $table) {
      $table->id();

      $table->unsignedBigInteger('event_season_id');
      $table->string('title');
      $table->text('description')->nullable();
      $table->dateTime('start_time');
      $table->dateTime('end_time')->nullable(true);
      $table->string('home_team')->nullable(true);
      $table->string('away_team')->nullable(true);

      $table->timestamps();

      $table
        ->foreign('event_season_id')
        ->references('id')
        ->on('event_seasons')
        ->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('events');
  }
};
