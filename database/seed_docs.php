<?php
/**
 * IDS Fincas — Semilla de DOCUMENTOS de ejemplo para el vecino de prueba.
 * Genera PDFs placeholder y los asigna a las comunidades de vecino@idsfincas.es.
 * Ejecutar una vez por URL (/database/seed_docs.php) y luego BORRAR el archivo.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

if (PHP_SAPI !== 'cli' && !config('dev_mode')) { http_response_code(403); exit('Deshabilitado en producción.'); }
header('Content-Type: text/plain; charset=utf-8');

$pdo = db();
$uploadsDir = (string) config('uploads_dir');
if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0775, true);
if (!is_writable($uploadsDir)) exit("La carpeta de subidas no existe o no tiene permisos de escritura:\n$uploadsDir\n");

/** Genera un PDF mínimo válido de una página con un título. */
function make_pdf(string $title): string {
    $t = preg_replace('/[()\\\\]/', ' ', $title);
    $content = "BT /F1 20 Tf 72 770 Td ($t) Tj ET\n"
             . "BT /F1 12 Tf 72 740 Td (Documento de ejemplo - IDS Fincas) Tj ET";
    $objs = [
        1 => "<</Type/Catalog/Pages 2 0 R>>",
        2 => "<</Type/Pages/Kids[3 0 R]/Count 1>>",
        3 => "<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Resources<</Font<</F1 5 0 R>>>>/Contents 4 0 R>>",
        4 => "<</Length " . strlen($content) . ">>\nstream\n$content\nendstream",
        5 => "<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>",
    ];
    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objs as $i => $o) { $offsets[$i] = strlen($pdf); $pdf .= "$i 0 obj\n$o\nendobj\n"; }
    $xref = strlen($pdf);
    $n = count($objs) + 1;
    $pdf .= "xref\n0 $n\n0000000000 65535 f \n";
    for ($i = 1; $i < $n; $i++) $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    $pdf .= "trailer\n<</Size $n/Root 1 0 R>>\nstartxref\n$xref\n%%EOF";
    return $pdf;
}

function slug(string $s): string {
    $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    $s = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $s));
    return trim($s, '-');
}

// Vecino de prueba y admin (para uploaded_by).
$vecino = $pdo->query("SELECT id FROM users WHERE email='vecino@idsfincas.es' LIMIT 1")->fetch();
if (!$vecino) exit("No existe vecino@idsfincas.es. Ejecuta antes el seed principal.\n");
$adminRow = $pdo->query("SELECT id FROM users WHERE rol='admin' LIMIT 1")->fetch();
$adminId  = $adminRow ? (int)$adminRow['id'] : null;

// Mapa de categorías por nombre -> id.
$cats = [];
foreach ($pdo->query('SELECT id, nombre FROM doc_categories')->fetchAll() as $c) $cats[$c['nombre']] = (int)$c['id'];

// Comunidades del vecino.
$st = $pdo->prepare(
    'SELECT c.id, c.nombre FROM user_communities uc JOIN communities c ON c.id=uc.community_id WHERE uc.user_id=? ORDER BY c.nombre'
);
$st->execute([(int)$vecino['id']]);
$coms = $st->fetchAll();
if (!$coms) exit("El vecino no tiene comunidades asignadas.\n");

// Plantilla de documentos por comunidad.
$plantilla = [
    ['Acta junta ordinaria 2026', 'Actas', 'Acuerdos de la junta general ordinaria.'],
    ['Presupuesto anual 2026', 'Presupuestos', 'Presupuesto de ingresos y gastos del ejercicio.'],
    ['Contrato mantenimiento ascensor', 'Contratos', 'Contrato anual con la empresa de ascensores.'],
    ['Normas de régimen interno', 'Normativa', 'Reglamento de convivencia y uso de zonas comunes.'],
];

$ins = $pdo->prepare(
    'INSERT INTO documents (community_id, category_id, titulo, descripcion, filename_real, filename_original, filesize, mime, uploaded_by)
     VALUES (?,?,?,?,?,?,?,?,?)'
);

$creados = 0;
foreach ($coms as $com) {
    $cid = (int)$com['id'];
    // Evitar duplicar si ya hay documentos en la comunidad.
    $has = $pdo->prepare('SELECT COUNT(*) FROM documents WHERE community_id=?');
    $has->execute([$cid]);
    if ((int)$has->fetchColumn() > 0) { echo "· {$com['nombre']}: ya tiene documentos, omitida.\n"; continue; }

    foreach ($plantilla as [$titulo, $catNombre, $desc]) {
        $bytes = make_pdf($titulo . ' — ' . $com['nombre']);
        $real  = bin2hex(random_bytes(16)) . '.pdf';
        $dest  = rtrim($uploadsDir, '/\\') . DIRECTORY_SEPARATOR . $real;
        file_put_contents($dest, $bytes);
        @chmod($dest, 0640);
        $orig  = slug($titulo) . '.pdf';
        $ins->execute([$cid, $cats[$catNombre] ?? null, $titulo, $desc, $real, $orig, strlen($bytes), 'application/pdf', $adminId]);
        $creados++;
    }
    echo "· {$com['nombre']}: 4 documentos creados.\n";
}

echo "\nListo. Documentos de ejemplo creados: $creados\n";
echo "IMPORTANTE: borra este archivo (database/seed_docs.php) ahora.\n";
