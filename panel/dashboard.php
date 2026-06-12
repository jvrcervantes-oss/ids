<?php
/**
 * IDS Fincas — Panel de usuario (stub Fase 2; contenido real en Fase 4)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';

$user = require_login();

$nav = '<a class="act" href="' . e(base_url()) . '/panel/logout.php">Salir</a>';
page_top('Mi portal', ['nav' => $nav]);
?>
<div class="wrap" style="padding:40px 24px">
  <h1 style="font-size:30px">Hola, <?= e($user['nombre']) ?></h1>
  <p class="muted">Bienvenido al portal de su comunidad.</p>
  <div class="card" style="margin-top:24px">
    <p class="muted" style="margin:0">Aquí verá sus comunidades y la documentación disponible. (En construcción — Fase 4.)</p>
  </div>
</div>
<?php page_bottom();
