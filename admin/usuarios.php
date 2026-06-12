<?php
/**
 * IDS Fincas — Admin · Usuarios (vecinos): alta, comunidades, enlace de acceso
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_ui.php';
require_once __DIR__ . '/../includes/mailer.php';

$admin = require_admin();
$pdo   = db();
$self  = base_url() . '/admin/usuarios.php';

/** Reescribe las comunidades asignadas a un usuario. */
function set_user_communities(PDO $pdo, int $userId, array $communityIds): void
{
    $pdo->prepare('DELETE FROM user_communities WHERE user_id=?')->execute([$userId]);
    if ($communityIds) {
        $ins = $pdo->prepare('INSERT IGNORE INTO user_communities (user_id, community_id) VALUES (?,?)');
        foreach ($communityIds as $cid) {
            $cid = (int)$cid;
            if ($cid > 0) $ins->execute([$userId, $cid]);
        }
    }
}

/** Genera enlace de acceso, intenta email y deja el enlace listo para mostrar. */
function entregar_enlace(int $userId, string $nombre, string $email): void
{
    $url  = issue_set_password_link($userId);
    $sent = send_set_password_email($email, $nombre, $url);
    if ($sent) {
        flash_set('ok', "Enlace de acceso enviado por email a $email.");
    } else {
        // dev_mode / sin SMTP: se entrega a mano.
        $_SESSION['_newlink'] = ['nombre' => $nombre, 'email' => $email, 'url' => $url];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $email  = trim(strtolower((string)($_POST['email'] ?? '')));
        $coms   = (array)($_POST['communities'] ?? []);

        if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'Nombre y email válido son obligatorios.');
            redirect($self);
        }
        $exists = $pdo->prepare('SELECT id FROM users WHERE email=?');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            flash_set('error', 'Ya existe un usuario con ese email.');
            redirect($self);
        }
        $pdo->prepare('INSERT INTO users (nombre, email, rol, activo) VALUES (?,?,?,1)')
            ->execute([$nombre, $email, 'user']);
        $uid = (int)$pdo->lastInsertId();
        set_user_communities($pdo, $uid, $coms);
        audit((int)$admin['id'], 'user_create', $email);
        flash_set('ok', "Usuario $nombre creado.");
        entregar_enlace($uid, $nombre, $email);
        redirect($self);

    } elseif ($accion === 'editar') {
        $uid    = (int)($_POST['id'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $coms   = (array)($_POST['communities'] ?? []);
        if ($uid > 0 && $nombre !== '') {
            $pdo->prepare("UPDATE users SET nombre=? WHERE id=? AND rol='user'")->execute([$nombre, $uid]);
            set_user_communities($pdo, $uid, $coms);
            audit((int)$admin['id'], 'user_update', "#$uid");
            flash_set('ok', 'Usuario actualizado.');
        }
        redirect($self);

    } elseif ($accion === 'toggle') {
        $uid = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE users SET activo = 1 - activo WHERE id=? AND rol='user'")->execute([$uid]);
        flash_set('ok', 'Estado del usuario actualizado.');
        redirect($self);

    } elseif ($accion === 'enlace') {
        $uid = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare("SELECT * FROM users WHERE id=? AND rol='user'");
        $st->execute([$uid]);
        if ($u = $st->fetch()) entregar_enlace($uid, $u['nombre'], $u['email']);
        redirect($self);

    } elseif ($accion === 'borrar') {
        $uid = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM users WHERE id=? AND rol='user'")->execute([$uid]);
        audit((int)$admin['id'], 'user_delete', "#$uid");
        flash_set('ok', 'Usuario eliminado.');
        redirect($self);
    }
}

// --- Datos para la vista ---
$communities = $pdo->query('SELECT id, nombre FROM communities ORDER BY nombre')->fetchAll();

$editId = (int)($_GET['edit'] ?? 0);
$edit = null; $editComs = [];
if ($editId) {
    $st = $pdo->prepare("SELECT * FROM users WHERE id=? AND rol='user'");
    $st->execute([$editId]);
    $edit = $st->fetch() ?: null;
    if ($edit) {
        $cs = $pdo->prepare('SELECT community_id FROM user_communities WHERE user_id=?');
        $cs->execute([$editId]);
        $editComs = array_map('intval', array_column($cs->fetchAll(), 'community_id'));
    }
}

$users = $pdo->query(
    "SELECT u.*,
       (SELECT GROUP_CONCAT(c.nombre ORDER BY c.nombre SEPARATOR ', ')
          FROM user_communities uc JOIN communities c ON c.id=uc.community_id
         WHERE uc.user_id=u.id) AS comunidades
     FROM users u WHERE u.rol='user' ORDER BY u.nombre"
)->fetchAll();

$newlink = $_SESSION['_newlink'] ?? null;
unset($_SESSION['_newlink']);

admin_page_open('Usuarios', 'usuarios');
?>
<h1 style="font-size:28px">Usuarios (vecinos)</h1>

<?php if ($newlink): ?>
  <div class="flash info" style="display:block">
    <strong>Enlace de acceso para <?= e($newlink['nombre']) ?> (<?= e($newlink['email']) ?>)</strong>
    <p class="muted" style="margin:6px 0 10px">Cópialo y entrégaselo al vecino. Caduca en unas horas y solo sirve para crear su contraseña.</p>
    <div style="display:flex;gap:8px">
      <input id="newlink" readonly value="<?= e($newlink['url']) ?>" onclick="this.select()" style="font-size:14px">
      <button class="btn btn-gold" type="button" onclick="navigator.clipboard.writeText(document.getElementById('newlink').value);this.textContent='Copiado ✓'">Copiar</button>
    </div>
  </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr;gap:24px;margin-top:8px">
  <div class="card">
    <h3 style="font-size:19px"><?= $edit ? 'Editar usuario' : 'Nuevo usuario' ?></h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="<?= $edit ? 'editar' : 'crear' ?>">
      <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="field">
          <label for="nombre">Nombre *</label>
          <input id="nombre" name="nombre" required value="<?= e($edit['nombre'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="email">Email *</label>
          <?php if ($edit): ?>
            <input id="email" value="<?= e($edit['email']) ?>" disabled>
          <?php else: ?>
            <input id="email" name="email" type="email" required value="<?= e($_POST['email'] ?? '') ?>">
          <?php endif; ?>
        </div>
      </div>
      <div class="field">
        <label>Comunidades</label>
        <?php if (!$communities): ?>
          <p class="muted">No hay comunidades. Crea una primero en la sección Comunidades.</p>
        <?php else: ?>
          <div style="display:flex;flex-wrap:wrap;gap:10px">
            <?php foreach ($communities as $c):
              $checked = in_array((int)$c['id'], $editComs, true); ?>
              <label style="display:flex;align-items:center;gap:8px;border:1.5px solid var(--linea);padding:8px 14px;border-radius:999px;font-weight:500;cursor:pointer">
                <input type="checkbox" name="communities[]" value="<?= (int)$c['id'] ?>" style="width:auto" <?= $checked ? 'checked' : '' ?>>
                <?= e($c['nombre']) ?>
              </label>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div style="display:flex;gap:10px">
        <button class="btn btn-primary" type="submit"><?= $edit ? 'Guardar cambios' : 'Crear usuario y generar acceso' ?></button>
        <?php if ($edit): ?><a class="btn btn-line" href="<?= e($self) ?>">Cancelar</a><?php endif; ?>
      </div>
    </form>
  </div>

  <table>
    <thead><tr><th>Nombre</th><th>Email</th><th>Comunidades</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    <?php if (!$users): ?>
      <tr><td colspan="5" class="muted">Aún no hay usuarios.</td></tr>
    <?php else: foreach ($users as $u):
      $tieneClave = !empty($u['password_hash']); ?>
      <tr>
        <td><strong><?= e($u['nombre']) ?></strong></td>
        <td class="muted"><?= e($u['email']) ?></td>
        <td class="muted"><?= e($u['comunidades'] ?: '—') ?></td>
        <td>
          <?php if ((int)$u['activo'] !== 1): ?>
            <span style="color:#8A2B2B">Inactivo</span>
          <?php elseif ($tieneClave): ?>
            <span style="color:#2F5135">Activo</span>
          <?php else: ?>
            <span style="color:#9A7B1E">Pendiente de contraseña</span>
          <?php endif; ?>
        </td>
        <td style="text-align:right;white-space:nowrap">
          <a class="btn btn-line" style="min-height:34px;padding:6px 12px" href="<?= e($self) ?>?edit=<?= (int)$u['id'] ?>">Editar</a>
          <form method="post" style="display:inline">
            <?= csrf_field() ?><input type="hidden" name="accion" value="enlace"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <button class="btn btn-line" style="min-height:34px;padding:6px 12px" type="submit" title="Generar enlace de acceso">Enlace</button>
          </form>
          <form method="post" style="display:inline">
            <?= csrf_field() ?><input type="hidden" name="accion" value="toggle"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <button class="btn btn-line" style="min-height:34px;padding:6px 12px" type="submit"><?= (int)$u['activo'] === 1 ? 'Desactivar' : 'Activar' ?></button>
          </form>
          <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar este usuario?')">
            <?= csrf_field() ?><input type="hidden" name="accion" value="borrar"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <button class="btn btn-line" style="min-height:34px;padding:6px 12px;color:#8A2B2B;border-color:#E6BBBB" type="submit">Borrar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php admin_page_close();
