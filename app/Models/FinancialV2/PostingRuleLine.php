<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostingRuleLine extends FinancialV2Model
{
    protected $table = 'financial_v2_posting_rule_lines';

    protected static function booted(): void
    {
        static::creating(function (self $line): void {
            $version = PostingRuleVersion::query()->find($line->posting_rule_version_id);
            if ($version?->compilation_strategy === PostingRuleVersion::STRATEGY_SOURCE_JOURNAL_REVERSAL) {
                throw new DomainException('Source-journal reversal tidak menerima Posting Rule Lines.');
            }
        });
        static::updating(function (self $line): void {
            if ($line->version?->compilation_strategy === PostingRuleVersion::STRATEGY_SOURCE_JOURNAL_REVERSAL) {
                throw new DomainException('Posting Rule Lines tidak dapat ditambahkan atau diubah pada source-journal reversal.');
            }
        });
        static::deleting(function (self $line): void {
            if ($line->version?->compilation_strategy === PostingRuleVersion::STRATEGY_SOURCE_JOURNAL_REVERSAL) {
                throw new DomainException('Konfigurasi source-journal reversal tidak mempunyai lifecycle Posting Rule Lines.');
            }
        });
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(PostingRuleVersion::class, 'posting_rule_version_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
