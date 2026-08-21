<?php

namespace App\Domain\FinancialV2;

/** Controlled transaction families from the approved policy catalogue. */
enum TransactionTypeCode: string
{
    case Receipt = 'RCV';
    case Payment = 'PAY';
    case TreasuryTransfer = 'TRF';
    case InterfundTransfer = 'IFT';
    case OpeningBalance = 'OPB';
    case Reversal = 'REV';
    case Adjustment = 'ADJ';
}
