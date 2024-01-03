<?php

use App\Enums\SeatStatus;
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
    Schema::create('seats', function (Blueprint $table) {
      $table->id();

      $table->unsignedBigInteger('seat_section_id');
      $table->string('seat_no');
      $table->text('description')->nullable();
      $table->text('features')->nullable();
      $table->string('status')->default(SeatStatus::Available);
      $table->timestamps();

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
    Schema::dropIfExists('seats');
  }
};
