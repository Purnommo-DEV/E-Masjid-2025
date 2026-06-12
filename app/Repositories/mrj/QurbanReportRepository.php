<?php
// app/Repositories/mrj/QurbanReportRepository.php

namespace App\Repositories\mrj;

use App\Interfaces\QurbanReportRepositoryInterface;
use App\Models\QurbanReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QurbanReportRepository implements QurbanReportRepositoryInterface
{
    public function all()
    {
        return QurbanReport::where('masjid_code', masjid())
            ->orderBy('tahun_hijriah', 'desc')
            ->get();
    }
    
    public function find($id)
    {
        return QurbanReport::where('masjid_code', masjid())
            ->findOrFail($id);
    }
    
    public function findActive()
    {
        return QurbanReport::where('masjid_code', masjid())
            ->where('is_active', true)
            ->where('is_published', true)
            ->first();
    }
    
    public function findByTahun($tahun)
    {
        return QurbanReport::where('masjid_code', masjid())
            ->where('tahun_hijriah', $tahun)
            ->where('is_published', true)
            ->first();
    }
    
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['masjid_code'] = masjid();
            
            // Clean amount fields
            $data = $this->cleanAmountFields($data);
            
            return QurbanReport::create($data);
        });
    }
    
    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $report = $this->find($id);
            
            // Handle file uploads
            $imageFields = [
                'pelaksanaan_gambar1', 'pelaksanaan_gambar2', 'pelaksanaan_gambar3', 'pelaksanaan_gambar4',  // tambah gambar4
                'dramatis1_image', 'dramatis2_image', 'dramatis3_image', 'qr_image'
            ];
            
            foreach ($imageFields as $field) {
                if (isset($data[$field]) && $data[$field] instanceof UploadedFile) {
                    if ($report->$field) {
                        delete_image($report->$field);
                    }
                    $folder = "qurban-report/{$report->tahun_hijriah}/images";
                    $path = upload_image($data[$field], $folder, null, true, 80);
                    $data[$field] = Storage::disk('public')->url($path);
                } elseif (isset($data['remove_' . $field]) && $data['remove_' . $field] == '1') {
                    if ($report->$field) {
                        delete_image($report->$field);
                    }
                    $data[$field] = null;
                }
                unset($data['remove_' . $field]);
            }
            
            // Clean amount fields
            $data = $this->cleanAmountFields($data);
            
            $report->update($data);
            return $report;
        });
    }
    
    private function cleanAmountFields(array $data)
    {
        $amountFields = ['keuangan_penerimaan_peserta', 'keuangan_penerimaan_infaq', 'keuangan_pengeluaran'];
        
        foreach ($amountFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                foreach ($data[$field] as &$item) {
                    if (isset($item['amount'])) {
                        $item['amount'] = (int)preg_replace('/[^0-9]/', '', $item['amount']);
                    }
                }
            }
        }
        
        return $data;
    }
    
    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $report = $this->find($id);
            
            // Delete all images
            $imageFields = [
                'pelaksanaan_gambar1', 'pelaksanaan_gambar2', 'pelaksanaan_gambar3', 'pelaksanaan_gambar4',  // tambah gambar4
                'dramatis1_image', 'dramatis2_image', 'dramatis3_image', 'qr_image'
            ];
            
            foreach ($imageFields as $field) {
                if ($report->$field) {
                    delete_image($report->$field);
                }
            }
            
            // Delete gallery images
            if ($report->gallery_images) {
                foreach ($report->gallery_images as $img) {
                    if (isset($img['url'])) {
                        delete_image($img['url']);
                    }
                }
            }
            
            if ($report->additional_images) {
                foreach ($report->additional_images as $img) {
                    delete_image($img);
                }
            }
            
            return $report->delete();
        });
    }
    
    public function clone($id)
    {
        return DB::transaction(function () use ($id) {
            $oldReport = $this->find($id);
            
            $year = (int) filter_var($oldReport->tahun_hijriah, FILTER_SANITIZE_NUMBER_INT);
            $newYear = ($year + 1) . 'H';
            $newMasehi = (intval($oldReport->tahun_masehi) + 1) . '';
            
            $newReport = $oldReport->replicate();
            $newReport->tahun_hijriah = $newYear;
            $newReport->tahun_masehi = $newMasehi;
            $newReport->is_active = false;
            $newReport->is_published = false;
            $newReport->created_at = now();
            $newReport->updated_at = now();
            
            // Reset statistik
            $newReport->stat_hewan_sapi = 0;
            $newReport->stat_hewan_kambing = 0;
            $newReport->stat_paket_daging = 0;
            $newReport->stat_mustahik = 0;
            $newReport->stat_daging_kg = 0;
            
            // Reset semua kolom gambar (gunakan array untuk memudahkan)
            $imageFields = [
                'pelaksanaan_gambar1', 'pelaksanaan_gambar2', 'pelaksanaan_gambar3', 'pelaksanaan_gambar4',
                'pelaksanaan_caption1', 'pelaksanaan_caption2', 'pelaksanaan_caption3', 'pelaksanaan_caption4',
                'dramatis1_image', 'dramatis2_image', 'dramatis3_image',
                'qr_image', 'qr_link',
                'gallery_images', 'additional_images',
                'keuangan_penerimaan_peserta', 'keuangan_penerimaan_infaq', 'keuangan_pengeluaran',
                'rings', 'distribusi'
            ];
            
            foreach ($imageFields as $field) {
                $newReport->$field = null;
            }
            
            // Gallery dan additional_images harus array kosong
            $newReport->gallery_images = [];
            $newReport->additional_images = [];
            
            $newReport->save();
            
            return $newReport;
        });
    }
    
    public function setActive($id)
    {
        return DB::transaction(function () use ($id) {
            $report = $this->find($id);
            // Toggle: jika aktif jadi nonaktif, jika nonaktif jadi aktif
            $report->is_active = !$report->is_active;
            $report->save();
            return $report;
        });
    }
    
    public function getAvailableYears()
    {
        return QurbanReport::where('masjid_code', masjid())
            ->where('is_published', true)
            ->orderBy('tahun_hijriah', 'desc')
            ->pluck('tahun_hijriah')
            ->toArray();
    }
}