<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Users/Index', [
            'users' => User::with('roles')->orderBy('name')->paginate(20),
            'roles' => Role::pluck('name'),
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
            'clients' => Client::orderBy('company')->get(['id', 'company']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'partner_id' => 'nullable|exists:partners,id',
            'client_id' => 'nullable|exists:clients,id',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'partner_id' => $data['partner_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->assignRole($data['role']);

        return redirect()->route('users.index')->with('success', 'Usuario creado.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|exists:roles,name',
            'partner_id' => 'nullable|exists:partners,id',
            'client_id' => 'nullable|exists:clients,id',
            'is_active' => 'boolean',
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'partner_id' => $data['partner_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $user->syncRoles([$data['role']]);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado.');
    }
}
