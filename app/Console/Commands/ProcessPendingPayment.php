<?php

namespace App\Console\Commands;

use App\Enums\PaymentReferenceStatus;
use App\Models\PaymentReference;
use App\Support\Payment\Processor\PaymentProcessor;
use Illuminate\Console\Command;

class ProcessPendingPayment extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'app:process-pending-payments';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Process all pending payments';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $paymentReferences = PaymentReference::query()
      ->where('status', PaymentReferenceStatus::Pending)
      ->where('created_at', '<', now()->subMinutes(10))
      ->take(10)
      ->get();

    foreach ($paymentReferences as $key => $paymentReference) {
      $this->comment(
        "Running payment ref id = {$paymentReference->id}, index = $key"
      );
      PaymentProcessor::make($paymentReference)->handleCallback();
    }

    $this->comment('Payment processing completed');
    return Command::SUCCESS;
  }
}
