<?php

namespace App\Http\Controllers;

use App\Services\LegalDocuments;

/** Páginas públicas de los textos legales vigentes (B-18, B-39). */
class LegalPageController extends Controller
{
    public function privacy()
    {
        return view('legal.page', ['documento' => LegalDocuments::get(LegalDocuments::PRIVACY)]);
    }

    public function terms()
    {
        return view('legal.page', ['documento' => LegalDocuments::get(LegalDocuments::TERMS)]);
    }

    public function staff()
    {
        return view('legal.page', ['documento' => LegalDocuments::get(LegalDocuments::STAFF)]);
    }
}
