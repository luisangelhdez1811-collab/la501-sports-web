<?php

namespace App\Http\Controllers;

use App\Models\PromoSubscriber;
use App\Services\Newsletter;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $estado = Newsletter::subscribe($request->input('email'), $request->user());

        return back()->with('success', Newsletter::message($estado));
    }

    /** El enlace del correo de confirmación (doble opt-in). */
    public function confirm(string $token)
    {
        return view('newsletter.confirmada', ['success' => Newsletter::confirm($token)]);
    }

    // Da de baja un correo con su token único
    public function unsubscribe(string $token)
    {
        $subscriber = PromoSubscriber::where('unsubscribe_token', $token)->first();

        if ($subscriber) {
            $subscriber->delete();
            return view('newsletter.baja', ['success' => true]);
        }

        return view('newsletter.baja', ['success' => false]);
    }
}
