<?php
/**
 * IDS Fincas — Descarga protegida de documentos (IDOR-safe).
 * Comprueba sesión + pertenencia a la comunidad antes de servir el archivo.
 */
require_once __DIR__ . '/../includes/auth.php';

$user = require_login();
$docId = (int)($_GET['doc'] ?? 0);
if ($docId <= 0) { http_response_code(400); exit('Petición no válida.'); }

$pdo = db();
$st = $pdo->prepare('SELECT * FROM documents WHERE id=? LIMIT 1');
$st->execute([$docId]);
$doc = $st->fetch();
if (!$doc) { http_response_code(404); exit('Documento no encontrado.'); }

// --- Control de acceso: admin todo; usuario solo su(s) comunidad(es) ---
if ($user['rol'] !== 'admin') {
    $chk = $pdo->prepare('SELECT 1 FROM user_communities WHERE user_id=? AND community_id=? LIMIT 1');
    $chk->execute([(int)$user['id'], (int)$doc['community_id']]);
    if (!$chk->fetch()) {
        audit((int)$user['id'], 'download_denied', '#' . $docId);
        http_response_code(403);
        exit('No tiene acceso a este documento.');
    }
}

$path = rtrim((string)config('uploads_dir'), '/\\') . DIRECTORY_SEPARATOR . $doc['filename_real'];
if (!is_file($path)) { http_response_code(404); exit('El archivo ya no está disponible.'); }

audit((int)$user['id'], 'download', '#' . $docId . ' ' . $doc['titulo']);

// PDFs e imágenes se muestran en el navegador; el resto se descargan.
$inlineTypes = ['application/pdf','image/jpeg','image/png','image/gif','image/webp'];
$disposition = in_array($doc['mime'], $inlineTypes, true) ? 'inline' : 'attachment';

// Limpia cualquier salida previa para no corromper el binario.
while (ob_get_level() > 0) ob_end_clean();

header('Content-Type: ' . $doc['mime']);
header('Content-Length: ' . (int)$doc['filesize']);
$fnAscii = preg_replace('/[^\x20-\x7E]/', '_', $doc['filename_original']); // fallback ASCII
header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $fnAscii) . '"; filename*=UTF-8\'\'' . rawurlencode($doc['filename_original']));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
exit;
