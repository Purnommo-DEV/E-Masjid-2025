<?php

namespace App\Http\Controllers\Admin\Ramadhan;

use App\Http\Controllers\Controller;
use App\Interfaces\LaporanRamadhanHarianRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use RalphJSmit\Laravel\SEO\SchemaCollection;

class LaporanHarianController extends Controller
{
    public function __construct(protected LaporanRamadhanHarianRepositoryInterface $repo) {}

    public function index()
    {
        // SEO khusus halaman laporan harian Ramadhan
        $seoData = new SEOData(
            title: 'Laporan Harian Ramadhan 1447 H - Masjid Raudhotul Jannah TCE',
            description: 'Ikuti kegiatan Ramadhan Masjid Raudhotul Jannah Taman Cipulir Estate. Laporan infak harian, jadwal imam tarawih, santunan yatim & dhuafa serta kegiatan jamaah secara transparan dan real-time.',
            image: secure_asset('images/default-ramadhan.jpg'),
            // Tambah schema custom untuk Organization + WebPage
            schema: SchemaCollection::make()->add([
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => 'Laporan Harian Ramadhan 1447 H',
                'description' => 'Laporan transparan infak dan kegiatan Ramadhan di Masjid Raudhotul Jannah TCE',
                'url' => route('guest.laporan-harian'), // sesuaikan route name
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => 'Masjid Raudhotul Jannah Taman Cipulir Estate',
                    'logo' => secure_asset('pwa/mrj-logo.png'), // ganti kalau ada logo
                    'url' => url('/'),
                ],
            ]),
        );

        return view('masjid.' . masjid() . '.admin.ramadhan.laporan-harian.index')
            ->with('seoData', $seoData);
    }
    public function data()
    {
        return response()->json(['data' => $this->repo->all()]);
    }

    public function store(Request $request)
    {
        // Decode semua JSON repeater fields (wajib, karena Alpine kirim sebagai string JSON)
        $jsonFields = [
            'infaq_ramadan_pengeluaran_detail',       // BARU
            'ifthor_penerimaan_detail',
            'ifthor_pengeluaran_detail',
            'santunan_yatim_penerimaan_hari_ini',
            'paket_sembako_penerimaan_hari_ini',
            'sumbangan_barang',
            'gebyar_anak_penerimaan_hari_ini'
        ];

        foreach ($jsonFields as $field) {
            if ($request->has($field)) {
                $value = $request->input($field);
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    $request->merge([
                        $field => is_array($decoded) ? $decoded : []
                    ]);
                }
            } else {
                // Kalau tidak dikirim, set sebagai array kosong
                $request->merge([$field => []]);
            }
        }

        // Validasi lengkap
        $validated = $request->validate([
            'malam_ke' => 'required|integer|min:1|max:30|unique:laporan_ramadhan_harian,malam_ke',
            'tanggal' => 'required|date',
            'jadwal_imam_id' => 'nullable|exists:jadwal_imam_tarawih,id',

            // Infaq Ramadhan
            'infaq_ramadan_saldo_kemarin' => 'nullable|numeric|min:0',
            'infaq_ramadan_penerimaan_tromol' => 'nullable|numeric|min:0',
            'infaq_ramadan_pengeluaran_detail' => 'nullable|array',
            'infaq_ramadan_pengeluaran_detail.*.untuk' => 'required|string|max:255',
            'infaq_ramadan_pengeluaran_detail.*.nominal' => 'required|numeric|min:0',

            // Iftor
            'ifthor_saldo_kemarin' => 'nullable|numeric|min:0',
            'ifthor_penerimaan_detail' => 'nullable|array',
            'ifthor_penerimaan_detail.*.dari' => 'required|string|max:255',
            'ifthor_penerimaan_detail.*.nominal' => 'required|numeric|min:0',
            'ifthor_pengeluaran_detail' => 'nullable|array',
            'ifthor_pengeluaran_detail.*.untuk' => 'required|string|max:255',
            'ifthor_pengeluaran_detail.*.nominal' => 'required|numeric|min:0',

            // Santunan
            'santunan_yatim_target_anak' => 'nullable|integer|min:0',
            'santunan_yatim_target_nominal' => 'nullable|numeric|min:0',
            'santunan_yatim_terkumpul_kemarin' => 'nullable|numeric|min:0',
            'santunan_yatim_penerimaan_hari_ini' => 'nullable|array',
            'santunan_yatim_penerimaan_hari_ini.*.dari' => 'required|string|max:255',
            'santunan_yatim_penerimaan_hari_ini.*.nominal' => 'required|numeric|min:0',

            // Paket Sembako
            'paket_sembako_target' => 'nullable|integer|min:0',
            'paket_sembako_target_nominal' => 'nullable|numeric|min:0',
            'paket_sembako_terkumpul_kemarin' => 'nullable|numeric|min:0',
            'paket_sembako_penerimaan_hari_ini' => 'nullable|array',
            'paket_sembako_penerimaan_hari_ini.*.dari' => 'required|string|max:255',
            'paket_sembako_penerimaan_hari_ini.*.nominal' => 'required|numeric|min:0',

            // Gebyar Ramadhan (baru)
            'gebyar_anak_tanggal' => 'nullable|date',
            'gebyar_anak_terkumpul_kemarin' => 'nullable|numeric|min:0',
            'gebyar_anak_penerimaan_hari_ini' => 'nullable|array',
            'gebyar_anak_penerimaan_hari_ini.*.dari' => 'required|string|max:255',
            'gebyar_anak_penerimaan_hari_ini.*.nominal' => 'required|numeric|min:0',

            // ZISWAF
            'zakat_fitrah_per_jiwa' => 'nullable|numeric|min:0',
            'fidyah_per_hari_per_jiwa' => 'nullable|numeric|min:0',

            // Lomba & Gebyar
            'lomba_anak_tanggal' => 'nullable|date',
            'lomba_anak_infaq_terkumpul' => 'nullable|numeric|min:0',
            'gebyar_anak_tanggal' => 'nullable|date',
            'gebyar_anak_infaq_terkumpul' => 'nullable|numeric|min:0',

            // Sumbangan Barang & Pengingat
            'sumbangan_barang' => 'nullable|array',
            'pengingat_adab' => 'nullable|string',
        ]);

        $this->repo->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Laporan malam ke-' . $validated['malam_ke'] . ' berhasil disimpan!'
        ]);
    }
    
    public function donatur(){}

    public function edit($id)
    {
        $laporan = $this->repo->find($id);

        // Pastikan semua repeater dikirim sebagai array (bukan null)
        $laporan->infaq_ramadan_pengeluaran_detail = $laporan->infaq_ramadan_pengeluaran_detail ?? [];
        $laporan->ifthor_penerimaan_detail = $laporan->ifthor_penerimaan_detail ?? [];
        $laporan->ifthor_pengeluaran_detail = $laporan->ifthor_pengeluaran_detail ?? [];
        $laporan->santunan_yatim_penerimaan_hari_ini = $laporan->santunan_yatim_penerimaan_hari_ini ?? [];
        $laporan->paket_sembako_penerimaan_hari_ini = $laporan->paket_sembako_penerimaan_hari_ini ?? [];
        $laporan->sumbangan_barang = $laporan->sumbangan_barang ?? [];
        $laporan->gebyar_anak_penerimaan_hari_ini     ??= [];   // <-- BARU

        return response()->json($laporan->toArray());
    }

    public function update(Request $request, $id)
    {
        // Decode JSON repeater fields (sama seperti store)
        $jsonFields = [
            'infaq_ramadan_pengeluaran_detail',       // BARU
            'ifthor_penerimaan_detail',
            'ifthor_pengeluaran_detail',
            'santunan_yatim_penerimaan_hari_ini',
            'paket_sembako_penerimaan_hari_ini',
            'sumbangan_barang',
            'gebyar_anak_penerimaan_hari_ini'
        ];

        foreach ($jsonFields as $field) {
            if ($request->filled($field) && is_string($request->$field)) {
                $request->merge([
                    $field => json_decode($request->$field, true) ?? []
                ]);
            } elseif (!$request->filled($field)) {
                $request->merge([$field => []]);
            }
        }

        // Validasi lengkap (sama seperti store, tapi unique malam_ke ignore ID ini)
        $validated = $request->validate([
            'malam_ke' => 'required|integer|min:1|max:30|unique:laporan_ramadhan_harian,malam_ke,' . $id,
            'tanggal' => 'required|date',
            'jadwal_imam_id' => 'nullable|exists:jadwal_imam_tarawih,id',

            'infaq_ramadan_saldo_kemarin' => 'nullable|numeric|min:0',
            'infaq_ramadan_penerimaan_tromol' => 'nullable|numeric|min:0',
            'infaq_ramadan_pengeluaran_detail' => 'nullable|array',
            'infaq_ramadan_pengeluaran_detail.*.untuk' => 'required|string|max:255',
            'infaq_ramadan_pengeluaran_detail.*.nominal' => 'required|numeric|min:0',

            'ifthor_saldo_kemarin' => 'nullable|numeric|min:0',
            'ifthor_penerimaan_detail' => 'nullable|array',
            'ifthor_penerimaan_detail.*.dari' => 'required|string|max:255',
            'ifthor_penerimaan_detail.*.nominal' => 'required|numeric|min:0',
            'ifthor_pengeluaran_detail' => 'nullable|array',
            'ifthor_pengeluaran_detail.*.untuk' => 'required|string|max:255',
            'ifthor_pengeluaran_detail.*.nominal' => 'required|numeric|min:0',

            'santunan_yatim_target_anak' => 'nullable|integer|min:0',
            'santunan_yatim_target_nominal' => 'nullable|numeric|min:0',
            'santunan_yatim_terkumpul_kemarin' => 'nullable|numeric|min:0',
            'santunan_yatim_penerimaan_hari_ini' => 'nullable|array',
            'santunan_yatim_penerimaan_hari_ini.*.dari' => 'required|string|max:255',
            'santunan_yatim_penerimaan_hari_ini.*.nominal' => 'required|numeric|min:0',

            'paket_sembako_target' => 'nullable|integer|min:0',
            'paket_sembako_target_nominal' => 'nullable|numeric|min:0',
            'paket_sembako_terkumpul_kemarin' => 'nullable|numeric|min:0',
            'paket_sembako_penerimaan_hari_ini' => 'nullable|array',
            'paket_sembako_penerimaan_hari_ini.*.dari' => 'required|string|max:255',
            'paket_sembako_penerimaan_hari_ini.*.nominal' => 'required|numeric|min:0',
            
            'gebyar_anak_tanggal' => 'nullable|date',
            'gebyar_anak_terkumpul_kemarin' => 'nullable|numeric|min:0',
            'gebyar_anak_penerimaan_hari_ini' => 'nullable|array',
            'gebyar_anak_penerimaan_hari_ini.*.dari' => 'required|string|max:255',
            'gebyar_anak_penerimaan_hari_ini.*.nominal' => 'required|numeric|min:0',

            'zakat_fitrah_per_jiwa' => 'nullable|numeric|min:0',
            'fidyah_per_hari_per_jiwa' => 'nullable|numeric|min:0',

            'lomba_anak_tanggal' => 'nullable|date',
            'lomba_anak_infaq_terkumpul' => 'nullable|numeric|min:0',
            'gebyar_anak_tanggal' => 'nullable|date',
            'gebyar_anak_infaq_terkumpul' => 'nullable|numeric|min:0',

            'sumbangan_barang' => 'nullable|array',
            'pengingat_adab' => 'nullable|string',
        ]);

        $this->repo->update($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Laporan malam ke-' . $validated['malam_ke'] . ' berhasil diperbarui!'
        ]);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['success' => true, 'message' => 'Laporan dihapus!']);
    }
}