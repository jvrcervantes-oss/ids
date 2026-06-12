<?php
/**
 * IDS Fincas — Shell de interfaz para panel/admin (marca consistente).
 */
declare(strict_types=1);

/** Lockup tipográfico del logo (sin imagen). $dark=true para fondo oscuro. */
function ids_logo(bool $dark = false): string
{
    $col = $dark ? '#FBF7EF' : '#21395A';
    $sub = $dark ? '#AFC0D4' : '#6F695B';
    return '<span style="display:inline-flex;align-items:center;gap:12px;text-decoration:none;">'
        . '<span style="width:38px;height:42px;border:2.5px solid ' . $col . ';border-bottom:none;border-radius:999px 999px 0 0;display:flex;align-items:flex-end;justify-content:center;padding-bottom:3px;">'
        . '<span style="font-family:\'Bricolage Grotesque\',sans-serif;font-weight:600;font-size:12px;letter-spacing:.5px;color:' . $col . '">IDS</span></span>'
        . '<span style="line-height:1.15;">'
        . '<span style="display:block;font-family:\'Bricolage Grotesque\',sans-serif;font-weight:600;font-size:19px;color:' . $col . '">IDS Fincas</span>'
        . '<span style="display:block;font-size:12px;letter-spacing:1.6px;color:' . $sub . '">MURCIA</span>'
        . '</span></span>';
}

/**
 * Cabecera de página. $opts:
 *   'nav'  => HTML de navegación a la derecha del header (o '')
 *   'header' => false para páginas centradas (login) sin barra superior
 */
function page_top(string $title, array $opts = []): void
{
    $showHeader = $opts['header'] ?? true;
    $nav = $opts['nav'] ?? '';
    ?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($title) ?> · IDS Fincas</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600&family=Public+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --tinta:#21395A; --lino:#FBF7EF; --oro:#D08A2E; --salvia:#5E7150;
    --azul:#E8EEF5; --texto:#4A5568; --linea:#E2DDD2;
  }
  *{box-sizing:border-box}
  body{margin:0;font-family:'Public Sans',sans-serif;color:var(--tinta);background:var(--lino);line-height:1.6}
  a{color:var(--tinta)}
  h1,h2,h3{font-family:'Bricolage Grotesque',sans-serif;font-weight:600;letter-spacing:-.3px;color:var(--tinta);margin:0 0 .4em}
  .wrap{max-width:1080px;margin:0 auto;padding:0 24px}
  .topbar{background:var(--tinta);position:sticky;top:0;z-index:30}
  .topbar .wrap{display:flex;align-items:center;justify-content:space-between;gap:24px;padding-top:14px;padding-bottom:14px}
  .topbar a.act{color:var(--lino);text-decoration:none;font-weight:500;font-size:15px}
  .topbar a.act:hover{text-decoration:underline;text-underline-offset:4px}
  .btn{display:inline-flex;align-items:center;gap:8px;min-height:46px;padding:11px 22px;border-radius:999px;
       font-family:'Public Sans',sans-serif;font-size:15px;font-weight:600;cursor:pointer;border:1.5px solid transparent;text-decoration:none;transition:.15s}
  .btn-primary{background:var(--tinta);color:var(--lino)}
  .btn-primary:hover{background:#16284099;background:#182e4b}
  .btn-gold{background:var(--oro);color:#fff}
  .btn-gold:hover{filter:brightness(.95)}
  .btn-ghost{background:transparent;color:var(--lino);border-color:var(--lino)}
  .btn-ghost:hover{background:rgba(251,247,239,.12)}
  .btn-line{background:#fff;color:var(--tinta);border-color:var(--linea)}
  .btn-line:hover{border-color:var(--tinta)}
  .card{background:#fff;border:1px solid var(--linea);border-radius:16px;padding:24px}
  label{display:block;font-size:14px;font-weight:600;margin:0 0 6px;color:var(--tinta)}
  input,select,textarea{width:100%;padding:12px 14px;border:1.5px solid var(--linea);border-radius:10px;
       font-family:'Public Sans',sans-serif;font-size:16px;color:var(--tinta);background:#fff}
  input:focus,select:focus,textarea:focus{outline:none;border-color:var(--tinta)}
  .field{margin-bottom:16px}
  .muted{color:var(--texto);font-size:15px}
  .flash{padding:12px 16px;border-radius:10px;margin-bottom:18px;font-size:15px;border:1px solid}
  .flash.ok{background:#EAF3EC;border-color:#BFD8C4;color:#2F5135}
  .flash.error{background:#FBEAEA;border-color:#E6BBBB;color:#8A2B2B}
  .flash.info{background:var(--azul);border-color:#C6D6E6;color:var(--tinta)}
  table{width:100%;border-collapse:collapse;background:#fff;border:1px solid var(--linea);border-radius:12px;overflow:hidden}
  th,td{text-align:left;padding:12px 14px;border-bottom:1px solid var(--linea);font-size:15px}
  th{background:var(--azul);font-family:'Bricolage Grotesque',sans-serif;font-weight:600}
  tr:last-child td{border-bottom:none}
</style>
</head>
<body>
<?php if ($showHeader): ?>
  <div class="topbar"><div class="wrap">
    <a href="<?= e(base_url()) ?>/" class="act"><?= ids_logo(true) ?></a>
    <nav style="display:flex;align-items:center;gap:22px"><?= $nav ?></nav>
  </div></div>
<?php endif; ?>
<?php
}

/** Imprime los mensajes flash pendientes. */
function render_flash(): void
{
    foreach (flash_take() as $f) {
        echo '<div class="flash ' . e($f['type']) . '">' . e($f['msg']) . '</div>';
    }
}

function page_bottom(): void
{
    ?>
</body>
</html>
<?php
}
