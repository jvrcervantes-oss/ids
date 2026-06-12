<?php
/**
 * IDS Fincas — Protección CSRF (token por sesión)
 */
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** Campo oculto listo para incrustar en cualquier <form>. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Valida el token enviado por POST. Corta con 419 si no coincide. */
function csrf_check(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Sesión expirada o petición no válida. Vuelve atrás y reintenta.');
    }
}
