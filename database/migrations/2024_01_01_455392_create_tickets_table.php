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
    Schema::create('tickets', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('ticket_payment_id');
      $table->unsignedBigInteger('event_package_id');
      $table->unsignedBigInteger('seat_id');
      $table->string('reference')->unique();
      $table->text('qr_code')->nullable();

      $table->unsignedBigInteger('user_id')->nullable(true);
      // $table->string('name')->nullable();
      // $table->string('phone')->nullable();
      // $table->string('email')->nullable();

      // $table->timestamp('ticket_verified_at')->nullable();

      $table->timestamps();

      $table
        ->foreign('ticket_payment_id')
        ->references('id')
        ->on('ticket_payments')
        ->cascadeOnDelete();
      $table
        ->foreign('event_package_id')
        ->references('id')
        ->on('event_packages')
        ->cascadeOnDelete();
      $table
        ->foreign('seat_id')
        ->references('id')
        ->on('seats')
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
    Schema::dropIfExists('tickets');
  }
};
