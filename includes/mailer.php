<?php
/**
 * IDS Fincas — Envío de correo
 * Mientras no haya dominio/SMTP (dev_mode), no se envía: el admin entrega
 * el enlace de "crear contraseña" a mano. Cuando se active 'mail.enabled',
 * este módulo intentará enviar por SMTP (PHPMailer) o por mail() nativo.
 */
declare(strict_types=1);

/**
 * Envía (o simula) el correo con el enlace de alta.
 * Devuelve true si se envió, false si quedó pendiente de entrega manual.
 */
function send_set_password_email(string $toEmail, string $toName, string $link): bool
{
    $mail = config('mail');
    if (empty($mail['enabled'])) {
        // Modo sin email: dejamos rastro en log y delegamos en el admin.
        error_log("IDS set-password link para {$toEmail}: {$link}");
        return false;
    }

    $subject = 'Acceso al portal de IDS Fincas';
    $body    = "Hola {$toName},\n\n"
             . "Se ha creado tu acceso al portal de clientes de IDS Fincas.\n"
             . "Crea tu contraseña aquí (enlace válido unas horas):\n\n{$link}\n\n"
             . "Si no esperabas este correo, ignóralo.\n\nIDS Fincas";

    // PHPMailer si está disponible (vendor/), si no, mail() nativo.
    $phpmailer = __DIR__ . '/../vendor/autoload.php';
    if (is_file($phpmailer)) {
        require_once $phpmailer;
        try {
            $m = new \PHPMailer\PHPMailer\PHPMailer(true);
            $m->isSMTP();
            $m->Host       = $mail['host'];
            $m->SMTPAuth   = true;
            $m->Username   = $mail['user'];
            $m->Password   = $mail['pass'];
            $m->SMTPSecure = $mail['secure'];
            $m->Port       = (int) $mail['port'];
            $m->CharSet    = 'UTF-8';
            $m->setFrom($mail['from_mail'], $mail['from_name']);
            $m->addAddress($toEmail, $toName);
            $m->Subject = $subject;
            $m->Body    = $body;
            $m->send();
            return true;
        } catch (Throwable $e) {
            error_log('IDS mail SMTP error: ' . $e->getMessage());
            return false;
        }
    }

    // Fallback básico.
    $headers = 'From: ' . $mail['from_name'] . ' <' . $mail['from_mail'] . ">\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";
    return @mail($toEmail, $subject, $body, $headers);
}
