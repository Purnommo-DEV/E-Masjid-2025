<?php

namespace App\Models\FinancialV2;

class IdempotencyKey extends FinancialV2Model
{
    protected $table = 'financial_v2_idempotency_keys';

    protected $casts = ['expires_at' => 'datetime'];
}
