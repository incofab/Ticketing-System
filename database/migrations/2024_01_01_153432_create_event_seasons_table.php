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
    Schema::create('event_seasons', function (Blueprint $table) {
      $table->id();

      $table->unsignedBigInteger('event_category_id');
      $table->string('title');
      $table->text('description')->nullable();
      $table->dateTime('date_from')->nullable(true);
      $table->dateTime('date_to')->nullable(true);
      $table->string('status')->nullable(true);

      $table->timestamps();

      $table
        ->foreign('event_category_id')
        ->references('id')
        ->on('event_categories')
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
    Schema::dropIfExists('event_seasons');
  }
};
