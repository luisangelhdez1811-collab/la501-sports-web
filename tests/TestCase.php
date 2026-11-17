<?php

namespace Tests;

use App\Models\User;
use App\Services\LegalDocuments;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Las pruebas actúan con una cuenta que ya aceptó la versión vigente del Aviso y los Términos (o del
     * aviso para personal), como cualquier usuario al día. Las que prueban ese flujo usan be().
     */
    public function actingAs(Authenticatable $user, $guard = null)
    {
        if ($user instanceof User && $user->exists && LegalDocuments::pendingFor($user) !== []) {
            LegalDocuments::accept($user, LegalDocuments::pendingFor($user));
        }

        return parent::actingAs($user, $guard);
    }
}
