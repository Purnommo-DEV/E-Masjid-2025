<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\DanaTerikatReferensiRepositoryInterface;
use Illuminate\Http\Request;

class DanaTerikatReferensiController extends Controller
{
    protected DanaTerikatReferensiRepositoryInterface $repo;

    public function __construct(DanaTerikatReferensiRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        return response()->json($this->repo->all());
    }

    public function store(Request $request)
    {
        $ref = $this->repo->create($request->validate([
            'nama'  => 'required|string|max:100',
            'warna' => 'nullable|string|max:20'
        ]));

        return response()->json($ref);
    }

    public function show($id)
    {
        return $this->repo->find($id);
    }

    public function update(Request $request, $id)
    {
        $ref = $this->repo->update($id, $request->validate([
            'nama'  => 'required|string|max:100',
            'warna' => 'nullable|string|max:20'
        ]));

        return response()->json($ref);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['message' => 'Berhasil dihapus']);
    }
}
