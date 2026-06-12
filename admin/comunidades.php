<?php
/**
 * IDS Fincas — Admin · Comunidades (CRUD)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_ui.php';

$admin = require_admin();
$pdo   = db();
$self  = base_url() . '/admin/comunidades.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $id     = (int)($_POST['id'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $dir    = trim((string)($_POST['direccion'] ?? ''));
        $desc   = trim((string)($_POST['descripcion'] ?? ''));

        if ($nombre === '') {
            flash_set('error', 'El nombre de la comunidad es obligatorio.');
        } elseif ($id > 0) {
            $pdo->prepare('UPDATE communities SET nombre=?, direccion=?, descripcion=? WHERE id=?')
                ->execute([$nombre, $dir, $desc, $id]);
            audit((int)$admin['id'], 'community_update', "#$id $nombre");
            flash_set('ok', 'Comunidad actualizada.');
        } else {
            $pdo->prepare('INSERT INTO communities (nombre, direccion, descripcion) VALUES (?,?,?)')
                ->execute([$nombre, $dir, $desc]);
            audit((int)$admin['id'], 'community_create', $nombre);
            flash_set('ok', 'Comunidad creada.');
        }
    } elseif ($accion === 'borrar') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM communities WHERE id=?')->execute([$id]);
        audit((int)$admin['id'], 'community_delete', "#$id");
        flash_set('ok', 'Comunidad eliminada (y sus documentos asociados).');
    }
    redirect($self);
}

// Datos
$editId = (int)($_GET['edit'] ?? 0);
$edit = null;
if ($editId) {
    $st = $pdo->prepare('SELECT * FROM communities WHERE id=?');
    $st->execute([$editId]);
    $edit = $st->fetch() ?: null;
}

$rows = $pdo->query(
    'SELECT c.*,
       (SELECT COUNT(*) FROM user_communities uc WHERE uc.community_id=c.id) AS n_users,
       (SELECT COUNT(*) FROM documents d WHERE d.community_id=c.id) AS n_docs
     FROM communities c ORDER BY c.nombre'
)->fetchAll();

admin_page_open('Comunidades', 'comunidades');
?>
<h1 style="font-size:28px">Comunidades</h1>

<div style="display:grid;grid-template-columns:1fr;gap:24px;margin-top:16px">
  <div class="card">
    <h3 style="font-size:19px"><?= $edit ? 'Editar comunidad' : 'Nueva comunidad' ?></h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="guardar">
      <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
      <div class="field">
        <label for="nombre">Nombre *</label>
        <input id="nombre" name="nombre" required value="<?= e($edit['nombre'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="direccion">Dirección</label>
        <input id="direccion" name="direccion" value="<?= e($edit['direccion'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="2"><?= e($edit['descripcion'] ?? '') ?></textarea>
      </div>
      <div style="display:flex;gap:10px">
        <button class="btn btn-primary" type="submit"><?= $edit ? 'Guardar cambios' : 'Crear comunidad' ?></button>
        <?php if ($edit): ?><a class="btn btn-line" href="<?= e($self) ?>">Cancelar</a><?php endif; ?>
      </div>
    </form>
  </div>

  <div>
    <table>
      <thead><tr><th>Comunidad</th><th>Dirección</th><th>Vecinos</th><th>Docs</th><th></th></tr></thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="5" class="muted">Aún no hay comunidades.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><strong><?= e($r['nombre']) ?></strong></td>
          <td class="muted"><?= e($r['direccion'] ?: '—') ?></td>
          <td><?= (int)$r['n_users'] ?></td>
          <td><?= (int)$r['n_docs'] ?></td>
          <td style="white-space:nowrap;text-align:right">
            <a class="btn btn-line" style="min-height:36px;padding:7px 14px" href="<?= e($self) ?>?edit=<?= (int)$r['id'] ?>">Editar</a>
            <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar esta comunidad? Se borrarán sus documentos y asignaciones.')">
              <?= csrf_field() ?>
              <input type="hidden" name="accion" value="borrar">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-line" style="min-height:36px;padding:7px 14px;color:#8A2B2B;border-color:#E6BBBB" type="submit">Borrar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php admin_page_close();
