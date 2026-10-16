<?php

/*
 * Conexión de la API ligera (Alexa / consultas públicas).
 *
 * Las credenciales NO se escriben en el código: se leen del .env de Laravel
 * (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD), que vive fuera del
 * repositorio. Si existen como variables de entorno del servidor, tienen prioridad.
 */

function la501EnvFile(): array
{
    static $valores = null;
    if ($valores !== null) {
        return $valores;
    }

    $valores = [];
    $archivo = dirname(__DIR__) . '/.env';
    if (!is_readable($archivo)) {
        return $valores;
    }

    foreach (file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || $linea[0] === '#' || strpos($linea, '=') === false) {
            continue;
        }
        [$clave, $valor] = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor);
        // Quita comillas envolventes: DB_PASSWORD="abc" o 'abc'
        if (strlen($valor) >= 2 && ($valor[0] === '"' || $valor[0] === "'") && substr($valor, -1) === $valor[0]) {
            $valor = substr($valor, 1, -1);
        }
        $valores[$clave] = $valor;
    }

    return $valores;
}

function la501Env(string $clave, string $porDefecto = ''): string
{
    $entorno = getenv($clave);
    if ($entorno !== false && $entorno !== '') {
        return $entorno;
    }

    return la501EnvFile()[$clave] ?? $porDefecto;
}

define('DB_HOST', la501Env('DB_HOST', '127.0.0.1'));
define('DB_USER', la501Env('DB_USERNAME'));
define('DB_PASS', la501Env('DB_PASSWORD'));
define('DB_NAME', la501Env('DB_DATABASE'));

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(['error' => 'Error de conexión']));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['error' => 'Método no permitido']));
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
