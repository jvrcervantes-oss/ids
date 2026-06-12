<?php
/**
 * IDS Fincas — Admin · Documentos (subida, listado, borrado)
 * Los archivos se guardan en /private/uploads con nombre aleatorio.
 * Nunca se sirven por URL: solo vía panel/download.php con control de acceso.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_ui.php';

$admin = require_admin();
$pdo   = db();
$self  = base_url() . '/admin/documentos.php';

/** Extensiones permitidas (gate duro). Los archivos nunca se ejecutan. */
function allowed_doc_ext(): array
{
    return ['pdf','jpg','jpeg','png','gif','webp','doc','docx','xls','xlsx',
            'ppt','pptx','odt','ods','txt','csv','zip'];
}

/** MIME real (por extensión) que aceptamos. El contenido debe coincidir con la extensión. */
function allowed_doc_mimes(string $ext): array
{
    $map = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'], 'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'], 'gif' => ['image/gif'], 'webp' => ['image/webp'],
        'txt'  => ['text/plain'], 'csv' => ['text/plain','text/csv','application/csv'],
        'zip'  => ['application/zip','application/x-zip-compressed',
                   // Office moderno (docx/xlsx/pptx/odt/ods) son ZIP por dentro:
                   'application/octet-stream'],
        'doc'  => ['application/msword','application/octet-stream'],
        'xls'  => ['application/vnd.ms-excel','application/octet-stream'],
        'ppt'  => ['application/vnd.ms-powerpoint','application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/zip','application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/zip','application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation','application/zip','application/octet-stream'],
        'odt'  => ['application/vnd.oasis.opendocument.text','application/zip','application/octet-stream'],
        'ods'  => ['application/vnd.oasis.opendocument.spreadsheet','application/zip','application/octet-stream'],
    ];
    return $map[$ext] ?? [];
}

/** Nombre original saneado para mostrar/descargar. */
function sanitize_filename(string $name): string
{
    $name = basename($name);
    $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name);     // control chars
    $name = preg_replace('/[\/\\\\:*?"<>|]+/', '_', $name);    // chars problemáticos
    $name = trim($name);
    return $name !== '' ? mb_substr($name, 0, 200) : 'documento';
}

$uploadsDir = (string) config('uploads_dir');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'subir') {
        $communityId = (int)($_POST['community_id'] ?? 0);
        $categoryId  = (int)($_POST['category_id'] ?? 0) ?: null;
        $titulo      = trim((string)($_POST['titulo'] ?? ''));
        $desc        = trim((string)($_POST['descripcion'] ?? ''));
        $f           = $_FILES['archivo'] ?? null;
        $maxBytes    = (int) config('max_upload_mb') * 1024 * 1024;

        $err = null;
        if ($communityId <= 0) {
            $err = 'Selecciona una comunidad.';
        } elseif (!$f || $f['error'] !== UPLOAD_ERR_OK) {
            $err = 'No se recibió el archivo (¿supera el límite del servidor?).';
        } elseif ($f['size'] <= 0 || $f['size'] > $maxBytes) {
            $err = 'El archivo está vacío o supera el máximo (' . (int)config('max_upload_mb') . ' MB).';
        } else {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, allowed_doc_ext(), true)) {
                $err = 'Tipo de archivo no permitido (.' . e($ext) . ').';
            }
        }

        if ($err) {
            flash_set('error', $err);
            redirect($self);
        }

        // Comprobar que la comunidad existe.
        $chk = $pdo->prepare('SELECT id FROM communities WHERE id=?');
        $chk->execute([$communityId]);
        if (!$chk->fetch()) { flash_set('error', 'Comunidad no válida.'); redirect($self); }

        if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0775, true);

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $f['tmp_name']) ?: 'application/octet-stream';
        finfo_close($finfo);

        // El contenido real debe concordar con la extensión declarada.
        // Bloquea, p. ej., un .pdf que en realidad es HTML/SVG ejecutable.
        if (!in_array($mime, allowed_doc_mimes($ext), true)) {
            flash_set('error', 'El contenido del archivo no coincide con su extensión (.' . e($ext) . ').');
            redirect($self);
        }

        $real = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = rtrim($uploadsDir, '/\\') . DIRECTORY_SEPARATOR . $real;

        if (!move_uploaded_file($f['tmp_name'], $dest)) {
            flash_set('error', 'No se pudo guardar el archivo en el servidor.');
            redirect($self);
        }
        @chmod($dest, 0640);

        $original = sanitize_filename($f['name']);
        if ($titulo === '') $titulo = $original;

        $pdo->prepare(
            'INSERT INTO documents
               (community_id, category_id, titulo, descripcion, filename_real, filename_original, filesize, mime, uploaded_by)
             VALUES (?,?,?,?,?,?,?,?,?)'
        )->execute([$communityId, $categoryId, $titulo, $desc, $real, $original, (int)$f['size'], $mime, (int)$admin['id']]);

        audit((int)$admin['id'], 'doc_upload', $titulo);
        flash_set('ok', 'Documento subido.');
        redirect($self . '?community=' . $communityId);

    } elseif ($accion === 'borrar') {
        $id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare('SELECT filename_real, community_id FROM documents WHERE id=?');
        $st->execute([$id]);
        if ($d = $st->fetch()) {
            $path = rtrim($uploadsDir, '/\\') . DIRECTORY_SEPARATOR . $d['filename_real'];
            if (is_file($path)) @unlink($path);
            $pdo->prepare('DELETE FROM documents WHERE id=?')->execute([$id]);
            audit((int)$admin['id'], 'doc_delete', "#$id");
            flash_set('ok', 'Documento eliminado.');
            redirect($self . '?community=' . (int)$d['community_id']);
        }
        redirect($self);
    }
}

// --- Datos vista ---
$communities = $pdo->query('SELECT id, nombre FROM communities ORDER BY nombre')->fetchAll();
$categories  = $pdo->query('SELECT id, nombre FROM doc_categories ORDER BY orden, nombre')->fetchAll();

$filter = (int)($_GET['community'] ?? 0);
$sql = 'SELECT d.*, c.nombre AS comunidad, dc.nombre AS categoria
        FROM documents d
        JOIN communities c ON c.id=d.community_id
        LEFT JOIN doc_categories dc ON dc.id=d.category_id';
$params = [];
if ($filter > 0) { $sql .= ' WHERE d.community_id=?'; $params[] = $filter; }
$sql .= ' ORDER BY d.uploaded_at DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$docs = $st->fetchAll();

admin_page_open('Documentos', 'documentos');
?>
<h1 style="font-size:28px">Documentos</h1>

<?php if (!$communities): ?>
  <div class="flash info">Crea una comunidad antes de subir documentos.</div>
<?php else: ?>
  <div class="card">
    <h3 style="font-size:19px">Subir documento</h3>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="subir">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="field">
          <label for="community_id">Comunidad *</label>
          <select id="community_id" name="community_id" required>
            <option value="">— Elegir —</option>
            <?php foreach ($communities as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $filter === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="category_id">Categoría</label>
          <select id="category_id" name="category_id">
            <option value="">— Sin categoría —</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>"><?= e($cat['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="field">
        <label for="titulo">Título (opcional — si se deja vacío se usa el nombre del archivo)</label>
        <input id="titulo" name="titulo" value="">
      </div>
      <div class="field">
        <label for="descripcion">Descripción (opcional)</label>
        <input id="descripcion" name="descripcion" value="">
      </div>
      <div class="field">
        <label for="archivo">Archivo * <span class="muted" style="font-weight:400">(máx <?= (int)config('max_upload_mb') ?> MB · PDF, imágenes, Office, ZIP…)</span></label>
        <input id="archivo" name="archivo" type="file" required>
      </div>
      <button class="btn btn-primary" type="submit">Subir documento</button>
    </form>
  </div>

  <div style="display:flex;align-items:center;gap:12px;margin:28px 0 12px">
    <h3 style="font-size:19px;margin:0">Documentos subidos</h3>
    <form method="get" style="margin-left:auto">
      <select name="community" onchange="this.form.submit()">
        <option value="0">Todas las comunidades</option>
        <?php foreach ($communities as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $filter === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <table>
    <thead><tr><th>Documento</th><th>Comunidad</th><th>Categoría</th><th>Tamaño</th><th>Fecha</th><th></th></tr></thead>
    <tbody>
    <?php if (!$docs): ?>
      <tr><td colspan="6" class="muted">No hay documentos<?= $filter ? ' en esta comunidad' : '' ?>.</td></tr>
    <?php else: foreach ($docs as $d): ?>
      <tr>
        <td><strong><?= e($d['titulo']) ?></strong><br><span class="muted" style="font-size:13px"><?= e($d['filename_original']) ?></span></td>
        <td class="muted"><?= e($d['comunidad']) ?></td>
        <td class="muted"><?= e($d['categoria'] ?: 'Sin categoría') ?></td>
        <td class="muted"><?= e(format_bytes((int)$d['filesize'])) ?></td>
        <td class="muted"><?= e(date('d/m/Y', strtotime($d['uploaded_at']))) ?></td>
        <td style="text-align:right;white-space:nowrap">
          <a class="btn btn-line" style="min-height:34px;padding:6px 12px" href="<?= e(base_url()) ?>/panel/download.php?doc=<?= (int)$d['id'] ?>">Ver</a>
          <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar este documento?')">
            <?= csrf_field() ?><input type="hidden" name="accion" value="borrar"><input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
            <button class="btn btn-line" style="min-height:34px;padding:6px 12px;color:#8A2B2B;border-color:#E6BBBB" type="submit">Borrar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php admin_page_close();
