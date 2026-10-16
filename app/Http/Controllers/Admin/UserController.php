<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        if (Auth::id() !== 2) {
            return redirect()->route('admin.dashboard')->with('error', 'No tienes permisos para ver el personal.');
        }

        $users = User::all();
        return view('admin.users.index', compact('users'));
    }

    // Guardar un nuevo empleado
    public function store(Request $request)
    {
        if (Auth::id() !== 2) {
            return abort(403);
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'role'     => 'required|string',
        ]);

        User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'telefono'  => $request->telefono,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'is_active' => true,
            'points'    => 0,
        ]);

        return back()->with('success', 'Empleado registrado correctamente.');
    }

    // Actualizar contraseña de un empleado
    public function update(Request $request, $id)
    {
        if (Auth::id() !== 2) {
            return abort(403);
        }

        $user = User::findOrFail($id);

        $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('success', 'Contraseña de ' . $user->name . ' actualizada.');
    }

    // Despedir un empleado
    public function destroy($id)
    {
        if (Auth::id() !== 2) {
            return abort(403);
        }

        if ($id == 1) {
            return back()->with('error', 'No puedes borrar al dueño del sistema.');
        }

        $user = User::findOrFail($id);
        $user->delete();

        return back()->with('success', 'Empleado eliminado del sistema.');
    }

    // Pausar o Reactivar Empleado
    public function toggleStatus($id)
    {
        if (Auth::id() !== 2) {
            return abort(403);
        }

        if ($id == 2) {
            return back()->with('error', 'No puedes pausar al dueño del sistema.');
        }

        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        $estado = $user->is_active ? 'reactivado' : 'suspendido temporalmente';
        return back()->with('success', "El empleado {$user->name} ha sido {$estado}.");
    }
}
