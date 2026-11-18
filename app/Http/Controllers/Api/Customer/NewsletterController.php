<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\Newsletter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    /** Igual que la web: sin sesión (o con otro correo) llega un correo para confirmar. */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);

        $estado = Newsletter::subscribe($validated['email'], $request->user('sanctum') ?? $request->user('jwt'));

        return response()->json([
            'success' => true,
            'status' => $estado,
            'message' => Newsletter::message($estado),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
