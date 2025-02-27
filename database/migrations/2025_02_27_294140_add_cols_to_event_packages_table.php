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
    Schema::table('event_packages', function (Blueprint $table) {
      $table->string('title');
      $table->text('notes')->nullable();
      $table->unsignedInteger('capacity')->default(0);
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('event_packages', function (Blueprint $table) {
      $table->dropColumn('title', 'notes', 'capacity');
    });
  }
};
