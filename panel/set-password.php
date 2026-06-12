<?php
/**
 * IDS Fincas — Crear / restablecer contraseña vía token
 * El usuario llega aquí desde el enlace que le entrega IDS.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';

$uid   = (int)($_GET['uid'] ?? $_POST['uid'] ?? 0);
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');

$u = $uid && $token ? verify_set_password_token($uid, $token) : null;

$error = '';
$done  = false;

if ($u && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $p1 = (string)($_POST['password'] ?? '');
    $p2 = (string)($_POST['password2'] ?? '');

    if (strlen($p1) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($p1 !== $p2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        commit_password((int)$u['id'], $p1);
        $done = true;
    }
}

page_top('Crear contraseña', ['header' => false]);
?>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:32px 20px;background:linear-gradient(160deg,#FBF7EF 0%,#E8EEF5 100%)">
  <div style="width:100%;max-width:440px">
    <div style="text-align:center;margin-bottom:28px"><?= ids_logo(false) ?></div>
    <div class="card" style="padding:32px">
      <?php if (!$u): ?>
        <h1 style="font-size:24px;text-align:center">Enlace no válido</h1>
        <p class="muted" style="text-align:center">El enlace ha caducado o ya se ha usado. Solicite uno nuevo a IDS Fincas.</p>
        <p style="text-align:center;margin-top:18px"><a class="btn btn-line" href="<?= e(base_url()) ?>/panel/login.php">Ir al acceso</a></p>
      <?php elseif ($done): ?>
        <h1 style="font-size:24px;text-align:center">Contraseña creada ✓</h1>
        <p class="muted" style="text-align:center">Ya puede acceder al portal con su email y su nueva contraseña.</p>
        <p style="text-align:center;margin-top:18px"><a class="btn btn-primary" href="<?= e(base_url()) ?>/panel/login.php">Entrar</a></p>
      <?php else: ?>
        <h1 style="font-size:24px;text-align:center">Cree su contraseña</h1>
        <p class="muted" style="text-align:center;margin-bottom:22px">Hola <?= e($u['nombre']) ?>, defina la contraseña de acceso a su portal.</p>
        <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="uid" value="<?= (int)$uid ?>">
          <input type="hidden" name="token" value="<?= e($token) ?>">
          <div class="field">
            <label for="password">Nueva contraseña</label>
            <input id="password" name="password" type="password" minlength="8" required autofocus>
          </div>
          <div class="field">
            <label for="password2">Repita la contraseña</label>
            <input id="password2" name="password2" type="password" minlength="8" required>
          </div>
          <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">Guardar y acceder</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php page_bottom();
