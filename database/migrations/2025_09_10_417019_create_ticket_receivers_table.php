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
    Schema::table('events', function (Blueprint $table) {
      $table->json('meta')->nullable();
    });

    Schema::create('ticket_receivers', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('ticket_payment_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->string('name')->nullable();
      $table->string('phone')->nullable();
      $table->string('email')->nullable();
      $table->json('meta')->nullable();

      $table->timestamps();
    });

    Schema::table('tickets', function (Blueprint $table) {
      $table
        ->foreignId('ticket_receiver_id')
        ->nullable()
        ->constrained();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('tickets', function (Blueprint $table) {
      $table->dropForeign(['ticket_receiver_id']);
      $table->dropColumn('ticket_receiver_id');
    });

    Schema::dropIfExists('ticket_receivers');

    Schema::table('events', function (Blueprint $table) {
      $table->dropColumn('meta');
    });
  }
};
