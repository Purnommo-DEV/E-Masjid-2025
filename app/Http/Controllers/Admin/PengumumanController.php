<?php

namespace App\Http\Controllers\Admin;

use App\Interfaces\PengumumanRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PengumumanController extends Controller
{
    protected $repo;

    public function __construct(PengumumanRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        return view('masjid.' . masjid() . '.admin.pengumuman.index');
    }

    public function data(Request $request)
    {
        $pengumuman = $this->repo->all();

        return DataTables::of($pengumuman)
            ->addColumn('tipe_badge', function($row) {
                $badges = [
                    'banner' => '<span class="badge bg-primary">📢 Banner</span>',
                    'popup'  => '<span class="badge bg-info">🪟 Popup</span>',
                    'notif'  => '<span class="badge bg-warning">🔔 Notif</span>'
                ];
                return $badges[$row->tipe] ?? '<span class="badge bg-secondary">' . $row->tipe . '</span>';
            })
            ->addColumn('status_badge', function($row) {
                if (!$row->is_active) {
                    return '<span class="badge bg-danger">❌ Nonaktif</span>';
                }
                
                if ($row->mulai && $row->selesai) {
                    $now = now();
                    if ($now->lt($row->mulai)) {
                        return '<span class="badge bg-warning">⏳ Akan Datang</span>';
                    } elseif ($now->gt($row->selesai)) {
                        return '<span class="badge bg-secondary">📅 Kadaluarsa</span>';
                    }
                }
                
                return '<span class="badge bg-success">✅ Aktif</span>';
            })
            ->addColumn('periode_text', function($row) {
                if (!$row->mulai && !$row->selesai) {
                    return '<span class="text-muted text-xs">Tanpa batas</span>';
                }
                $mulai = $row->mulai ? $row->mulai->format('d/m/Y H:i') : '-';
                $selesai = $row->selesai ? $row->selesai->format('d/m/Y H:i') : '-';
                return $mulai . ' → ' . $selesai;
            })
            ->addColumn('aksi', function($row) {
                return '
                    <div class="flex gap-2 justify-center">
                        <button class="btn btn-sm btn-outline-primary" onclick="editPengumuman(' . $row->id . ')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 20h4l11-11a2.828 2.828 0 0 0-4-4L4 16v4z"/>
                            </svg>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="hapusPengumuman(' . $row->id . ')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6"/>
                            </svg>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['tipe_badge', 'status_badge', 'periode_text', 'aksi'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'tipe' => 'required|in:banner,popup,notif',
            'isi' => 'nullable|string',
            'mulai' => 'nullable|date',
            'selesai' => 'nullable|date|after_or_equal:mulai',
        ]);

        $this->repo->create([
            'judul' => $request->judul,
            'isi' => $request->isi,
            'tipe' => $request->tipe,
            'mulai' => $request->mulai,
            'selesai' => $request->selesai,
            'is_active' => $request->has('is_active'),
        ]);

        return response()->json(['success' => true, 'message' => 'Pengumuman ditambahkan!']);
    }

    public function edit($id)
    {
        $pengumuman = $this->repo->find($id);
        return response()->json([
            'id' => $pengumuman->id,
            'judul' => $pengumuman->judul,
            'isi' => $pengumuman->isi,
            'tipe' => $pengumuman->tipe,
            'mulai' => $pengumuman->mulai?->format('Y-m-d\TH:i'),
            'selesai' => $pengumuman->selesai?->format('Y-m-d\TH:i'),
            'is_active' => $pengumuman->is_active,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'tipe' => 'required|in:banner,popup,notif',
            'isi' => 'nullable|string',
            'mulai' => 'nullable|date',
            'selesai' => 'nullable|date|after_or_equal:mulai',
        ]);

        $this->repo->update($id, [
            'judul' => $request->judul,
            'isi' => $request->isi,
            'tipe' => $request->tipe,
            'mulai' => $request->mulai,
            'selesai' => $request->selesai,
            'is_active' => $request->has('is_active'),
        ]);

        return response()->json(['success' => true, 'message' => 'Pengumuman diperbarui!']);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['success' => true, 'message' => 'Pengumuman dihapus!']);
    }
}