<?php

namespace App\Http\Controllers;

use App\Models\PromoSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{

    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $subscriber = PromoSubscriber::firstOrNew(['email' => $request->email]);

        if (!$subscriber->exists) {
            $subscriber->unsubscribe_token = Str::random(48);
        }

        if (auth()->check() && auth()->user()->email === $request->email) {
            $subscriber->user_id = auth()->id();
        }

        $subscriber->save();

        return back()->with('success', '¡Listo! Te avisaremos por correo cuando haya nuevas promociones.');
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
