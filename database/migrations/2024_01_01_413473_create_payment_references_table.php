<?php

use App\Enums\PaymentMerchantType;
use App\Enums\PaymentReferenceStatus;
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
    Schema::create('payment_references', function (Blueprint $table) {
      $table->id();
      $table->morphs('paymentable'); // TicketPayments etc...
      $table->unsignedBigInteger('user_id')->nullable(true);
      $table->string('reference')->unique();
      $table->string('merchant')->default(PaymentMerchantType::Paystack);

      $table->float('amount', 10, 2)->default(0);
      $table->float('charge', 10, 2)->default(0);
      $table->text('content')->nullable(true);
      $table->string('status')->default(PaymentReferenceStatus::Pending);

      $table->timestamps();

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
    Schema::dropIfExists('payment_references');
  }
};
