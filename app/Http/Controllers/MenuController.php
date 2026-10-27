<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MenuCatalog;

class MenuController extends Controller
{
    public function index(MenuCatalog $catalog)
    {
        $products = $catalog->products();

        return view('menu', compact('products'));
    }
}
