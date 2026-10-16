<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PromoSubscriber;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountDeletionService
{
    public function delete(User $user): void
    {
        $originalEmail = $user->email;

        if ($user->avatar) {
            $path = public_path($user->avatar);
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        Order::where('user_id', $user->id)->update([
            'customer_name' => 'Cliente eliminado',
            'customer_phone' => null,
            'customer_address' => null,
            'customer_email' => null,
        ]);

        PromoSubscriber::where('user_id', $user->id)->orWhere('email', $originalEmail)->delete();

        // Revoca todos los tokens de Sanctum de la app (si tiene).
        $user->tokens()->delete();

        $user->forceFill([
            'name' => 'Usuario eliminado',
            'email' => 'eliminado_' . $user->id . '_' . time() . '@la501.invalid',
            'telefono' => null,
            'pregunta_secreta' => null,
            'respuesta_secreta' => null,
            'avatar' => null,
            'password' => Hash::make(Str::random(40)),
            'is_active' => 0,
        ])->save();
    }
}
