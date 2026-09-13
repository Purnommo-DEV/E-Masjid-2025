<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Attachment;
use App\Models\FinancialV2\AttachmentLink;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Normalizes uploaded transaction evidence into the existing Attachment and
 * AttachmentLink architecture. It never writes any accounting fact.
 */
final class TransactionEvidenceUploadService
{
    private const IMAGE_MEDIA_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    private const MAX_LONG_EDGE = 2400;

    private const WEBP_QUALITY = 84;

    public function __construct(private readonly EvidenceService $evidence) {}

    public function attach(
        string $entityId,
        string $transactionId,
        UploadedFile $upload,
        string $evidenceType,
        ?int $actorUserId = null,
    ): AttachmentLink {
        return $this->attachToMany($entityId, [$transactionId], $upload, $evidenceType, $actorUserId)->sole();
    }

    /**
     * Stores one physical evidence object and links it to every transaction.
     *
     * @param  array<int, string>  $transactionIds
     * @return Collection<int, AttachmentLink>
     */
    public function attachToMany(
        string $entityId,
        array $transactionIds,
        UploadedFile $upload,
        string $evidenceType,
        ?int $actorUserId = null,
    ): Collection {
        $transactionIds = array_values(array_unique($transactionIds));
        if ($transactionIds === []) {
            throw new FinancialDomainException('E-ATTACHMENT-TARGET', 'Evidence batch requires at least one transaction.');
        }

        $prepared = $this->prepareUpload($entityId, $upload);
        $createdStorageObject = ! Storage::disk('local')->exists($prepared['storage_reference']);
        if ($createdStorageObject) {
            Storage::disk('local')->put($prepared['storage_reference'], $prepared['contents']);
        }

        try {
            return DB::transaction(function () use ($entityId, $transactionIds, $evidenceType, $actorUserId, $prepared): Collection {
                return collect($transactionIds)->map(fn (string $transactionId): AttachmentLink => $this->evidence->attachToTransaction(
                    $entityId,
                    $transactionId,
                    $prepared['original_filename'],
                    $prepared['stored_media_type'],
                    strlen($prepared['contents']),
                    $prepared['content_hash'],
                    $prepared['storage_reference'],
                    $evidenceType,
                    $actorUserId,
                    $prepared['source_metadata'],
                ));
            }, 3);
        } catch (\Throwable $exception) {
            if ($createdStorageObject) {
                Storage::disk('local')->delete($prepared['storage_reference']);
            }

            throw $exception;
        }
    }

    /**
     * Links the existing shared batch evidence to newly added draft rows.
     * The stored object and Attachment record are reused; only missing links
     * are created.
     *
     * @param  array<int, string>  $sourceTransactionIds
     * @param  array<int, string>  $targetTransactionIds
     * @return Collection<int, AttachmentLink>
     */
    public function shareExistingToMany(
        string $entityId,
        array $sourceTransactionIds,
        array $targetTransactionIds,
        string $evidenceType,
        ?int $actorUserId = null,
    ): Collection {
        $sourceLink = AttachmentLink::query()
            ->where('accounting_entity_id', $entityId)
            ->where('target_type', 'transaction')
            ->whereIn('target_id', array_values(array_unique($sourceTransactionIds)))
            ->where('evidence_type', $evidenceType)
            ->where('status', 'active')
            ->orderBy('created_at')
            ->first();
        if (! $sourceLink) {
            throw new FinancialDomainException('E-ATTACHMENT-BATCH-MISSING', 'Bukti bersama batch Mutasi Bank tidak ditemukan. Unggah bukti pengganti untuk melanjutkan.');
        }

        $attachment = Attachment::query()
            ->where('accounting_entity_id', $entityId)
            ->findOrFail($sourceLink->attachment_id);
        $targetTransactionIds = array_values(array_unique($targetTransactionIds));

        return DB::transaction(function () use ($entityId, $targetTransactionIds, $evidenceType, $actorUserId, $attachment): Collection {
            return collect($targetTransactionIds)->map(function (string $transactionId) use ($entityId, $evidenceType, $actorUserId, $attachment): AttachmentLink {
                $existing = AttachmentLink::query()
                    ->where('accounting_entity_id', $entityId)
                    ->where('attachment_id', $attachment->id)
                    ->where('target_type', 'transaction')
                    ->where('target_id', $transactionId)
                    ->where('evidence_type', $evidenceType)
                    ->where('status', 'active')
                    ->first();
                if ($existing) {
                    return $existing;
                }

                return $this->evidence->attachToTransaction(
                    $entityId,
                    $transactionId,
                    $attachment->original_filename,
                    $attachment->media_type,
                    (int) $attachment->byte_size,
                    $attachment->content_hash,
                    $attachment->storage_reference,
                    $evidenceType,
                    $actorUserId,
                    [
                        'source_media_type' => $attachment->source_media_type,
                        'source_byte_size' => (int) $attachment->source_byte_size,
                        'image_width' => $attachment->image_width,
                        'image_height' => $attachment->image_height,
                    ],
                );
            });
        }, 3);
    }

    /** @return array{contents:string,original_filename:string,stored_media_type:string,content_hash:string,storage_reference:string,source_metadata:array<string,mixed>} */
    private function prepareUpload(string $entityId, UploadedFile $upload): array
    {
        $sourceMediaType = (string) ($upload->getMimeType() ?: 'application/octet-stream');
        $sourceByteSize = (int) ($upload->getSize() ?: 0);
        $originalFilename = $upload->getClientOriginalName();

        if ($sourceMediaType === 'application/pdf') {
            $contents = file_get_contents($upload->getRealPath());
            $storedMediaType = 'application/pdf';
            $extension = 'pdf';
            $width = null;
            $height = null;
        } elseif (in_array($sourceMediaType, [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/zip',
            'application/octet-stream',
        ], true) && in_array(strtolower($upload->getClientOriginalExtension()), ['xlsx', 'xls'], true)) {
            $contents = file_get_contents($upload->getRealPath());
            $storedMediaType = strtolower($upload->getClientOriginalExtension()) === 'xlsx'
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'application/vnd.ms-excel';
            $extension = strtolower($upload->getClientOriginalExtension());
            $width = null;
            $height = null;
        } elseif (in_array($sourceMediaType, self::IMAGE_MEDIA_TYPES, true)) {
            [$contents, $width, $height] = $this->normalizeImage($upload);
            $storedMediaType = 'image/webp';
            $extension = 'webp';
        } else {
            throw new FinancialDomainException('E-ATTACHMENT-INVALID', 'Lampiran harus berupa JPG, JPEG, PNG, WEBP, PDF, XLS, atau XLSX.');
        }

        if (! is_string($contents) || $contents === '') {
            throw new FinancialDomainException('E-ATTACHMENT-CONVERSION', 'Lampiran tidak dapat diproses atau disimpan.');
        }

        $contentHash = hash('sha256', $contents);
        $storageReference = "financial-v2-evidence/{$entityId}/{$contentHash}.{$extension}";

        return [
            'contents' => $contents,
            'original_filename' => $originalFilename,
            'stored_media_type' => $storedMediaType,
            'content_hash' => $contentHash,
            'storage_reference' => $storageReference,
            'source_metadata' => [
                'source_media_type' => $sourceMediaType,
                'source_byte_size' => $sourceByteSize,
                'image_width' => $width,
                'image_height' => $height,
            ],
        ];
    }

    /** @return array{0:string,1:int,2:int} */
    private function normalizeImage(UploadedFile $upload): array
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            throw new FinancialDomainException('E-ATTACHMENT-CONVERSION', 'Konversi WebP belum tersedia pada server.');
        }
        $image = match ((string) $upload->getMimeType()) {
            'image/jpeg' => @imagecreatefromjpeg($upload->getRealPath()),
            'image/png' => @imagecreatefrompng($upload->getRealPath()),
            'image/webp' => @imagecreatefromwebp($upload->getRealPath()),
            default => false,
        };
        if (! $image) {
            throw new FinancialDomainException('E-ATTACHMENT-CONVERSION', 'Gambar lampiran rusak atau tidak dapat dibaca.');
        }

        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);
        $ratio = min(1, self::MAX_LONG_EDGE / max($sourceWidth, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $ratio));
        $targetHeight = max(1, (int) round($sourceHeight * $ratio));

        if ($targetWidth !== $sourceWidth || $targetHeight !== $sourceHeight) {
            $resized = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $targetWidth, $targetHeight, $transparent);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
            imagedestroy($image);
            $image = $resized;
        }

        ob_start();
        $encoded = imagewebp($image, null, self::WEBP_QUALITY);
        $contents = ob_get_clean();
        imagedestroy($image);
        unset($image, $resized);
        gc_collect_cycles();
        if (! $encoded || ! is_string($contents) || $contents === '') {
            throw new FinancialDomainException('E-ATTACHMENT-CONVERSION', 'Gambar lampiran gagal dikonversi ke WebP.');
        }

        return [$contents, $targetWidth, $targetHeight];
    }
}
