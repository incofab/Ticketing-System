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
    Schema::create('ticket_payments', function (Blueprint $table) {
      $table->id();
      $table->unsignedInteger('quantity');
      $table->unsignedBigInteger('event_package_id');

      $table->unsignedBigInteger('user_id')->nullable(true);
      $table->string('name')->nullable();
      $table->string('phone')->nullable();
      $table->string('email')->nullable();

      $table->timestamps();

      $table
        ->foreign('event_package_id')
        ->references('id')
        ->on('event_packages')
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
    Schema::dropIfExists('ticket_payments');
  }
};
