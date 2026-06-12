<?php
/**
 * IDS Fincas — Shell de navegación del panel admin.
 */
declare(strict_types=1);

require_once __DIR__ . '/ui.php';

/** Devuelve el HTML de navegación admin con el item activo resaltado. */
function admin_nav(string $active): string
{
    $b = base_url();
    $items = [
        'inicio'      => ['Inicio',      $b . '/admin/'],
        'comunidades' => ['Comunidades', $b . '/admin/comunidades.php'],
        'usuarios'    => ['Usuarios',    $b . '/admin/usuarios.php'],
        'categorias'  => ['Categorías',  $b . '/admin/categorias.php'],
        'documentos'  => ['Documentos',  $b . '/admin/documentos.php'],
        'leads'       => ['Solicitudes', $b . '/admin/leads.php'],
    ];
    $out = '';
    foreach ($items as $key => [$label, $href]) {
        $style = $key === $active
            ? 'color:#fff;text-decoration:underline;text-underline-offset:5px;text-decoration-color:var(--oro,#D08A2E);text-decoration-thickness:2px'
            : '';
        $out .= '<a class="act" style="' . $style . '" href="' . e($href) . '">' . e($label) . '</a>';
    }
    $out .= '<a class="act" href="' . e($b) . '/panel/logout.php" style="opacity:.8">Salir</a>';
    return $out;
}

/** Abre la página admin: guard + cabecera + contenedor + flash. */
function admin_page_open(string $title, string $active): void
{
    page_top($title, ['nav' => admin_nav($active)]);
    echo '<div class="wrap" style="padding:36px 24px">';
    render_flash();
}

function admin_page_close(): void
{
    echo '</div>';
    page_bottom();
}
