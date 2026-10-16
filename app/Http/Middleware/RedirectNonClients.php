<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectNonClients
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $role = trim(strtolower(Auth::user()->role));
            if (in_array($role, ['admin', 'cocinero', 'empleado'])) {
                return $this->redirectByRole($role);
            }
        }

        return $next($request);
    }

    private function redirectByRole(string $role)
    {
        if ($role === 'cocinero') {
            return redirect()->route('kitchen.index');
        }
        if ($role === 'empleado') {
            return redirect()->route('mesero.mesas');
        }
        return redirect()->route('admin.dashboard');
    }
}
