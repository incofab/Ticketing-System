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
      $table->string('facebook')->nullable();
      $table->string('instagram')->nullable();
      $table->string('tiktok')->nullable();
      $table->string('twitter')->nullable();
      $table->string('youtube')->nullable();
      $table->string('linkedin')->nullable();
      $table->string('logo')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('events', function (Blueprint $table) {
      $table->dropColumn(
        'facebook',
        'instagram',
        'tiktok',
        'twitter',
        'youtube',
        'linkedin',
        'logo'
      );
    });
  }
};
