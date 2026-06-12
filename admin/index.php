<?php
/**
 * IDS Fincas — Panel admin (stub Fase 2; secciones reales en Fase 3)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';

$admin = require_admin();

$nav = '<a class="act" href="' . e(base_url()) . '/admin/">Admin</a>'
     . '<a class="act" href="' . e(base_url()) . '/panel/logout.php">Salir</a>';
page_top('Administración', ['nav' => $nav]);
?>
<div class="wrap" style="padding:40px 24px">
  <h1 style="font-size:30px">Panel de administración</h1>
  <p class="muted">Conectado como <?= e($admin['nombre']) ?> (<?= e($admin['email']) ?>)</p>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-top:24px">
    <div class="card"><h3 style="font-size:19px">Comunidades</h3><p class="muted">Crear y editar comunidades. (Fase 3)</p></div>
    <div class="card"><h3 style="font-size:19px">Usuarios</h3><p class="muted">Alta de vecinos y asignación. (Fase 3)</p></div>
    <div class="card"><h3 style="font-size:19px">Categorías</h3><p class="muted">Tipos de documento editables. (Fase 3)</p></div>
    <div class="card"><h3 style="font-size:19px">Documentos</h3><p class="muted">Subir y gestionar archivos. (Fase 3)</p></div>
  </div>
</div>
<?php page_bottom();
