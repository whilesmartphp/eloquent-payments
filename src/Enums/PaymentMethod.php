<?php

namespace Whilesmart\Payments\Enums;

/**
 * String-backed but not exhaustive. The `method` column on the payments
 * table is a free-form string at the DB level so gateways can introduce
 * new rails without requiring a migration. Use these as canonical values
 * when your code knows the method up front.
 */
enum PaymentMethod: string
{
    case Card = 'card';
    case MobileMoney = 'mobile_money';
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Wallet = 'wallet';
    case Crypto = 'crypto';
    case Other = 'other';
}
