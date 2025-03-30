<?php

namespace App\Enums;

enum PaymentMerchantType: string
{
  case Paystack = 'paystack';
  case BankDeposit = 'bank-deposit';
  case Airvend = 'airvend';
  case Paydestal = 'paydestal';
}
