<?php

namespace App\Models\FinancialV2;

use DomainException;

class EvidenceRequirement extends FinancialV2Model
{
    protected $table = 'financial_v2_evidence_requirements';

    protected static function booted(): void
    {
        static::creating(function (self $requirement): void {
            $version = PostingRuleVersion::query()->find($requirement->posting_rule_version_id);
            if ($version?->compilation_strategy === PostingRuleVersion::STRATEGY_SOURCE_JOURNAL_REVERSAL
                && $version->status !== 'draft') {
                throw new DomainException('Evidence Requirement reversal hanya dapat ditambahkan pada Posting Rule Version draft.');
            }
        });
        $assertMutable = function (self $requirement): void {
            $version = $requirement->version;
            if ($version?->compilation_strategy === PostingRuleVersion::STRATEGY_SOURCE_JOURNAL_REVERSAL
                && in_array($version->status, ['effective', 'superseded'], true)) {
                throw new DomainException('Evidence Requirement reversal pada Posting Rule Version approved bersifat immutable.');
            }
        };
        static::updating($assertMutable);
        static::deleting($assertMutable);
    }

    public function version(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PostingRuleVersion::class, 'posting_rule_version_id');
    }
}
