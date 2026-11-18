<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MlEngineService;
use Illuminate\Http\Request;

class MlController extends Controller
{
    protected $mlService;

    public function __construct(MlEngineService $mlService)
    {
        $this->mlService = $mlService;
    }

    public function index()
    {
        $customerAnalysis = $this->mlService->getCustomerAnalysis();
        $demandForecast = $this->mlService->getDemandForecast();
        $associationRules = $this->mlService->getAssociationRules();

        return view('admin.ml.index', compact('customerAnalysis', 'demandForecast', 'associationRules'));
    }

    public function sendPromotionCoupon(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // No hay cupones en el sistema (el pago no los acepta), así que no se envía nada: decir «enviado»
        // engañaba al administrador, y ofrecer al cliente un descuento que no puede usar sería publicidad
        // engañosa. Las promociones se publican en Promociones y solo llegan a quien aceptó recibirlas.
        return response()->json([
            'success' => false,
            'message' => 'Todavía no hay cupones en el sistema, así que no se envió nada. Para ofrecer un descuento, publícalo en Promociones: llega por correo solo a quien aceptó recibir promociones.',
        ], 422);
    }
}
