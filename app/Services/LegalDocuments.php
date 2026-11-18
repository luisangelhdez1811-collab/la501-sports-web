<?php

namespace App\Services;

use App\Models\User;
use InvalidArgumentException;

/**
 * Textos legales versionados (B-18, B-32, B-39). Cada documento vive en resources/legal/<doc>/<versión>.php
 * como una lista de bloques (h2, h3, p, list, table) que pintan la web y, con el mismo formato, la app.
 */
class LegalDocuments
{
    public const PRIVACY = 'privacy';
    public const TERMS = 'terms';
    public const STAFF = 'staff';

    private const VERSION_KEYS = [
        self::PRIVACY => 'privacy_version',
        self::TERMS => 'terms_version',
        self::STAFF => 'staff_privacy_version',
    ];

    public static function version(string $doc): string
    {
        if (!isset(self::VERSION_KEYS[$doc])) {
            throw new InvalidArgumentException("Documento legal desconocido: {$doc}");
        }

        return (string) config('legal.' . self::VERSION_KEYS[$doc]);
    }

    /** El documento en la versión pedida (por defecto la vigente), con su versión incluida. */
    public static function get(string $doc, ?string $version = null): array
    {
        $version ??= self::version($doc);
        if (!preg_match('/^\d{4}-\d{2}(-\d{2})?$/', $version)) {
            throw new InvalidArgumentException("Versión inválida: {$version}");
        }

        $archivo = resource_path("legal/{$doc}/{$version}.php");
        if (!is_file($archivo)) {
            throw new InvalidArgumentException("No existe la versión {$version} de {$doc}");
        }

        return ['version' => $version] + require $archivo;
    }

    /**
     * ¿La cuenta debe aceptar una versión nueva? Los clientes aceptan el Aviso y los Términos; el
     * personal, su propio aviso.
     */
    public static function pendingFor(User $user): array
    {
        $pendientes = [];

        if (in_array($user->role, ['admin', 'empleado', 'mesero', 'cocinero'], true)) {
            if ($user->staff_privacy_version_accepted !== self::version(self::STAFF)) {
                $pendientes[] = self::STAFF;
            }

            return $pendientes;
        }

        if ($user->privacy_version_accepted !== self::version(self::PRIVACY)) {
            $pendientes[] = self::PRIVACY;
        }
        if ($user->terms_version_accepted !== self::version(self::TERMS)) {
            $pendientes[] = self::TERMS;
        }

        return $pendientes;
    }

    /** Guarda en la cuenta las versiones vigentes de los documentos aceptados. */
    public static function accept(User $user, array $docs): void
    {
        foreach ($docs as $doc) {
            $columna = match ($doc) {
                self::PRIVACY => 'privacy_version_accepted',
                self::TERMS => 'terms_version_accepted',
                self::STAFF => 'staff_privacy_version_accepted',
            };
            $user->{$columna} = self::version($doc);
        }
        $user->legal_accepted_at = now();
        $user->save();
    }
}
