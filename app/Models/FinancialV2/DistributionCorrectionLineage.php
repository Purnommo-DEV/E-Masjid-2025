<?php
namespace App\Models\FinancialV2;
use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class DistributionCorrectionLineage extends FinancialV2Model
{
    public $timestamps = false;
    protected $table = 'financial_v2_distribution_correction_lineages';
    protected $casts = ['distribution_snapshot' => 'array', 'created_at' => 'datetime'];
    protected static function booted(): void { static::creating(fn () => throw new DomainException('Overlay Distribution hanya dapat dibuat workflow resmi.')); static::updating(fn () => throw new DomainException('Overlay Distribution bersifat immutable.')); static::deleting(fn () => throw new DomainException('Overlay Distribution disimpan permanen.')); }
    public function originalDistribution(): BelongsTo { return $this->belongsTo(Distribution::class, 'original_distribution_id'); }
    public function replacementLineage(): BelongsTo { return $this->belongsTo(ReplacementRealizationLineage::class, 'replacement_realization_lineage_id'); }
}
