<?php
// app/Models/QurbanReport.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QurbanReport extends Model
{
    use HasFactory;
    
    protected $table = 'qurban_reports';
    
    protected $fillable = [
        'masjid_code',
        'tahun_hijriah',
        'tahun_masehi',
        'is_active',
        'is_published',
        'hero_title',
        'hero_subtitle',
        'hero_badge',
        'hero_masjid',
        'hero_tagline',
        'stat_hewan_sapi',
        'stat_hewan_kambing',
        'stat_paket_daging',
        'stat_mustahik',
        'stat_daging_kg',
        'stat_jamaah',
        'pelaksanaan_deskripsi',
        'pelaksanaan_ketua_nama',
        'pelaksanaan_ketua_jabatan',
        'pelaksanaan_masjid_nama',
        'pelaksanaan_masjid_sub',
        'pelaksanaan_lokasi_sholat',
        'pelaksanaan_lokasi_qurban',
        'pelaksanaan_gambar1',
        'pelaksanaan_gambar2',
        'pelaksanaan_gambar3',
        'dramatis1_title',
        'dramatis1_quote',
        'dramatis1_stat',
        'dramatis1_image',
        'dramatis2_title',
        'dramatis2_quote',
        'dramatis2_stat',
        'dramatis2_image',
        'dramatis3_title',
        'dramatis3_quote',
        'dramatis3_stat',
        'dramatis3_image',
        'pemotongan_sapi_berat_kg',
        'pemotongan_kambing_berat_kg',
        'keuangan_penerimaan_peserta',
        'keuangan_penerimaan_infaq',
        'keuangan_pengeluaran',
        'keuangan_catatan',
        'rings',
        'distribusi',
        'gallery_images',
        'additional_images',
        'qr_image',
        'qr_link',
        'thankyou_title',
        'thankyou_message',
        'thankyou_hadits',
        'footer_instagram',
        'footer_whatsapp',
        'footer_email',
        'footer_quote',
        'catatan_keterangan',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'keuangan_penerimaan_peserta' => 'array',
        'keuangan_penerimaan_infaq' => 'array',
        'keuangan_pengeluaran' => 'array',
        'rings' => 'array',
        'distribusi' => 'array',
        'gallery_images' => 'array',
        'additional_images' => 'array',
    ];
    
    // ========== ACCESSORS ==========
    
    public function getHeroBadgeFormattedAttribute()
    {
        $badge = $this->hero_badge;
        $badge = str_replace('{TAHUN}', $this->tahun_hijriah, $badge);
        return $badge;
    }
    
    public function getHeroSubtitleFormattedAttribute()
    {
        return $this->hero_subtitle ?? $this->tahun_hijriah;
    }
    
    public function getTotalHewanAttribute()
    {
        return $this->stat_hewan_sapi + $this->stat_hewan_kambing;
    }
    
    // ========== KEUANGAN HELPERS ==========
    
    private function cleanAmount($amount)
    {
        if (is_numeric($amount)) {
            return (int)$amount;
        }
        $clean = preg_replace('/[^0-9]/', '', (string)$amount);
        return (int)$clean;
    }
    
    public function getTotalPenerimaanPesertaAttribute()
    {
        $total = 0;
        if ($this->keuangan_penerimaan_peserta) {
            foreach ($this->keuangan_penerimaan_peserta as $item) {
                $total += $this->cleanAmount($item['amount'] ?? 0);
            }
        }
        return $total;
    }
    
    public function getTotalPenerimaanInfaqAttribute()
    {
        $total = 0;
        if ($this->keuangan_penerimaan_infaq) {
            foreach ($this->keuangan_penerimaan_infaq as $item) {
                $total += $this->cleanAmount($item['amount'] ?? 0);
            }
        }
        return $total;
    }
    
    public function getTotalPenerimaanAttribute()
    {
        return $this->getTotalPenerimaanPesertaAttribute() + $this->getTotalPenerimaanInfaqAttribute();
    }
    
    public function getTotalPengeluaranAttribute()
    {
        $total = 0;
        if ($this->keuangan_pengeluaran) {
            foreach ($this->keuangan_pengeluaran as $item) {
                $total += $this->cleanAmount($item['amount'] ?? 0);
            }
        }
        return $total;
    }
    
    public function getSisaDanaAttribute()
    {
        return $this->getTotalPenerimaanAttribute() - $this->getTotalPengeluaranAttribute();
    }
    
    public function getTotalPenerimaanPesertaFormattedAttribute()
    {
        return 'Rp ' . number_format($this->getTotalPenerimaanPesertaAttribute(), 0, ',', '.');
    }
    
    public function getTotalPenerimaanInfaqFormattedAttribute()
    {
        return 'Rp ' . number_format($this->getTotalPenerimaanInfaqAttribute(), 0, ',', '.');
    }
    
    public function getTotalPenerimaanFormattedAttribute()
    {
        return 'Rp ' . number_format($this->getTotalPenerimaanAttribute(), 0, ',', '.');
    }
    
    public function getTotalPengeluaranFormattedAttribute()
    {
        return 'Rp ' . number_format($this->getTotalPengeluaranAttribute(), 0, ',', '.');
    }
    
    public function getSisaDanaFormattedAttribute()
    {
        $sisa = $this->getSisaDanaAttribute();
        $prefix = $sisa < 0 ? '-Rp ' : 'Rp ';
        return $prefix . number_format(abs($sisa), 0, ',', '.');
    }
    
    // ========== RINGS HELPERS ==========
    
    public function getRingsFormattedAttribute()
    {
        if (!$this->rings) {
            return [
                [
                    'title' => 'RING I — Warga TCE & Perangkat',
                    'icon' => 'fa-building',
                    'color' => 'emerald',
                    'items' => [
                        ['label' => '👥 Warga RT/RW Taman Cipulir Estate', 'value' => 'Seluruh warga'],
                        ['label' => '🛡️ Satpam & Tukang Taman TCE', 'value' => '10 orang'],
                        ['label' => '📚 Guru TPA, SD & SMP Al Madinah', 'value' => '23 orang'],
                        ['label' => '🕌 Perangkat Masjid & Panitia', 'value' => '40 orang'],
                        ['label' => '🥩 Tukang Jagal / Pemotong', 'value' => '57 orang'],
                    ],
                    'total' => '130+ penerima'
                ],
                [
                    'title' => 'RING II — Luar TCE & Umum',
                    'icon' => 'fa-globe',
                    'color' => 'teal',
                    'items' => [
                        ['label' => '🏘️ RT/RW di luar Perumahan TCE', 'value' => '792 penerima'],
                        ['label' => '🤲 Pesanggrahan (Bu Hadi) Titipan', 'value' => '40 orang'],
                        ['label' => '🧸 Panti Asuhan & Anak Yatim', 'value' => '46 anak'],
                        ['label' => '🎟️ Masyarakat Umum (tanpa kupon)', 'value' => '244 paket'],
                    ],
                    'total' => '1.122+ penerima'
                ]
            ];
        }
        return $this->rings;
    }
    
    public function getDistribusiFormattedAttribute()
    {
        if (!$this->distribusi) {
            return [
                ['label' => 'Shohibul Qurban', 'value' => 467, 'icon' => 'fa-star-of-life', 'percentage' => 33, 'color' => 'emerald'],
                ['label' => 'Masyarakat Sekitar', 'value' => 467, 'icon' => 'fa-building', 'percentage' => 33, 'color' => 'teal'],
                ['label' => 'Fakir Miskin & Dhuafa', 'value' => 466, 'icon' => 'fa-hands-helping', 'percentage' => 34, 'color' => 'amber'],
            ];
        }
        return $this->distribusi;
    }
    
    public function getRingColorClass($color)
    {
        $colors = [
            'emerald' => 'from-emerald-800 to-emerald-600',
            'teal' => 'from-teal-800 to-teal-600',
            'blue' => 'from-blue-800 to-blue-600',
            'amber' => 'from-amber-800 to-amber-600',
            'purple' => 'from-purple-800 to-purple-600',
            'rose' => 'from-rose-800 to-rose-600',
        ];
        return $colors[$color] ?? 'from-emerald-800 to-emerald-600';
    }
    
    public function getRingBgColorClass($color)
    {
        $colors = [
            'emerald' => 'bg-emerald-50 text-emerald-700',
            'teal' => 'bg-teal-50 text-teal-700',
            'blue' => 'bg-blue-50 text-blue-700',
            'amber' => 'bg-amber-50 text-amber-700',
            'purple' => 'bg-purple-50 text-purple-700',
            'rose' => 'bg-rose-50 text-rose-700',
        ];
        return $colors[$color] ?? 'bg-emerald-50 text-emerald-700';
    }
    
    // Gallery Helpers
    public function getGalleryImagesFormattedAttribute()
    {
        return $this->gallery_images ?? [];
    }
    
    public function getAdditionalImagesFormattedAttribute()
    {
        return $this->additional_images ?? [];
    }
    
    // ========== URL ACCESSORS UNTUK GAMBAR ==========
    
    public function getPelaksanaanGambar1UrlAttribute()
    {
        return $this->pelaksanaan_gambar1 ? asset($this->pelaksanaan_gambar1) : null;
    }
    
    public function getPelaksanaanGambar2UrlAttribute()
    {
        return $this->pelaksanaan_gambar2 ? asset($this->pelaksanaan_gambar2) : null;
    }
    
    public function getPelaksanaanGambar3UrlAttribute()
    {
        return $this->pelaksanaan_gambar3 ? asset($this->pelaksanaan_gambar3) : null;
    }
    
    public function getDramatis1ImageUrlAttribute()
    {
        return $this->dramatis1_image ? asset($this->dramatis1_image) : null;
    }
    
    public function getDramatis2ImageUrlAttribute()
    {
        return $this->dramatis2_image ? asset($this->dramatis2_image) : null;
    }
    
    public function getDramatis3ImageUrlAttribute()
    {
        return $this->dramatis3_image ? asset($this->dramatis3_image) : null;
    }
    
    public function getQrImageUrlAttribute()
    {
        return $this->qr_image ? asset($this->qr_image) : null;
    }
    
    // ========== SCOPES ==========
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_published', true);
    }
    
    public function scopeTahun($query, $tahun)
    {
        return $query->where('tahun_hijriah', $tahun);
    }
}