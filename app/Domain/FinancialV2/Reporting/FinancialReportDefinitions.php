<?php

namespace App\Domain\FinancialV2\Reporting;

/**
 * Reversible presentation definitions for report classifications.
 *
 * The definitions only map approved TransactionType codes. They never infer a
 * Fund's religious/purpose classification from a name, code, or Program.
 */
final class FinancialReportDefinitions
{
    /** @return array<int, string> */
    public function cashInTypes(): array
    {
        return $this->codes('cash_in_transaction_type_codes');
    }

    /** @return array<int, string> */
    public function cashOutTypes(): array
    {
        return $this->codes('cash_out_transaction_type_codes');
    }

    /** @return array<int, string> */
    public function internalTransferTypes(): array
    {
        return $this->codes('internal_transfer_transaction_type_codes');
    }

    /** @return array<int, string> */
    public function fundTransferTypes(): array
    {
        return $this->codes('fund_transfer_transaction_type_codes');
    }

    /** @return array<int, string> */
    public function adjustmentTypes(): array
    {
        return $this->codes('adjustment_transaction_type_codes');
    }

    /** @return array{key: string, label: string, cash_in_transaction_type_codes: array<int, string>, cash_out_transaction_type_codes: array<int, string>, internal_transfer_transaction_type_codes: array<int, string>} */
    public function friday(string $key = 'default'): array
    {
        $definition = config('financial_reporting.friday.'.$key);
        if (! is_array($definition)) {
            $key = 'default';
            $definition = config('financial_reporting.friday.default', []);
        }

        return [
            'key' => $key,
            'label' => (string) ($definition['label'] ?? 'Operasional Jumat'),
            'cash_in_transaction_type_codes' => array_values($definition['cash_in_transaction_type_codes'] ?? $this->cashInTypes()),
            'cash_out_transaction_type_codes' => array_values($definition['cash_out_transaction_type_codes'] ?? $this->cashOutTypes()),
            'internal_transfer_transaction_type_codes' => array_values($definition['internal_transfer_transaction_type_codes'] ?? $this->internalTransferTypes()),
        ];
    }

    /** @return array<int, string> */
    private function codes(string $key): array
    {
        return array_values(config('financial_reporting.cash_flow.'.$key, []));
    }
}
