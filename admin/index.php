<?php
/**
 * IDS Fincas — Panel admin · Inicio
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_ui.php';

$admin = require_admin();

$pdo = db();
$stats = [
    'Comunidades' => (int)$pdo->query('SELECT COUNT(*) FROM communities')->fetchColumn(),
    'Usuarios'    => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE rol='user'")->fetchColumn(),
    'Documentos'  => (int)$pdo->query('SELECT COUNT(*) FROM documents')->fetchColumn(),
    'Categorías'  => (int)$pdo->query('SELECT COUNT(*) FROM doc_categories')->fetchColumn(),
];
$b = base_url();
$links = [
    'Comunidades' => $b . '/admin/comunidades.php',
    'Usuarios'    => $b . '/admin/usuarios.php',
    'Documentos'  => $b . '/admin/documentos.php',
    'Categorías'  => $b . '/admin/categorias.php',
];

admin_page_open('Administración', 'inicio');
?>
<h1 style="font-size:30px">Panel de administración</h1>
<p class="muted">Conectado como <?= e($admin['nombre']) ?> · <?= e($admin['email']) ?></p>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px;margin-top:24px">
  <?php foreach ($stats as $label => $n): ?>
    <a href="<?= e($links[$label]) ?>" class="card" style="text-decoration:none;display:block">
      <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:40px;font-weight:600;color:var(--tinta)"><?= $n ?></div>
      <div class="muted" style="margin-top:4px"><?= e($label) ?></div>
    </a>
  <?php endforeach; ?>
</div>

<div class="card" style="margin-top:24px">
  <h3 style="font-size:19px;margin-bottom:6px">Flujo de alta de un vecino</h3>
  <ol class="muted" style="margin:0;padding-left:20px;line-height:1.9">
    <li>Crea la <strong>comunidad</strong> si no existe.</li>
    <li>Crea el <strong>usuario</strong> y asígnalo a su(s) comunidad(es).</li>
    <li>Copia el <strong>enlace de contraseña</strong> que aparece y entrégaselo al vecino.</li>
    <li>Sube los <strong>documentos</strong> de la comunidad por categoría.</li>
  </ol>
</div>
<?php admin_page_close();
