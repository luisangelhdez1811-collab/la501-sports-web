<?php

namespace App\Http\Controllers;

use App\Services\LegalDocuments;
use Illuminate\Http\Request;

/**
 * B-32 / B-39: pantalla donde una cuenta acepta la versión vigente del Aviso de Privacidad y de los
 * Términos (clientes) o del aviso para personal. Se guarda la versión aceptada y la fecha.
 */
class LegalAcceptanceController extends Controller
{
    public function show(Request $request)
    {
        $pendientes = LegalDocuments::pendingFor($request->user());
        if ($pendientes === []) {
            return redirect()->intended('/');
        }

        $documentos = collect($pendientes)->mapWithKeys(fn ($doc) => [$doc => LegalDocuments::get($doc)]);
        $nuevo = $request->user()->legal_accepted_at === null && $request->user()->aviso_privacidad_aceptado_at === null;

        return view('legal.accept', compact('documentos', 'nuevo'));
    }

    public function store(Request $request)
    {
        $request->validate(['acepto' => ['accepted']], [
            'acepto.accepted' => 'Marca la casilla para aceptar los documentos y continuar.',
        ]);

        LegalDocuments::accept($request->user(), LegalDocuments::pendingFor($request->user()));

        return redirect()->intended('/')->with('success', 'Gracias. Guardamos tu aceptación.');
    }
}
