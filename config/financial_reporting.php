<?php

/*
 * Read-only Financial V2 reporting definitions.
 *
 * These mappings classify already-posted transaction types for presentation.
 * They do not alter posting, balances, Fund policy, or any accounting fact.
 * A finance-policy decision can change the mappings without changing report
 * query code. ZISWAF is deliberately not inferred from Fund names or codes.
 */

return [
    'cash_flow' => [
        'cash_in_transaction_type_codes' => ['RCV'],
        'cash_out_transaction_type_codes' => ['PAY'],
        'internal_transfer_transaction_type_codes' => ['TRF'],
        'fund_transfer_transaction_type_codes' => ['IFT'],
        'adjustment_transaction_type_codes' => ['ADJ'],
    ],

    'friday' => [
        'default' => [
            'label' => 'Operasional Jumat',
            'cash_in_transaction_type_codes' => ['RCV'],
            'cash_out_transaction_type_codes' => ['PAY'],
            'internal_transfer_transaction_type_codes' => ['TRF'],
        ],
    ],

    /*
     * Public disclosure is an explicit governance allow-list.  It is not a
     * name-based classifier and it does not create a relationship between a
     * Fund and a Financial Account.  Amounts always come from the posted V2
     * reporting service; these codes only decide what can be shown publicly.
     */
    'public_ziswaf' => [
        'entity_code' => env('FINANCIAL_PUBLIC_ZISWAF_ENTITY', 'MRJ-ACTUAL'),
        'fund_codes' => [
            'ZAKAT-MAAL',
            'INFAQ-TROMOL',
            'SODAQOH',
            'SANTUNAN-YATIM',
            'FIDYAH',
            'DHUAFA',
        ],
        'financial_account_codes' => [
            'BNI-ZISWAF',
            'CASH-ZISWAF',
        ],
    ],
];
