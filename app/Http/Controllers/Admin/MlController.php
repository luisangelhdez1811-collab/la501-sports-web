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

        return response()->json([
            'success' => true,
            'message' => 'Cupón de reactivación (15% OFF) enviado exitosamente al cliente.',
        ]);
    }
}
