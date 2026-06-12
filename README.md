# IDS Fincas — Portal de clientes

Administración de Fincas (Murcia). Landing pública + panel de clientes (vecinos) + panel de administración. Backend PHP 8 + MySQL.

## Estructura

```
index.php            landing (Fase 5 — pendiente)
panel/               área de clientes (login, dashboard, comunidad, descarga)
admin/               área de administración (CRUD + subida de documentos)
includes/            núcleo: db, auth, csrf, helpers, mailer, ui
private/             ZONA PRIVADA (no accesible por web)
  config.php         credenciales y ajustes (NO va a git)
  config.example.php plantilla versionable
  uploads/           documentos reales (UUID; servidos solo vía download.php)
database/            schema.sql + seed.php
```

## Seguridad (resumen)

- Sin auto-registro: los usuarios los crea el admin; la contraseña la fija el propio usuario vía enlace con token caducable.
- Contraseñas con `password_hash` (bcrypt). PDO con consultas preparadas. Salida escapada (anti-XSS). Tokens CSRF en todos los formularios.
- Sesiones con cookie `HttpOnly`+`Secure`+`SameSite=Strict`, regeneración de ID al entrar.
- Rate limiting en login (5 intentos / 15 min).
- **Descargas IDOR-safe**: `download.php` comprueba que el usuario pertenece a la comunidad del documento antes de servirlo. Los archivos viven fuera de la web (`/private/uploads`) con `.htaccess` que deniega acceso y ejecución.
- HTTPS forzado y cabeceras de seguridad vía `.htaccess`.

## Despliegue en Hostinger

1. **Base de datos**: hPanel → MySQL → crear BD y usuario. Importar `database/schema.sql` en phpMyAdmin.
2. **Config**: copiar `private/config.example.php` → `private/config.php` y rellenar credenciales de la BD. Generar `app_secret` con `bin2hex(random_bytes(32))`.
3. **Subir el código** al `public_html` del dominio/subdominio (Git deploy + webhook, igual que el resto de proyectos).
4. **Carpeta de subidas**: asegurarse de que `private/uploads/` existe y tiene permisos de escritura (755/775).
5. **SSL**: activar el certificado gratuito y dejar el forzado de HTTPS del `.htaccess`.
6. **Semilla** (opcional, datos de prueba): visitar `/database/seed.php` una vez y **borrar el archivo después**.
7. **Email** (cuando haya dominio): poner `mail.enabled => true` en config y `dev_mode => false`. Mientras tanto, el alta de usuarios muestra el enlace de contraseña en pantalla para entregarlo a mano.

## Credenciales de prueba (tras ejecutar seed.php)

| Rol | Email | Contraseña |
|-----|-------|-----------|
| Admin | `admin@idsfincas.es` | `IDSadmin2026!` |
| Vecino | `vecino@idsfincas.es` | `vecino2026` |

> Cambiar/eliminar estas cuentas antes de pasar a producción real.

## Estado

- ✅ Fase 1 — Esquema MySQL + capa BD
- ✅ Fase 2 — Auth (login, CSRF, sesiones, rate-limit, crear-contraseña)
- ✅ Fase 3 — Panel admin (comunidades, usuarios, categorías, documentos, solicitudes)
- ✅ Fase 4 — Panel usuario (dashboard, comunidad, descarga protegida IDOR-safe)
- ✅ Fase 5 — Landing de producción (`index.php`) + SEO + formulario de leads

## Pendiente de datos reales del cliente

Los datos de la landing vienen del mockup de diseño y **hay que confirmarlos**:
nombre de la administradora (Isabel Domínguez), nº de colegiada, teléfono
(968 274 351), WhatsApp, email, dirección (Calle Trapería 12, Murcia).
También: dominio, cuenta SMTP para activar emails, e imagen `assets/img/og.jpg`.
