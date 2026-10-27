<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\News;
use App\Models\Promotion;

class PageController extends Controller
{
    public function index(\App\Services\MenuCatalog $catalog) {
        $products = $catalog->products();
        return view('pedido', compact('products'));
    }

    public function novedades()
    {
        $today = now()->toDateString();

        $baseQuery = News::where('active', 1)
            ->where(function ($query) use ($today) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $today);
            });

        $deportes = (clone $baseQuery)->where('category', 'Deportes')->orderBy('created_at', 'desc')->get();
        $avisos = (clone $baseQuery)->where('category', 'Avisos')->orderBy('created_at', 'desc')->get();
        $eventos = (clone $baseQuery)->where('category', 'Eventos')->orderBy('created_at', 'desc')->get();

        return view('novedades', compact('deportes', 'avisos', 'eventos'));
    }

    public function promociones()
    {
        $today = now()->toDateString();

        $promotions = Promotion::with('products')
            ->where('active', 1)
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $today);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('promociones', compact('promotions'));
    }

    public function enviarContacto(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'type' => 'required|in:pregunta,sugerencia,queja',
            'subject' => 'required|string|max:255',
            'message' => 'required|string'
        ]);

        \App\Models\ContactMessage::create([
            'name' => $request->name,
            'email' => $request->email,
            'type' => $request->type,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'pendiente'
        ]);

        $mensajeRespuesta = match($request->type) {
            'queja' => 'Agradecemos sinceramente que nos compartas esta situación. Estamos trabajando para resolverlo a la brevedad y, de ser necesario, nos pondremos en contacto contigo para darle seguimiento.',
            'sugerencia' => '¡Muchas gracias por tu sugerencia! Valoramos enormemente las ideas de nuestros clientes para seguir mejorando y brindar la mejor experiencia en La 501 Sports.',
            'pregunta' => 'Hemos recibido tu consulta. Te daremos respuesta lo más pronto posible a través de tu correo electrónico. Recuerda que también puedes acercarte a cualquier miembro de nuestro equipo durante tu visita.'
        };

        return back()->with('success', $mensajeRespuesta);
    }
}