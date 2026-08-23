<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\Reporting\PublicZiswafReportService;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $input = $this->validatedPeriod($request);

        return view('masjid.mrj.guest.financial-v2.ziswaf-report', [
            'report' => $this->reports->report($input['from'] ?? null, $input['to'] ?? $input['as_of'] ?? null),
        ]);
    }

    public function pdf(Request $request)
    {
        $input = $this->validatedPeriod($request);
        $report = $this->reports->pdfReport($input['from'] ?? null, $input['to'] ?? $input['as_of'] ?? null);
        $generatedAt = now();

        $pdf = Pdf::loadView('masjid.mrj.guest.financial-v2.ziswaf-report-pdf', [
            'report' => $report,
        ])->setPaper('a4', 'portrait');
        $pdf->render();

        // Dompdf's CSS page counter is not reliable on long documents. Draw
        // only the stable footer and native page numbers; the report header is
        // regular-flow HTML so it cannot overlap a continuation-page table.
        $canvas = $pdf->getDomPDF()->getCanvas();
        $fontMetrics = $pdf->getDomPDF()->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
        $printedAt = $generatedAt->format('d/m/Y H:i').' WIB';
        $period = sprintf('Periode %s-%s', $report['period_from'], $report['as_of']);
        $canvas->page_script(static function (int $pageNumber, int $pageCount, $pageCanvas) use ($font, $printedAt, $period): void {
            $pageCanvas->line(40, 800, 555, 800, [0.85, 0.91, 0.88], 0.5);
            $pageCanvas->text(40, 812, 'Masjid Raudhotul Jannah - Dicetak '.$printedAt, $font, 6.5, [0.37, 0.45, 0.41]);
            $pageCanvas->text(355, 812, sprintf('%s - Hal. %d/%d', $period, $pageNumber, $pageCount), $font, 6.5, [0.37, 0.45, 0.41]);
        });

        return $pdf->download('laporan-dana-ziswaf-'.$report['as_of'].'.pdf');
    }

    public function fund(Request $request, string $fundCode)
    {
        $input = $this->validatedPeriod($request);

        return view('masjid.mrj.guest.financial-v2.ziswaf-fund', [
            'report' => $this->reports->fundDetail($fundCode, $input['to'] ?? $input['as_of'] ?? null),
        ]);
    }

    /** @return array{from?: string, to?: string, as_of?: string} */
    private function validatedPeriod(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            // Backward-compatible public links from the first ZISWAF report.
            'as_of' => ['nullable', 'date'],
        ]);
    }
}
