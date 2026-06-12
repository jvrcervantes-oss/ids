<?php
/**
 * IDS Fincas — Semilla de datos de prueba.
 * Ejecutar UNA vez tras importar schema.sql:
 *   - CLI:        php database/seed.php
 *   - Navegador:  /database/seed.php   (solo permitido en dev_mode)
 *
 * Crea: 1 admin, 1 vecino de prueba y 2 comunidades (el vecino en ambas,
 * para validar el modelo multi-comunidad).
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

$isCli = (PHP_SAPI === 'cli');
if (!$isCli && !config('dev_mode')) {
    http_response_code(403);
    exit('Semilla deshabilitada en producción.');
}
header('Content-Type: text/plain; charset=utf-8');

$pdo = db();

// Evitar duplicar si ya hay usuarios.
$exists = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($exists > 0) {
    exit("Ya existen usuarios ({$exists}). Semilla omitida para no duplicar.\n");
}

// --- Comunidades ---
$pdo->prepare('INSERT INTO communities (nombre, direccion, descripcion) VALUES (?,?,?)')
    ->execute(['Residencial Los Olivos', 'Av. Juan Carlos I, 30009 Murcia', 'Comunidad de 48 viviendas con zonas comunes y piscina.']);
$com1 = (int) $pdo->lastInsertId();

$pdo->prepare('INSERT INTO communities (nombre, direccion, descripcion) VALUES (?,?,?)')
    ->execute(['Edificio Plaza Mayor 4', 'Plaza Mayor 4, 30201 Cartagena', 'Edificio de 12 viviendas en casco histórico.']);
$com2 = (int) $pdo->lastInsertId();

// --- Usuarios ---
$adminPass  = 'IDSadmin2026!';
$vecinoPass = 'vecino2026';

$pdo->prepare('INSERT INTO users (nombre, email, password_hash, rol, activo) VALUES (?,?,?,?,1)')
    ->execute(['Administrador IDS', 'admin@idsfincas.es', password_hash($adminPass, PASSWORD_DEFAULT), 'admin']);
$adminId = (int) $pdo->lastInsertId();

$pdo->prepare('INSERT INTO users (nombre, email, password_hash, rol, activo) VALUES (?,?,?,?,1)')
    ->execute(['Vecino de Prueba', 'vecino@idsfincas.es', password_hash($vecinoPass, PASSWORD_DEFAULT), 'user']);
$vecinoId = (int) $pdo->lastInsertId();

// Vecino en ambas comunidades.
$uc = $pdo->prepare('INSERT INTO user_communities (user_id, community_id) VALUES (?,?)');
$uc->execute([$vecinoId, $com1]);
$uc->execute([$vecinoId, $com2]);

echo "Semilla creada correctamente.\n\n";
echo "ADMIN  → admin@idsfincas.es  /  {$adminPass}\n";
echo "VECINO → vecino@idsfincas.es /  {$vecinoPass}  (en 2 comunidades)\n\n";
echo "IMPORTANTE: borra este archivo (seed.php) tras usarlo en producción.\n";
