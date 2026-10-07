<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\FinancialTransaction;
use Illuminate\Support\Collection;

final class TransactionEvidenceStatusService
{
    /** @var array<string, string> */
    public const LABELS = [
        'receipt' => 'Tanda Terima',
        'invoice' => 'Invoice',
        'transfer_proof' => 'Bukti Transfer',
        'statement' => 'Rekening Koran',
        'cash_count' => 'Perhitungan Kas',
        'approval' => 'Persetujuan',
        'policy' => 'Dokumen Kebijakan',
        'other' => 'Lampiran Umum',
    ];

    /**
     * @param Collection<int, mixed> $requirements
     * @return array{complete: bool, required: array<int, array<string, mixed>>, actual: array<int, array<string, mixed>>, files: array<int, array<string, mixed>>, missing: array<int, array<string, mixed>>, suggested_type: ?string}
     */
    public function evaluate(FinancialTransaction $transaction, Collection $requirements): array
    {
        $links = AttachmentLink::query()
            ->where('financial_v2_attachment_links.accounting_entity_id', $transaction->accounting_entity_id)
            ->where('financial_v2_attachment_links.target_type', 'transaction')
            ->where('financial_v2_attachment_links.target_id', $transaction->id)
            ->where('financial_v2_attachment_links.status', 'active')
            ->join('financial_v2_attachments as attachment', 'attachment.id', '=', 'financial_v2_attachment_links.attachment_id')
            ->orderBy('financial_v2_attachment_links.created_at')
            ->get([
                'financial_v2_attachment_links.id as link_id',
                'financial_v2_attachment_links.evidence_type',
                'financial_v2_attachment_links.status',
                'attachment.id as attachment_id',
                'attachment.original_filename',
            ]);

        $counts = $links->countBy('evidence_type');
        $required = $requirements->map(function ($requirement) use ($counts): array {
            $type = (string) $requirement->evidence_type;
            $minimum = (int) $requirement->minimum_count;
            $count = (int) $counts->get($type, 0);

            return [
                'type' => strtoupper($type),
                'value' => $type,
                'label' => $this->label($type),
                'minimum' => $minimum,
                'count' => $count,
                'complete' => $count >= $minimum,
            ];
        })->values();

        $actual = $counts->map(fn (int $count, string $type): array => [
            'type' => strtoupper($type),
            'value' => $type,
            'label' => $this->label($type),
            'count' => $count,
        ])->values();

        $files = $links->map(fn ($link): array => [
            'id' => $link->attachment_id,
            'link_id' => $link->link_id,
            'filename' => $link->original_filename,
            'type' => strtoupper((string) $link->evidence_type),
            'value' => $link->evidence_type,
            'label' => $this->label((string) $link->evidence_type),
            'status' => $link->status,
            'status_label' => $link->status === 'active' ? 'Aktif' : ucfirst(str_replace('_', ' ', $link->status)),
        ])->values();
        $missing = $required->where('complete', false)->values();

        return [
            'complete' => $missing->isEmpty(),
            'required' => $required->all(),
            'actual' => $actual->all(),
            'files' => $files->all(),
            'missing' => $missing->all(),
            'suggested_type' => $missing->count() === 1 ? $missing->first()['value'] : null,
        ];
    }

    public function label(string $type): string
    {
        return self::LABELS[$type] ?? str($type)->replace('_', ' ')->title()->toString();
    }
}
