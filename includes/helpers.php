<?php
/**
 * IDS Fincas — Utilidades comunes
 */
declare(strict_types=1);

/** Escapa para salida HTML (anti-XSS). */
function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/** Redirección y corte de ejecución. */
function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

/** IP del cliente (respeta proxy de Hostinger con cautela). */
function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** URL base del sitio. Autodetecta si app_url está vacío. */
function base_url(): string
{
    $configured = (string) config('app_url');
    if ($configured !== '') return rtrim($configured, '/');
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Directorio raíz del proyecto respecto al script en ejecución.
    return $scheme . '://' . $host;
}

/** Registra una acción en audit_log (nunca interrumpe el flujo). */
function audit(?int $userId, string $accion, ?string $target = null): void
{
    try {
        $st = db()->prepare(
            'INSERT INTO audit_log (user_id, accion, target, ip) VALUES (?, ?, ?, ?)'
        );
        $st->execute([$userId, $accion, $target, client_ip()]);
    } catch (Throwable $e) {
        error_log('IDS audit error: ' . $e->getMessage());
    }
}

/** Formatea bytes a tamaño legible. */
function format_bytes(int $bytes): string
{
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return number_format($bytes / 1024, 0) . ' KB';
    return $bytes . ' B';
}

/** Mensaje flash (un solo uso) en sesión. */
function flash_set(string $type, string $msg): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}

/** Devuelve y limpia los mensajes flash. */
function flash_take(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}
