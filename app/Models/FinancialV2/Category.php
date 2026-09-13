<?php

namespace App\Models\FinancialV2;

class Category extends FinancialV2Model
{
    protected $table = 'financial_v2_categories';

    protected $casts = ['valid_from' => 'date', 'valid_to' => 'date'];
}
