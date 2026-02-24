<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\QuoteHarianRepositoryInterface;
use Illuminate\Http\Request;

class QuoteHarianController extends Controller
{
    public function __construct(
        protected QuoteHarianRepositoryInterface $repo
    ) {}

    public function index()
    {
        return view('masjid.' . masjid() . '.admin.quote-harian.index');
    }

    public function data()
    {
        $data = $this->repo->all()
            ->orderBy('order', 'asc')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'text'  => 'required|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|in:on',
        ]);

        $this->repo->create($request->only(['title', 'text', 'order', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Quote harian berhasil ditambahkan!'
        ]);
    }

    public function edit($id)
    {
        return response()->json($this->repo->find($id));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'text'  => 'required|string',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|in:on',
        ]);

        $this->repo->update($id, $request->only(['title', 'text', 'order', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Quote harian berhasil diperbarui!'
        ]);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Quote harian berhasil dihapus!'
        ]);
    }
}