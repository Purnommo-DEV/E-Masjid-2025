<?php

namespace App\Models\FinancialV2;

class ExceptionLog extends FinancialV2Model
{
    protected $table = 'financial_v2_exception_logs';

    public $timestamps = false;

    protected $casts = ['event_at' => 'datetime', 'created_at' => 'datetime'];
}
