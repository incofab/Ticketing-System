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
    Schema::create('ticket_verifications', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('ticket_id');
      $table->unsignedBigInteger('user_id');
      $table->string('reference');
      $table->string('device_no')->index();

      $table->timestamps();

      $table
        ->foreign('ticket_id')
        ->references('id')
        ->on('tickets')
        ->cascadeOnDelete();
      $table
        ->foreign('user_id')
        ->references('id')
        ->on('users')
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
    Schema::dropIfExists('ticket_verifications');
  }
};
