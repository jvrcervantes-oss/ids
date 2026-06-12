<?php
/**
 * IDS Fincas — Autenticación y control de acceso
 * Carga única: require este archivo y tienes BD + helpers + CSRF + sesión segura.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';

/** Arranca la sesión con cookies endurecidas. Idempotente. */
function secure_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,        // en local (http) se relaja; en Hostinger (https) se exige
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_name('IDSSESSID');
    session_start();
}

// Arranca la sesión en cuanto se incluye este módulo.
secure_session_start();

/** Usuario autenticado (array) o null. */
function current_user(): ?array
{
    if (empty($_SESSION['uid'])) return null;
    static $cache = null;
    if ($cache !== null) return $cache;
    $st = db()->prepare('SELECT id, nombre, email, rol, activo FROM users WHERE id = ? LIMIT 1');
    $st->execute([$_SESSION['uid']]);
    $u = $st->fetch();
    if (!$u || (int)$u['activo'] !== 1) {
        session_destroy();
        return null;
    }
    return $cache = $u;
}

function is_logged_in(): bool { return current_user() !== null; }
function is_admin(): bool { $u = current_user(); return $u && $u['rol'] === 'admin'; }

/** Exige sesión; si no, va al login del panel. */
function require_login(): array
{
    $u = current_user();
    if (!$u) redirect(base_url() . '/panel/login.php');
    return $u;
}

/** Exige rol admin. */
function require_admin(): array
{
    $u = require_login();
    if ($u['rol'] !== 'admin') {
        http_response_code(403);
        exit('Acceso restringido.');
    }
    return $u;
}

// ---------------------------------------------------------------------
//  Rate limiting de login
// ---------------------------------------------------------------------
function record_login_attempt(?string $email, bool $exito): void
{
    $st = db()->prepare('INSERT INTO login_attempts (email, ip, exito) VALUES (?, ?, ?)');
    $st->execute([$email, client_ip(), $exito ? 1 : 0]);
}

function too_many_attempts(?string $email): bool
{
    $max    = (int) config('max_login_attempts');
    $window = (int) config('login_window_min');
    $st = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE exito = 0 AND created_at > (NOW() - INTERVAL ? MINUTE)
           AND (ip = ? OR email = ?)'
    );
    $st->execute([$window, client_ip(), $email]);
    return ((int) $st->fetchColumn()) >= $max;
}

/**
 * Intenta autenticar. Devuelve el usuario o null.
 * Aplica rate limiting y registra el intento.
 */
function attempt_login(string $email, string $password): ?array
{
    $email = trim(strtolower($email));

    if (too_many_attempts($email)) {
        return null; // bloqueado; el caller muestra mensaje genérico
    }

    $st = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $u = $st->fetch();

    $ok = $u
        && (int)$u['activo'] === 1
        && !empty($u['password_hash'])
        && password_verify($password, $u['password_hash']);

    record_login_attempt($email, $ok);

    if (!$ok) {
        audit(is_array($u) ? (int)$u['id'] : null, 'login_fail', $email);
        return null;
    }

    // Rehash si el coste de bcrypt cambió.
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        $new = password_hash($password, PASSWORD_DEFAULT);
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$new, $u['id']]);
    }

    // Sesión nueva tras autenticar (anti fixation).
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$u['id']]);
    audit((int)$u['id'], 'login', $email);

    return $u;
}

function logout(): void
{
    $uid = $_SESSION['uid'] ?? null;
    if ($uid) audit((int)$uid, 'logout');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ---------------------------------------------------------------------
//  Tokens de "crear contraseña" (alta sin email conocido / reset)
// ---------------------------------------------------------------------

/** Hash almacenable de un token crudo (no guardamos el token en claro). */
function hash_token(string $raw): string
{
    return hash_hmac('sha256', $raw, (string) config('app_secret'));
}

/**
 * Genera un token de alta para un usuario y lo persiste (hasheado).
 * Devuelve la URL completa que el admin entrega al usuario.
 */
function issue_set_password_link(int $userId): string
{
    $raw = bin2hex(random_bytes(32));
    $ttl = (int) config('token_ttl_hours');
    $st = db()->prepare(
        'UPDATE users SET set_password_token = ?, token_expira = (NOW() + INTERVAL ? HOUR) WHERE id = ?'
    );
    $st->execute([hash_token($raw), $ttl, $userId]);
    return base_url() . '/panel/set-password.php?uid=' . $userId . '&token=' . $raw;
}

/** Valida (uid, token) y devuelve el usuario si el token es vigente. */
function verify_set_password_token(int $userId, string $raw): ?array
{
    $st = db()->prepare(
        'SELECT * FROM users WHERE id = ? AND set_password_token IS NOT NULL
           AND token_expira > NOW() LIMIT 1'
    );
    $st->execute([$userId]);
    $u = $st->fetch();
    if (!$u) return null;
    return hash_equals($u['set_password_token'], hash_token($raw)) ? $u : null;
}

/** Fija la contraseña definitiva e invalida el token. */
function commit_password(int $userId, string $password): void
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = db()->prepare(
        'UPDATE users SET password_hash = ?, set_password_token = NULL, token_expira = NULL, activo = 1 WHERE id = ?'
    );
    $st->execute([$hash, $userId]);
    audit($userId, 'password_set');
}
