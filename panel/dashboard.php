<?php
/**
 * IDS Fincas — Panel de usuario · Mis comunidades
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';

$user = require_login();
$pdo  = db();

$st = $pdo->prepare(
    'SELECT c.*,
       (SELECT COUNT(*) FROM documents d WHERE d.community_id=c.id) AS n_docs
     FROM user_communities uc
     JOIN communities c ON c.id=uc.community_id
     WHERE uc.user_id=?
     ORDER BY c.nombre'
);
$st->execute([(int)$user['id']]);
$coms = $st->fetchAll();

$b = base_url();
$nav = ($user['rol'] === 'admin' ? '<a class="act" href="' . e($b) . '/admin/">Admin</a>' : '')
     . '<a class="act" href="' . e($b) . '/panel/logout.php">Salir</a>';
page_top('Mi portal', ['nav' => $nav]);
?>
<div class="wrap" style="padding:40px 24px">
  <h1 style="font-size:30px">Hola, <?= e($user['nombre']) ?></h1>
  <p class="muted">Estas son sus comunidades y su documentación.</p>

  <?php if (!$coms): ?>
    <div class="card" style="margin-top:20px">
      <p class="muted" style="margin:0">Todavía no tiene comunidades asignadas. IDS Fincas las configurará en breve.</p>
    </div>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px;margin-top:24px">
      <?php foreach ($coms as $c): ?>
        <a href="<?= e($b) ?>/panel/comunidad.php?id=<?= (int)$c['id'] ?>" class="card" style="text-decoration:none;display:flex;flex-direction:column;gap:8px">
          <div style="width:44px;height:44px;border-radius:12px;background:var(--azul);display:flex;align-items:center;justify-content:center">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5M9 9h.01M15 9h.01M9 13h.01M15 13h.01" stroke="#21395A" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>
          <h3 style="font-size:20px;margin:6px 0 0"><?= e($c['nombre']) ?></h3>
          <?php if ($c['direccion']): ?><p class="muted" style="margin:0;font-size:14px"><?= e($c['direccion']) ?></p><?php endif; ?>
          <p style="margin:8px 0 0;color:var(--oro);font-weight:600;font-size:14px"><?= (int)$c['n_docs'] ?> documento<?= (int)$c['n_docs'] === 1 ? '' : 's' ?> →</p>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php page_bottom();
