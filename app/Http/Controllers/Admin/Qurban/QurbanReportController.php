<?php
// app/Http/Controllers/Admin/Qurban/QurbanReportController.php

namespace App\Http\Controllers\Admin\Qurban;

use App\Http\Controllers\Controller;
use App\Interfaces\QurbanReportRepositoryInterface;
use App\Models\QurbanReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class QurbanReportController extends Controller
{
    protected $repo;
    
    public function __construct(QurbanReportRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }
    
    public function index()
    {
        $reports = $this->repo->all();
        return view('masjid.' . masjid() . '.admin.qurban.report.index', compact('reports'));
    }
    
    public function create()
    {
        $lastReport = $this->repo->all()->first();
        $nextYear = $lastReport 
            ? ((int) filter_var($lastReport->tahun_hijriah, FILTER_SANITIZE_NUMBER_INT) + 1) . ' H'
            : '1447 H';
        $nextMasehi = $lastReport 
            ? (intval($lastReport->tahun_masehi) + 1) . ''
            : '2026';
        
        return view('masjid.' . masjid() . '.admin.qurban.report.create', compact('nextYear', 'nextMasehi'));
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tahun_hijriah' => 'required|string|unique:qurban_reports,tahun_hijriah,NULL,id,masjid_code,' . masjid(),
            'tahun_masehi' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        $data = $request->all();
        
        // Handle JSON fields
        $jsonFields = ['keuangan_penerimaan_peserta', 'keuangan_penerimaan_infaq', 'keuangan_pengeluaran', 'rings', 'distribusi', 'gallery_images', 'additional_images'];
        foreach ($jsonFields as $field) {
            if ($request->has($field) && is_array($request->$field)) {
                $data[$field] = $request->$field;
            }
        }
        
        $this->repo->create($data);
        
        return redirect()->route('admin.qurban.report.index')
            ->with('success', 'Laporan berhasil dibuat!');
    }
    
    public function edit($id)
    {
        $report = $this->repo->find($id);
        return view('masjid.' . masjid() . '.admin.qurban.report.edit', compact('report'));
    }
    
    public function update(Request $request, $id)
    {
        $report = $this->repo->find($id);
        
        $validator = Validator::make($request->all(), [
            'tahun_hijriah' => 'required|string|unique:qurban_reports,tahun_hijriah,' . $id . ',id,masjid_code,' . masjid(),
            'tahun_masehi' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        $data = $request->all();
        
        // Handle JSON fields
        $jsonFields = ['keuangan_penerimaan_peserta', 'keuangan_penerimaan_infaq', 'keuangan_pengeluaran', 'rings', 'distribusi', 'gallery_images', 'additional_images'];
        foreach ($jsonFields as $field) {
            if ($request->has($field) && is_array($request->$field)) {
                $data[$field] = $request->$field;
            } else {
                $data[$field] = null;
            }
        }
        
        $this->repo->update($id, $data);
        
        return redirect()->route('admin.qurban.report.index')
            ->with('success', 'Laporan berhasil diperbarui!');
    }
    
    public function destroy($id)
    {
        try {
            $this->repo->delete($id);
            // Kembalikan response JSON untuk AJAX
            return response()->json(['success' => true, 'message' => 'Laporan berhasil dihapus!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function clone($id)
    {
        $newReport = $this->repo->clone($id);
        return redirect()->route('admin.qurban.report.edit', $newReport->id)
            ->with('success', 'Laporan untuk tahun ' . $newReport->tahun_hijriah . ' berhasil dibuat! Silakan isi datanya.');
    }
    
    public function setActive($id)
    {
        $this->repo->setActive($id);
        return redirect()->route('admin.qurban.report.index')
            ->with('success', 'Laporan berhasil diaktifkan!');
    }
    
    public function uploadGallery(Request $request, $id)
    {
        try {
            $report = $this->repo->find($id);
            $type = $request->input('type', 'gallery');
            
            $files = $type == 'gallery' ? $request->file('gallery_images') : $request->file('additional_images');
            
            if (!$files || empty($files)) {
                return response()->json(['success' => false, 'message' => 'Tidak ada file yang diupload']);
            }
            
            if (!is_array($files)) {
                $files = [$files];
            }
            
            $uploaded = [];
            $currentData = $type == 'gallery' ? ($report->gallery_images ?? []) : ($report->additional_images ?? []);
            
            foreach ($files as $file) {
                if (!$file || !$file->isValid()) continue;
                
                $folder = "qurban-report/" . $report->tahun_hijriah . "/" . $type;
                $path = upload_image($file, $folder, null, true, 80);
                $url = Storage::disk('public')->url($path);
                
                if ($type == 'gallery') {
                    $uploaded[] = ['url' => $url, 'alt' => 'Foto galeri', 'type' => 'square'];
                } else {
                    $uploaded[] = $url;
                }
            }
            
            if (empty($uploaded)) {
                return response()->json(['success' => false, 'message' => 'Tidak ada file yang berhasil diupload']);
            }
            
            $newData = array_merge($currentData, $uploaded);
            
            if ($type == 'gallery') {
                $report->gallery_images = $newData;
            } else {
                $report->additional_images = $newData;
            }
            $report->save();
            
            return response()->json(['success' => true, 'message' => count($uploaded) . ' file berhasil diupload']);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function removeGallery(Request $request, $id)
    {
        try {
            $report = $this->repo->find($id);
            $index = $request->input('index');
            $type = $request->input('type', 'gallery');
            
            $currentData = $type == 'gallery' ? ($report->gallery_images ?? []) : ($report->additional_images ?? []);
            
            if (!isset($currentData[$index])) {
                return response()->json(['success' => false, 'message' => 'Foto tidak ditemukan']);
            }
            
            $path = $type == 'gallery' ? $currentData[$index]['url'] : $currentData[$index];
            delete_image($path);
            
            unset($currentData[$index]);
            $newData = array_values($currentData);
            
            if ($type == 'gallery') {
                $report->gallery_images = $newData;
            } else {
                $report->additional_images = $newData;
            }
            $report->save();
            
            return response()->json(['success' => true, 'message' => 'Foto berhasil dihapus']);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function removeFieldImage(Request $request, $id)
    {
        try {
            $report = $this->repo->find($id);
            $field = $request->input('field');
            
            // Validasi field yang diizinkan
            $allowedFields = [
                'pelaksanaan_gambar1', 'pelaksanaan_gambar2', 'pelaksanaan_gambar3', 'pelaksanaan_gambar4',
                'dramatis1_image', 'dramatis2_image', 'dramatis3_image', 'qr_image'
            ];
            
            if (!in_array($field, $allowedFields)) {
                return response()->json(['success' => false, 'message' => 'Field tidak valid']);
            }
            
            // Hapus file fisik
            if ($report->$field) {
                delete_image($report->$field);
            }
            
            // Set field ke null
            $report->$field = null;
            $report->save();
            
            return response()->json(['success' => true, 'message' => 'Gambar berhasil dihapus']);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}