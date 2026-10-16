<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\PromoSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);

        $subscriber = PromoSubscriber::firstOrNew(['email' => $validated['email']]);

        if (!$subscriber->exists) {
            $subscriber->unsubscribe_token = Str::random(48);
        }

        $user = $request->user('sanctum');
        if ($user && $user->email === $validated['email']) {
            $subscriber->user_id = $user->id;
        }

        $subscriber->save();

        return response()->json([
            'success' => true,
            'message' => '¡Listo! Te avisaremos por correo cuando haya nuevas promociones.',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
