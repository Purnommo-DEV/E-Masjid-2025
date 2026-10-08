<?php

namespace Database\Seeders;

use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\PostingRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use UnexpectedValueException;

/**
 * Idempotently corrects display names for the MRJ-ACTUAL PAY configuration.
 *
 * php artisan db:seed --class=Database\\Seeders\\RenameMrjPayAccountingNamesSeeder --force
 */
final class RenameMrjPayAccountingNamesSeeder extends Seeder
{
    private const ENTITY_CODE = 'MRJ-ACTUAL';

    public function run(): void
    {
        DB::transaction(function (): void {
            $entity = AccountingEntity::query()
                ->where('code', self::ENTITY_CODE)
                ->lockForUpdate()
                ->sole();

            $account = Account::query()
                ->forEntity($entity->id)
                ->where('code', 'EXP-MRJ')
                ->lockForUpdate()
                ->sole();

            $this->rename(
                $account,
                'Penggunaan Operasional MRJ',
                'Penggunaan Dana MRJ',
                'Account EXP-MRJ',
            );

            $postingRule = PostingRule::query()
                ->forEntity($entity->id)
                ->where('code', 'MRJ-PAY-STANDARD')
                ->lockForUpdate()
                ->sole();

            $this->rename(
                $postingRule,
                'Pengeluaran operasional MRJ',
                'Pengeluaran/Penyaluran Dana MRJ',
                'Posting Rule MRJ-PAY-STANDARD',
            );
        }, 3);
    }

    private function rename(Account|PostingRule $model, string $oldName, string $newName, string $label): void
    {
        if ($model->name === $newName) {
            return;
        }

        if ($model->name !== $oldName) {
            throw new UnexpectedValueException(
                sprintf('%s has unexpected name "%s".', $label, $model->name),
            );
        }

        $model->update(['name' => $newName]);
    }
}
