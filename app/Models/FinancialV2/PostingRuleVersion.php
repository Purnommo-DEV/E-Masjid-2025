<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostingRuleVersion extends FinancialV2Model
{
    protected $table = 'financial_v2_posting_rule_versions';

    protected $casts = ['effective_from' => 'date', 'effective_to' => 'date', 'approved_at' => 'datetime'];

    public const STRATEGY_RULE_LINES = 'rule_lines';

    public const STRATEGY_SOURCE_JOURNAL_REVERSAL = 'source_journal_reversal';

    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            if ((string) $version->getOriginal('compilation_strategy') === self::STRATEGY_SOURCE_JOURNAL_REVERSAL
                && in_array((string) $version->getOriginal('status'), ['effective', 'superseded'], true)
                && array_diff(array_keys($version->getDirty()), ['status', 'effective_to', 'updated_by_user_id', 'updated_at']) !== []) {
                throw new DomainException('Posting Rule Version reversal approved tidak dapat diubah langsung; gunakan successor governance.');
            }
        });
        static::deleting(function (self $version): void {
            if ($version->compilation_strategy === self::STRATEGY_SOURCE_JOURNAL_REVERSAL
                && in_array($version->status, ['effective', 'superseded'], true)) {
                throw new DomainException('Posting Rule Version reversal approved tidak dapat dihapus.');
            }
        });
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(PostingRule::class, 'posting_rule_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PostingRuleLine::class);
    }

    public function evidenceRequirements(): HasMany
    {
        return $this->hasMany(EvidenceRequirement::class);
    }
}
