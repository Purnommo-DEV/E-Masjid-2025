<?php
namespace App\Interfaces;

interface ZakatRepositoryInterface
{
    public function terima(array $data, $request);
    public function salurkan(array $data, $request);
    public function dataTable();
}