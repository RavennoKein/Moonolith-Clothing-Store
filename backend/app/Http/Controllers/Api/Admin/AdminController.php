<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{

    use AuthorizesRequests;

    public function index()
    {
        $admins = User::whereIn('role', ['admin', 'user'])
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone_number,
                    'role' => $user->role,
                    'address' => $user->address,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $admins
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $admin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone ?? '-',
            'address' => $request->address ?? '-',
            'role' => 'admin',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin berhasil ditambahkan',
            'data' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone_number,
                'role' => $admin->role,
                'address' => $admin->address,
            ]
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Data ini bukan admin'
            ], 422);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone ?? $user->phone_number,
            'address' => $request->address ?? $user->address,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Admin berhasil diperbarui'
        ]);
    }


    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang bisa dihapus'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Admin berhasil dihapus'
        ]);
    }
}