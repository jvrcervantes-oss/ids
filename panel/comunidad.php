<?php
/**
 * IDS Fincas — Panel de usuario · Documentos de una comunidad
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';

$user = require_login();
$pdo  = db();
$cid  = (int)($_GET['id'] ?? 0);

// Comunidad + verificación de acceso.
$st = $pdo->prepare('SELECT * FROM communities WHERE id=?');
$st->execute([$cid]);
$com = $st->fetch();
if (!$com) { http_response_code(404); exit('Comunidad no encontrada.'); }

if ($user['rol'] !== 'admin') {
    $chk = $pdo->prepare('SELECT 1 FROM user_communities WHERE user_id=? AND community_id=? LIMIT 1');
    $chk->execute([(int)$user['id'], $cid]);
    if (!$chk->fetch()) { http_response_code(403); exit('No tiene acceso a esta comunidad.'); }
}

// Documentos ordenados por categoría.
$st = $pdo->prepare(
    'SELECT d.*, dc.nombre AS categoria, dc.orden AS cat_orden
     FROM documents d
     LEFT JOIN doc_categories dc ON dc.id=d.category_id
     WHERE d.community_id=?
     ORDER BY (dc.orden IS NULL), dc.orden, dc.nombre, d.uploaded_at DESC'
);
$st->execute([$cid]);
$docs = $st->fetchAll();

// Agrupar por categoría.
$grupos = [];
foreach ($docs as $d) {
    $cat = $d['categoria'] ?: 'Otros documentos';
    $grupos[$cat][] = $d;
}

$b = base_url();
$nav = ($user['rol'] === 'admin' ? '<a class="act" href="' . e($b) . '/admin/">Admin</a>' : '')
     . '<a class="act" href="' . e($b) . '/panel/logout.php">Salir</a>';
page_top($com['nombre'], ['nav' => $nav]);
?>
<div class="wrap" style="padding:36px 24px">
  <a href="<?= e($b) ?>/panel/dashboard.php" class="muted" style="text-decoration:none;font-size:14px">← Mis comunidades</a>
  <h1 style="font-size:30px;margin-top:10px"><?= e($com['nombre']) ?></h1>
  <?php if ($com['direccion']): ?><p class="muted" style="margin-top:-4px"><?= e($com['direccion']) ?></p><?php endif; ?>
  <?php if ($com['descripcion']): ?><p style="max-width:65ch"><?= e($com['descripcion']) ?></p><?php endif; ?>

  <?php if (!$docs): ?>
    <div class="card" style="margin-top:20px"><p class="muted" style="margin:0">Aún no hay documentos disponibles en esta comunidad.</p></div>
  <?php else: foreach ($grupos as $cat => $items): ?>
    <section style="margin-top:30px">
      <h2 style="font-size:20px;padding-bottom:8px;border-bottom:2px solid var(--linea)"><?= e($cat) ?> <span class="muted" style="font-weight:400;font-size:15px">· <?= count($items) ?></span></h2>
      <div style="display:flex;flex-direction:column;gap:10px;margin-top:14px">
        <?php foreach ($items as $d): ?>
          <a href="<?= e($b) ?>/panel/download.php?doc=<?= (int)$d['id'] ?>" class="card"
             style="text-decoration:none;display:flex;align-items:center;gap:16px;padding:16px 18px">
            <div style="width:40px;height:40px;border-radius:10px;background:var(--azul);display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M7 3h7l5 5v13a0 0 0 0 1 0 0H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z" stroke="#3447AA" stroke-width="1.5" stroke-linejoin="round"/><path d="M14 3v5h5" stroke="#3447AA" stroke-width="1.5" stroke-linejoin="round"/></svg>
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-weight:600"><?= e($d['titulo']) ?></div>
              <?php if ($d['descripcion']): ?><div class="muted" style="font-size:14px"><?= e($d['descripcion']) ?></div><?php endif; ?>
              <div class="muted" style="font-size:13px"><?= e(format_bytes((int)$d['filesize'])) ?> · <?= e(date('d/m/Y', strtotime($d['uploaded_at']))) ?></div>
            </div>
            <span class="btn btn-gold" style="min-height:40px;padding:9px 18px;flex-shrink:0">Descargar</span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; endif; ?>
</div>
<?php page_bottom();
