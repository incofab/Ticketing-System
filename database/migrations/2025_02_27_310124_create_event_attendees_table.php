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
    Schema::create('event_attendees', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('event_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('ticket_id')
        ->constrained()
        ->cascadeOnDelete();

      $table->string('name')->nullable();
      $table->string('phone')->nullable();
      $table->string('email')->nullable();
      $table->string('image')->nullable();
      $table->string('address')->nullable();

      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('event_attendees');
  }
};
