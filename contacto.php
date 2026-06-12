<?php
/**
 * IDS Fincas — Recepción de solicitudes de presupuesto desde la landing.
 */
require_once __DIR__ . '/includes/auth.php';

$B = base_url();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($B . '/#contacto');
}
csrf_check();

// Honeypot: si el campo oculto viene relleno, es un bot. Fingimos éxito.
if (trim((string)($_POST['website'] ?? '')) !== '') {
    redirect($B . '/?enviado=1#contacto');
}

$nombre   = trim((string)($_POST['nombre'] ?? ''));
$telefono = trim((string)($_POST['telefono'] ?? ''));
$mensaje  = trim((string)($_POST['mensaje'] ?? ''));

if ($nombre === '' || $telefono === '') {
    redirect($B . '/?error=1#contacto');
}

db()->prepare('INSERT INTO leads (nombre, telefono, mensaje, ip) VALUES (?,?,?,?)')
    ->execute([
        mb_substr($nombre, 0, 120),
        mb_substr($telefono, 0, 40),
        $mensaje !== '' ? mb_substr($mensaje, 0, 2000) : null,
        client_ip(),
    ]);

redirect($B . '/?enviado=1#contacto');
