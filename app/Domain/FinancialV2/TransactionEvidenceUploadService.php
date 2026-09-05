<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AttachmentLink;
use Illuminate\Http\UploadedFile;
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
        $sourceMediaType = (string) ($upload->getMimeType() ?: 'application/octet-stream');
        $sourceByteSize = (int) ($upload->getSize() ?: 0);
        $originalFilename = $upload->getClientOriginalName();

        if ($sourceMediaType === 'application/pdf') {
            $contents = file_get_contents($upload->getRealPath());
            $storedMediaType = 'application/pdf';
            $extension = 'pdf';
            $width = null;
            $height = null;
        } elseif (in_array($sourceMediaType, self::IMAGE_MEDIA_TYPES, true)) {
            [$contents, $width, $height] = $this->normalizeImage($upload);
            $storedMediaType = 'image/webp';
            $extension = 'webp';
        } else {
            throw new FinancialDomainException('E-ATTACHMENT-INVALID', 'Lampiran harus berupa JPG, JPEG, PNG, WEBP, atau PDF.');
        }

        if (! is_string($contents) || $contents === '') {
            throw new FinancialDomainException('E-ATTACHMENT-CONVERSION', 'Lampiran tidak dapat diproses atau disimpan.');
        }

        $contentHash = hash('sha256', $contents);
        $storageReference = "financial-v2-evidence/{$entityId}/{$contentHash}.{$extension}";
        if (! Storage::disk('local')->exists($storageReference)) {
            Storage::disk('local')->put($storageReference, $contents);
        }

        try {
            return $this->evidence->attachToTransaction(
                $entityId,
                $transactionId,
                $originalFilename,
                $storedMediaType,
                strlen($contents),
                $contentHash,
                $storageReference,
                $evidenceType,
                $actorUserId,
                [
                    'source_media_type' => $sourceMediaType,
                    'source_byte_size' => $sourceByteSize,
                    'image_width' => $width,
                    'image_height' => $height,
                ],
            );
        } catch (\Throwable $exception) {
            // A failed database link must not leave a new unreferenced object.
            if (! \App\Models\FinancialV2\Attachment::query()->where('accounting_entity_id', $entityId)->where('content_hash', $contentHash)->exists()) {
                Storage::disk('local')->delete($storageReference);
            }
            throw $exception;
        }
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
