<?php
namespace App\Models\FinancialV2;
use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
final class ReplacementRealizationLineage extends FinancialV2Model
{
    public $timestamps = false;
    protected $table = 'financial_v2_replacement_realization_lineages';
    protected $casts = ['lineage_snapshot' => 'array', 'created_at' => 'datetime'];
    protected static function booted(): void { static::creating(fn () => throw new DomainException('Lineage hanya dapat dibuat workflow resmi.')); static::updating(fn () => throw new DomainException('Lineage Replacement Realization bersifat write-once.')); static::deleting(fn () => throw new DomainException('Lineage disimpan permanen.')); }
    public function originalRealization(): BelongsTo { return $this->belongsTo(FundRealization::class, 'original_realization_id'); }
    public function replacementRealization(): BelongsTo { return $this->belongsTo(FundRealization::class, 'replacement_realization_id'); }
    public function distributionLineage(): HasOne { return $this->hasOne(DistributionCorrectionLineage::class, 'replacement_realization_lineage_id'); }
}
