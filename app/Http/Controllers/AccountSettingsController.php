<?php

namespace App\Http\Controllers;

use App\Models\PromoSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AccountSettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $subscribedToPromos = $user->role === 'cliente'
            ? PromoSubscriber::where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->exists()
            : false;

        return view('configuracion', compact('user', 'subscribedToPromos'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Tu contraseña actual no es correcta.');
        }

        $user->password = $request->password;
        $user->save();

        return back()->with('success', '¡Contraseña actualizada con éxito!');
    }

    public function togglePromoEmail(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'cliente') {
            return back()->with('error', 'Esta opción solo está disponible para clientes.');
        }

        $existing = PromoSubscriber::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('success', 'Ya no recibirás correos de promociones.');
        }

        PromoSubscriber::create([
            'email' => $user->email,
            'user_id' => $user->id,
            'unsubscribe_token' => Str::random(48),
        ]);

        return back()->with('success', '¡Listo! Te avisaremos por correo cuando haya nuevas promociones.');
    }
}
