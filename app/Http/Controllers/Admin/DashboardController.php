<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Models\Activity;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:SuperAdmin')->except(['profile']);
    }

    public function index()
    {
        return view('masjid'.masjid().'admin.dashboard');
    }

    public function data()
    {
        $users = User::with('roles')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->pluck('name')->first() ?? '-',
                'roles' => $user->roles->pluck('name')->toArray()
            ];
        });
        return response()->json(['data' => $users]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|exists:roles,name'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        $user->assignRole($request->role);
        activity()->log("Menambahkan user {$user->name} dengan role {$request->role}");

        return response()->json(['success' => true, 'data' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,$id",
            'role' => 'required|exists:roles,name'
        ]);

        $oldRole = $user->roles->pluck('name')->first();
        $user->update($request->only(['name', 'email']));
        if ($request->filled('password')) {
            $user->update(['password' => bcrypt($request->password)]);
        }
        $user->syncRoles($request->role);

        activity()->log("Mengubah user {$user->name} dari role {$oldRole} menjadi {$request->role}");

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->hasRole('SuperAdmin') && User::role('SuperAdmin')->count() === 1) {
            return response()->json(['success' => false, 'message' => 'Tidak bisa hapus SuperAdmin terakhir!'], 400);
        }

        $name = $user->name;
        $user->delete();
        activity()->log("Menghapus user {$name}");

        return response()->json(['success' => true]);
    }
}