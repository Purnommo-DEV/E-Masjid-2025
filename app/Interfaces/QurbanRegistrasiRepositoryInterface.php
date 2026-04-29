<?php
// app/Interfaces/QurbanRegistrasiRepositoryInterface.php

namespace App\Interfaces;

use Illuminate\Http\UploadedFile;

interface QurbanRegistrasiRepositoryInterface
{
    public function all($status = null, $search = null);
    public function find($id);
    public function updateStatus($id, $status);
    public function getStatistics();
    public function getByDateRange($startDate, $endDate);
    public function exportData($status = null);
    
    // ✅ TAMBAHKAN METHOD INI
    public function uploadBukti($id, UploadedFile $file);
    public function deleteBukti($id);
}