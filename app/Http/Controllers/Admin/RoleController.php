<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $permissions = Permission::all();
        return view('masjid.'.masjid().'.admin.role.index', compact('permissions'));
    }

    public function data()
    {
        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->implode(', '),
            ];
        });
        return response()->json(['data' => $roles]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|unique:roles']);
        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);
        return response()->json(['success' => true, 'message' => 'Role dibuat!']);
    }

    public function show($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        return response()->json([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray()
        ]);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $request->validate(['name' => "required|unique:roles,name,$id"]);
        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);
        return response()->json(['success' => true, 'message' => 'Role diperbarui!']);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        if ($role->name === 'SuperAdmin') {
            return response()->json(['success' => false, 'message' => 'Tidak bisa hapus SuperAdmin!'], 400);
        }
        $role->delete();
        return response()->json(['success' => true, 'message' => 'Role dihapus!']);
    }
}