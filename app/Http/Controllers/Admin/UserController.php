<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('cliente');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('rol')) {
            $query->where('rol', $request->input('rol'));
        }

        $usuarios = $query->orderBy('name', 'asc')->paginate(24)->withQueryString();
        $clientes = Cliente::select('id', 'nombre_cliente')->get();

        return Inertia::render('Admin/Usuarios/Index', [
            'usuarios' => $usuarios,
            'clientes' => $clientes,
            'filters'  => $request->only(['search', 'rol'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'username'   => 'nullable|string|max:50|unique:users,username',
            'email'      => 'required|email|max:100|unique:users,email',
            'password'   => 'required|string|min:8',
            'rol'        => ['required', Rule::in(['ADMIN', 'TECNICO', 'INVITADO'])],
            'cliente_id' => 'nullable|required_if:rol,INVITADO|exists:clientes,id',
            'activo'     => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['activo']   = $request->boolean('activo', true);

        User::create($validated);

        return redirect()->back()->with('success', 'Usuario creado exitosamente.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'username'   => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'email'      => ['required', 'email', 'max:100', Rule::unique('users')->ignore($user->id)],
            'password'   => 'nullable|string|min:8',
            'rol'        => ['required', Rule::in(['ADMIN', 'TECNICO', 'INVITADO'])],
            'cliente_id' => 'nullable|required_if:rol,INVITADO|exists:clientes,id',
            'activo'     => 'boolean',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggleState(User $user)
    {
        $user->update(['activo' => !$user->activo]);

        $status = $user->activo ? 'activado' : 'desactivado';
        return redirect()->back()->with('success', "Usuario {$status} correctamente.");
    }
}
