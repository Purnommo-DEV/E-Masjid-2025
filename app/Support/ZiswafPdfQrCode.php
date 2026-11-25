<?php

namespace App\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use LogicException;

/** Local PNGs for PDF navigation only. These links never convey authorization. */
final class ZiswafPdfQrCode
{
    /** @return array{url:string,image:string} */
    public function report(): array
    {
        return $this->encode(route('public.ziswaf.index'));
    }

    /** Only call with events already admitted by the restricted report. */
    public function distribution(array $event): ?array
    {
        $id = $event['distribution_id'] ?? null;
        if (! is_string($id) || ! Str::isUuid($id) || (int) ($event['recipient_count'] ?? 0) < 1) {
            return null;
        }

        return $this->encode(route('internal.ziswaf.distributions.show', ['distribution' => $id]));
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
