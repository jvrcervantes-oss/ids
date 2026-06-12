<?php
/**
 * IDS Fincas — Capa de base de datos (PDO singleton)
 */
declare(strict_types=1);

function config(?string $key = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $path = __DIR__ . '/../private/config.php';
        if (!is_file($path)) {
            http_response_code(500);
            exit('Falta private/config.php (copia config.example.php).');
        }
        $cfg = require $path;
    }
    if ($key === null) return $cfg;
    return $cfg[$key] ?? null;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config('db');
        $dsn = "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}";
        try {
            $pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            // No filtramos detalles de conexión al usuario.
            error_log('IDS DB error: ' . $e->getMessage());
            exit('No se pudo conectar con la base de datos.');
        }
    }
    return $pdo;
}
