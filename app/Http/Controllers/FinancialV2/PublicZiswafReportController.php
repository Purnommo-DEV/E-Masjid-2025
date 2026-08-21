<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\Reporting\PublicZiswafReportService;
use Illuminate\Http\Request;

/**
 * Public, read-only disclosure adapter.
 *
 * No authenticated admin capability is accepted here and the controller has
 * no writer dependency. The underlying service admits only the configured
 * public disclosure scope and obtains all financial values from V2 reports.
 */
final class PublicZiswafReportController
{
    public function __construct(private readonly PublicZiswafReportService $reports) {}

    public function index(Request $request)
    {
        $input = $request->validate([
            'as_of' => ['nullable', 'date'],
        ]);

        return view('masjid.mrj.guest.financial-v2.ziswaf-report', [
            'report' => $this->reports->report($input['as_of'] ?? null),
        ]);
    }

    public function fund(Request $request, string $fundCode)
    {
        $input = $request->validate([
            'as_of' => ['nullable', 'date'],
        ]);

        return view('masjid.mrj.guest.financial-v2.ziswaf-fund', [
            'report' => $this->reports->fundDetail($fundCode, $input['as_of'] ?? null),
        ]);
    }
}
