<?php
/**
 * IDS Fincas — Admin · Solicitudes de presupuesto (leads de la landing)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_ui.php';

$admin = require_admin();
$pdo   = db();
$self  = base_url() . '/admin/leads.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $accion = $_POST['accion'] ?? '';
    if ($accion === 'toggle') {
        $pdo->prepare('UPDATE leads SET atendido = 1 - atendido WHERE id=?')->execute([$id]);
    } elseif ($accion === 'borrar') {
        $pdo->prepare('DELETE FROM leads WHERE id=?')->execute([$id]);
    }
    redirect($self);
}

$rows = $pdo->query('SELECT * FROM leads ORDER BY atendido, created_at DESC')->fetchAll();

admin_page_open('Solicitudes', 'leads');
?>
<h1 style="font-size:28px">Solicitudes de presupuesto</h1>
<p class="muted">Llegan desde el formulario de la web.</p>

<table style="margin-top:16px">
  <thead><tr><th>Fecha</th><th>Nombre</th><th>Teléfono</th><th>Mensaje</th><th>Estado</th><th></th></tr></thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="6" class="muted">Aún no hay solicitudes.</td></tr>
  <?php else: foreach ($rows as $r): ?>
    <tr style="<?= (int)$r['atendido'] === 1 ? 'opacity:.55' : '' ?>">
      <td class="muted" style="white-space:nowrap"><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
      <td><strong><?= e($r['nombre']) ?></strong></td>
      <td><a class="phone" style="font-size:16px" href="tel:<?= e(preg_replace('/\s+/', '', $r['telefono'])) ?>"><?= e($r['telefono']) ?></a></td>
      <td class="muted" style="max-width:340px"><?= e($r['mensaje'] ?: '—') ?></td>
      <td><?= (int)$r['atendido'] === 1 ? 'Atendido' : '<strong style="color:var(--oro,#A9701A)">Nuevo</strong>' ?></td>
      <td style="text-align:right;white-space:nowrap">
        <form method="post" style="display:inline">
          <?= csrf_field() ?><input type="hidden" name="accion" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="btn btn-line" style="min-height:34px;padding:6px 12px" type="submit"><?= (int)$r['atendido'] === 1 ? 'Marcar nuevo' : 'Marcar atendido' ?></button>
        </form>
        <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar solicitud?')">
          <?= csrf_field() ?><input type="hidden" name="accion" value="borrar"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="btn btn-line" style="min-height:34px;padding:6px 12px;color:#8A2B2B;border-color:#E6BBBB" type="submit">Borrar</button>
        </form>
      </td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
<?php admin_page_close();
