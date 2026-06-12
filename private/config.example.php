<?php
/**
 * IDS Fincas — Configuración (PLANTILLA)
 * ---------------------------------------------------------------
 * Copia este archivo a `config.php` y rellena los valores reales.
 * `config.php` NO se sube a git (está en .gitignore) y vive fuera
 * de la zona pública gracias al .htaccess de /private.
 */

return [
    // --- Base de datos (Hostinger → MySQL) ---
    'db' => [
        'host'    => 'localhost',
        'name'    => 'NOMBRE_BD',
        'user'    => 'USUARIO_BD',
        'pass'    => 'CONTRASEÑA_BD',
        'charset' => 'utf8mb4',
    ],

    // --- Aplicación ---
    // URL base SIN barra final. Vacío = se autodetecta (útil sin dominio aún).
    'app_url'   => '',
    // Secreto para firmar/derivar tokens. Genera uno con: bin2hex(random_bytes(32))
    'app_secret'=> 'CAMBIA_ESTO_POR_64_CARACTERES_HEX_ALEATORIOS',

    // Carpeta privada donde se guardan los documentos subidos.
    // Por defecto la subcarpeta /uploads junto a este config.
    'uploads_dir' => __DIR__ . '/uploads',

    // DEV_MODE: si true, el alta de usuarios muestra el enlace de
    // "crear contraseña" en pantalla en vez de enviarlo por email.
    // Útil mientras no hay dominio/SMTP. Poner false en producción real.
    'dev_mode'  => true,

    // --- Email (SMTP Hostinger) — se activa cuando haya dominio ---
    'mail' => [
        'enabled'   => false,
        'host'      => 'smtp.hostinger.com',
        'port'      => 465,
        'secure'    => 'ssl',
        'user'      => 'noreply@TU-DOMINIO',
        'pass'      => '',
        'from_mail' => 'noreply@TU-DOMINIO',
        'from_name' => 'IDS Fincas',
    ],

    // --- Seguridad ---
    'max_login_attempts' => 5,    // intentos fallidos...
    'login_window_min'   => 15,   // ...en esta ventana (minutos) antes de bloquear
    'token_ttl_hours'    => 48,   // validez del enlace de crear contraseña
    'max_upload_mb'      => 25,   // tamaño máximo por documento
];
