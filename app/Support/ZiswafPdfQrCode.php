<?php

namespace App\Support;

use App\Domain\FinancialV2\DecimalAmount;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use LogicException;

/** Local PNGs; distribution links authorize only the redacted share page. */
final class ZiswafPdfQrCode
{
    /** @return array{url:string,image:string} */
    public function report(): array
    {
        return $this->encode(route('public.ziswaf.index'));
    }

    /** Only call with events already admitted by the restricted report. */
    public function distribution(array $event, ?string $entityId = null): ?array
    {
        $id = $event['distribution_id'] ?? null;
        $entityId ??= (string) config('financial_reporting.internal_ziswaf_dashboard.entity_id');
        if (! is_string($id) || ! Str::isUuid($id) || ! Str::isUuid($entityId) || ! self::shareable($event)) {
            return null;
        }

        return $this->encode(URL::temporarySignedRoute('public.ziswaf.distribution.share', now()->addDays(365), [
            'entity' => $entityId, 'distribution' => $id,
        ]));
    }

    /** A finalized, fully reconciled event from the existing restricted report. */
    public static function shareable(array $event): bool
    {
        return ($event['operational_status'] ?? null) === 'finalized'
            && (int) ($event['recipient_count'] ?? 0) > 0
            && DecimalAmount::compare((string) ($event['actual_amount'] ?? '0.00'), '0.00') > 0
            && DecimalAmount::equals((string) $event['actual_amount'], (string) ($event['operational_total'] ?? '0.00'));
    }

    /** @return array{url:string,image:string} */
    private function encode(string $url): array
    {
        $parts = parse_url($url);
        if (! in_array($parts['scheme'] ?? null, ['http', 'https'], true) || empty($parts['host'])) {
            throw new LogicException('The PDF QR destination must be an absolute application URL.');
        }

        // A four-module white quiet zone, black modules, and lossless PNG.
        $writer = new Writer(new GDLibRenderer(384, 4));
        $png = $writer->writeString($url, 'UTF-8', ErrorCorrectionLevel::M());

        return ['url' => $url, 'image' => 'data:image/png;base64,'.base64_encode($png)];
    }
}
