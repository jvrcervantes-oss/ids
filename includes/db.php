<?php
/**
 * IDS Fincas — Capa de base de datos (PDO singleton)
 */
declare(strict_types=1);

function config(?string $key = null)
{
    static $cfg = null;
    if ($cfg === null) {
        // Se busca el config en varias ubicaciones, priorizando las EXTERNAS
        // a la carpeta de despliegue (Git borra lo gitignored en cada deploy).
        $docroot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $candidates = [
            getenv('IDS_CONFIG') ?: null,            // ruta explícita por variable de entorno
            __DIR__ . '/../../ids_config.php',        // un nivel por encima de la raíz del repo (recomendado)
            $docroot ? dirname($docroot) . '/ids_config.php' : null, // junto a public_html
            __DIR__ . '/../private/config.php',       // dentro del proyecto (dev / fallback)
        ];
        $path = null;
        foreach ($candidates as $c) {
            if ($c && is_file($c)) { $path = $c; break; }
        }
        if ($path === null) {
            http_response_code(500);
            exit('Falta el archivo de configuración (ids_config.php fuera del web root, o private/config.php).');
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
