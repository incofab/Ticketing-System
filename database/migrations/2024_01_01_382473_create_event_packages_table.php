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
    Schema::create('event_packages', function (Blueprint $table) {
      $table->id();

      $table->unsignedBigInteger('event_id');
      $table->unsignedBigInteger('seat_section_id');
      $table->float('price', 10, 2);
      $table->unsignedInteger('quantity_sold')->default(0);
      $table->softDeletes();
      $table->timestamps();

      $table
        ->foreign('event_id')
        ->references('id')
        ->on('events')
        ->cascadeOnDelete();
      $table
        ->foreign('seat_section_id')
        ->references('id')
        ->on('seat_sections')
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
    Schema::dropIfExists('event_packages');
  }
};
