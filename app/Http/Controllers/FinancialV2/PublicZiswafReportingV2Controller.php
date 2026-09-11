<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\Reporting\ZiswafReportingV2Service;
use Illuminate\Http\Request;

/** Public, web-only and read-only ZISWAF Reporting V2 disclosure. */
final class PublicZiswafReportingV2Controller
{
    public function __construct(private readonly ZiswafReportingV2Service $reports) {}

    public function index(Request $request)
    {
        $input = $request->validate([
            'from' => ['nullable', 'date'],
            'through' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return view('masjid.mrj.guest.financial-v2.ziswaf-report-v2', [
            'report' => $this->reports->publicReport($input['from'] ?? null, $input['through'] ?? null),
        ]);
    }
}
