<?php

namespace App\Interfaces;

interface SaldoAwalRepositoryInterface
{
    public function simpanDraft(array $data);
    public function lockPeriode($periodeId);
    public function allPeriodes();
    public function isLocked($tanggal): bool;
}
