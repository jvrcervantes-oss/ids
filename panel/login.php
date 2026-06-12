<?php
/**
 * IDS Fincas — Acceso de clientes (login)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ui.php';

// Ya logueado: a su sitio.
if ($u = current_user()) {
    redirect(base_url() . ($u['rol'] === 'admin' ? '/admin/' : '/panel/dashboard.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = (string)($_POST['email'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');

    if (too_many_attempts(trim(strtolower($email)))) {
        $error = 'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.';
    } else {
        $u = attempt_login($email, $pass);
        if ($u) {
            redirect(base_url() . ($u['rol'] === 'admin' ? '/admin/' : '/panel/dashboard.php'));
        }
        $error = 'Email o contraseña incorrectos.';
    }
}

page_top('Acceso clientes', ['header' => false]);
?>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:32px 20px;background:linear-gradient(160deg,#FBF7EF 0%,#E8EEF5 100%)">
  <div style="width:100%;max-width:420px">
    <div style="text-align:center;margin-bottom:28px"><a href="<?= e(base_url()) ?>/" style="text-decoration:none"><?= ids_logo(false) ?></a></div>
    <div class="card" style="padding:32px">
      <h1 style="font-size:26px;text-align:center">Acceso clientes</h1>
      <p class="muted" style="text-align:center;margin-top:-4px;margin-bottom:24px">Portal de su comunidad</p>
      <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
      <form method="post" autocomplete="on">
        <?= csrf_field() ?>
        <div class="field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="password">Contraseña</label>
          <input id="password" name="password" type="password" required>
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">Entrar</button>
      </form>
      <p class="muted" style="text-align:center;margin-top:20px;font-size:14px">
        ¿Sin acceso? Lo gestiona IDS Fincas — contacte con su administrador.
      </p>
    </div>
    <p style="text-align:center;margin-top:18px"><a href="<?= e(base_url()) ?>/" class="muted" style="text-decoration:none">← Volver a la web</a></p>
  </div>
</div>
<?php page_bottom();
