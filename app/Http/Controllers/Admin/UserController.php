<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountSessions;
use App\Services\SystemOwner;
use App\Support\PasswordPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * El dueño administra al personal. Las rutas ya exigen ser el dueño y haber confirmado la contraseña
 * hace poco (middleware owner y password.recent). Aquí solo se tocan cuentas del personal: los clientes
 * manejan su propia cuenta (cambio de contraseña, eliminación) y el dueño no se puede borrar ni pausar.
 */
class UserController extends Controller
{
    /** Puestos que se pueden dar desde el panel. */
    public const STAFF_ROLES = ['admin', 'cocinero', 'empleado', 'limpieza'];

    public function index()
    {
        $users = User::whereIn('role', self::STAFF_ROLES)->orderBy('name')->get();

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'telefono' => ['nullable', 'digits:10'],
            'password' => ['required', 'string', PasswordPolicy::rule()],
            'role' => ['required', Rule::in(self::STAFF_ROLES)],
        ], PasswordPolicy::messages() + [
            'role.in' => 'Elige un puesto de la lista.',
            'telefono.digits' => 'El teléfono debe tener 10 dígitos.',
        ]);

        $nuevo = User::create([
            'name' => $datos['name'],
            'email' => mb_strtolower($datos['email']),
            'telefono' => $datos['telefono'] ?? null,
            'password' => $datos['password'],
            'role' => $datos['role'],
            'is_active' => true,
            'points' => 0,
        ]);

        $this->registrar('alta de personal', $nuevo);

        return back()->with('success', 'Empleado registrado correctamente.');
    }

    /** Nueva contraseña para un empleado: se cierran sus sesiones abiertas. */
    public function update(Request $request, $id)
    {
        $user = $this->empleado($id);

        $datos = $request->validate([
            'password' => ['required', 'string', PasswordPolicy::rule()],
        ], PasswordPolicy::messages());

        $user->password = $datos['password'];
        $user->save();
        AccountSessions::revokeAll($user, $user->is($request->user()) ? $request->session()->getId() : null);

        $this->registrar('cambio de contraseña de personal', $user);

        return back()->with('success', 'Contraseña de ' . $user->name . ' actualizada.');
    }

    public function destroy(Request $request, $id)
    {
        $user = $this->empleado($id);

        if ($user->is_owner || $user->is($request->user())) {
            return back()->with('error', 'No puedes eliminar al dueño del sistema ni tu propia cuenta.');
        }

        AccountSessions::revokeAll($user);
        $this->registrar('baja de personal', $user);
        $user->delete();

        return back()->with('success', 'Empleado eliminado del sistema.');
    }

    /** Pausar o reactivar. Al pausar, la persona sale en ese momento de la web y de las apps. */
    public function toggleStatus(Request $request, $id)
    {
        $user = $this->empleado($id);

        if ($user->is_owner || $user->is($request->user())) {
            return back()->with('error', 'No puedes pausar al dueño del sistema ni tu propia cuenta.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        if (!$user->is_active) {
            AccountSessions::revokeAll($user);
        }

        $this->registrar($user->is_active ? 'reactivación de personal' : 'suspensión de personal', $user);

        $estado = $user->is_active ? 'reactivado' : 'suspendido temporalmente';

        return back()->with('success', "El empleado {$user->name} ha sido {$estado}.");
    }

    /**
     * El dueño pasa la propiedad del sistema a otro administrador (por ejemplo, del desarrollador al dueño
     * del restaurante). Pide su contraseña en ese momento aunque la haya confirmado hace poco, porque no
     * tiene vuelta atrás: desde ahí solo el nuevo dueño puede devolverla.
     */
    public function transferOwnership(Request $request, $id)
    {
        $nuevo = $this->empleado($id);

        $request->validate(['current_password' => ['required', 'string']], [
            'current_password.required' => 'Escribe tu contraseña para confirmar.',
        ]);

        if (!Hash::check($request->input('current_password'), $request->user()->password)) {
            return back()->with('error', 'La contraseña no es correcta. No se cambió el dueño.');
        }

        if ($nuevo->is($request->user())) {
            return back()->with('error', 'Esa cuenta ya es la dueña del sistema.');
        }

        try {
            SystemOwner::transfer($nuevo, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.dashboard')
            ->with('success', "Listo. {$nuevo->name} es ahora el dueño del sistema. Tu cuenta sigue siendo administrador.");
    }

    private function empleado($id): User
    {
        return User::whereIn('role', self::STAFF_ROLES)->findOrFail($id);
    }

    /** Bitácora de quién cambió qué cuenta del personal (sin contraseñas). */
    private function registrar(string $accion, User $afectado): void
    {
        Log::channel('single')->notice('Panel: ' . $accion, [
            'por_usuario_id' => auth()->id(),
            'cuenta_id' => $afectado->id,
            'rol' => $afectado->role,
        ]);
    }
}
