<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FinancialPostingException;
use App\Domain\FinancialV2\InlineConfigurationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

final class InlineConfigurationController extends Controller
{
    public function __construct(private readonly InlineConfigurationService $configurations) {}

    public function show(Request $request): JsonResponse
    {
        $this->authorizeConfiguration($request);

        try {
            return response()->json(['ok' => true] + $this->configurations->describe($this->contextInput($request)));
        } catch (FinancialDomainException|FinancialPostingException $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeConfiguration($request);
        $input = $this->contextInput($request) + $request->validate([
            'posting_rule_version_id' => ['required', 'uuid'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'evidence_type' => ['nullable', Rule::in(['receipt', 'invoice', 'transfer_proof', 'statement', 'cash_count', 'approval', 'policy', 'other'])],
            'required_approval_steps' => ['nullable', 'integer', 'min:0', 'max:9'],
            'policy_document_ref' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            return response()->json(['ok' => true] + $this->configurations->createDraft($input, $request->user()?->id));
        } catch (FinancialDomainException|FinancialPostingException $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['ok' => false, 'message' => 'Draft konfigurasi belum dapat disimpan.'], 422);
        }
    }

    /** @return array<string, mixed> */
    private function contextInput(Request $request): array
    {
        return $request->validate([
            'entity' => ['required', 'uuid'],
            'operation' => ['required', Rule::in(['receipt', 'payment', 'transfer', 'interfund', 'bank_mutation'])],
            'date' => ['required', 'date'],
            'financial_account_id' => ['nullable', 'uuid'],
            'source_financial_account_id' => ['nullable', 'uuid'],
            'destination_financial_account_id' => ['nullable', 'uuid'],
            'fund_id' => ['nullable', 'uuid'],
            'source_fund_id' => ['nullable', 'uuid'],
            'destination_fund_id' => ['nullable', 'uuid'],
            'category_id' => ['nullable', 'uuid'],
            'program_id' => ['nullable', 'uuid'],
        ]);
    }

    private function authorizeConfiguration(Request $request): void
    {
        abort_unless($request->user()?->hasRole('SuperAdmin'), 403, 'Anda tidak memiliki akses untuk mengelola konfigurasi Financial V2.');
    }
}
