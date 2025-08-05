<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('coupons', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('event_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();
      $table->string('code');
      $table->string('discount_type');
      $table->float('discount_value', 10, 2);
      $table
        ->float('min_purchase', 10, 2)
        ->default(0)
        ->nullable();
      $table
        ->unsignedInteger('usage_limit')
        ->default(0)
        ->nullable()
        ->comment('How many times the coupon can be used in total.');
      $table->unsignedInteger('usage_count')->default(0);
      $table->timestamp('expires_at')->nullable();
      $table->timestamps();
    });

    Schema::create('coupon_event_package', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('coupon_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('event_package_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->timestamps();
    });

    Schema::table('ticket_payments', function (Blueprint $table) {
      $table
        ->foreignId('coupon_id')
        ->nullable()
        ->after('event_package_id')
        ->constrained()
        ->nullOnDelete();
      $table
        ->float('amount', 10, 2)
        ->nullable()
        ->after('quantity')
        ->comment('The final amount after applying the coupon discount.');
      $table
        ->float('original_amount', 10, 2)
        ->nullable()
        ->after('amount');
      $table
        ->float('discount_amount', 10, 2)
        ->nullable()
        ->default(0)
        ->after('original_amount');
      $table->json('receivers')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ticket_payments', function (Blueprint $table) {
      $table->dropForeign(['coupon_id']);
      $table->dropColumn([
        'coupon_id',
        'original_amount',
        'discount_amount',
        'amount',
        'receivers'
      ]);
    });
    Schema::dropIfExists('coupon_event_package');
    Schema::dropIfExists('coupons');
  }
};
