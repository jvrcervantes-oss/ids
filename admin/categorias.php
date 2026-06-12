<?php
/**
 * IDS Fincas — Admin · Categorías de documentos (CRUD)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_ui.php';

$admin = require_admin();
$pdo   = db();
$self  = base_url() . '/admin/categorias.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $id     = (int)($_POST['id'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $orden  = (int)($_POST['orden'] ?? 0);
        if ($nombre === '') {
            flash_set('error', 'El nombre es obligatorio.');
        } else {
            try {
                if ($id > 0) {
                    $pdo->prepare('UPDATE doc_categories SET nombre=?, orden=? WHERE id=?')->execute([$nombre, $orden, $id]);
                    flash_set('ok', 'Categoría actualizada.');
                } else {
                    $pdo->prepare('INSERT INTO doc_categories (nombre, orden) VALUES (?,?)')->execute([$nombre, $orden]);
                    flash_set('ok', 'Categoría creada.');
                }
            } catch (PDOException $e) {
                flash_set('error', 'Ya existe una categoría con ese nombre.');
            }
        }
    } elseif ($accion === 'borrar') {
        $id = (int)($_POST['id'] ?? 0);
        // Los documentos de esa categoría quedan con category_id = NULL (FK ON DELETE SET NULL).
        $pdo->prepare('DELETE FROM doc_categories WHERE id=?')->execute([$id]);
        flash_set('ok', 'Categoría eliminada. Sus documentos quedan como "Sin categoría".');
    }
    redirect($self);
}

$editId = (int)($_GET['edit'] ?? 0);
$edit = null;
if ($editId) {
    $st = $pdo->prepare('SELECT * FROM doc_categories WHERE id=?');
    $st->execute([$editId]);
    $edit = $st->fetch() ?: null;
}

$rows = $pdo->query(
    'SELECT dc.*, (SELECT COUNT(*) FROM documents d WHERE d.category_id=dc.id) AS n_docs
     FROM doc_categories dc ORDER BY dc.orden, dc.nombre'
)->fetchAll();

admin_page_open('Categorías', 'categorias');
?>
<h1 style="font-size:28px">Categorías de documentos</h1>
<p class="muted">Tipos con los que se clasifican los documentos de cada comunidad.</p>

<div style="display:grid;grid-template-columns:1fr;gap:24px;margin-top:16px;max-width:760px">
  <div class="card">
    <h3 style="font-size:19px"><?= $edit ? 'Editar categoría' : 'Nueva categoría' ?></h3>
    <form method="post" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="guardar">
      <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
      <div class="field" style="flex:1;min-width:220px;margin:0">
        <label for="nombre">Nombre *</label>
        <input id="nombre" name="nombre" required value="<?= e($edit['nombre'] ?? '') ?>">
      </div>
      <div class="field" style="width:110px;margin:0">
        <label for="orden">Orden</label>
        <input id="orden" name="orden" type="number" value="<?= (int)($edit['orden'] ?? 0) ?>">
      </div>
      <button class="btn btn-primary" type="submit"><?= $edit ? 'Guardar' : 'Añadir' ?></button>
      <?php if ($edit): ?><a class="btn btn-line" href="<?= e($self) ?>">Cancelar</a><?php endif; ?>
    </form>
  </div>

  <table>
    <thead><tr><th>Orden</th><th>Categoría</th><th>Docs</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['orden'] ?></td>
        <td><strong><?= e($r['nombre']) ?></strong></td>
        <td><?= (int)$r['n_docs'] ?></td>
        <td style="text-align:right;white-space:nowrap">
          <a class="btn btn-line" style="min-height:36px;padding:7px 14px" href="<?= e($self) ?>?edit=<?= (int)$r['id'] ?>">Editar</a>
          <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar categoría?')">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="borrar">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn btn-line" style="min-height:36px;padding:7px 14px;color:#8A2B2B;border-color:#E6BBBB" type="submit">Borrar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php admin_page_close();
