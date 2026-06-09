<?php
// app/Interfaces/QurbanReportRepositoryInterface.php

namespace App\Interfaces;

interface QurbanReportRepositoryInterface
{
    public function all();
    public function find($id);
    public function findActive();
    public function findByTahun($tahun);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function clone($id);
    public function setActive($id);
    public function getAvailableYears();
}