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
    Schema::create('users', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->string('first_name');
      $table->string('last_name');
      $table->string('other_names')->nullable();
      $table->string('phone')->unique();
      $table->string('photo')->nullable();
      $table->string('email')->nullable();
      $table->string('gender')->nullable();
      $table->string('dob')->nullable();
      $table->timestamp('email_verified_at')->nullable();
      $table->timestamp('phone_verified_at')->nullable();
      $table->string('password');
      $table->softDeletes();

      $table->rememberToken();
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
    Schema::dropIfExists('users');
  }
};
