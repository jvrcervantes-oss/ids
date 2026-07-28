<?php
/**
 * IDS Fincas — Landing pública (Administración de Fincas, Murcia)
 */
require_once __DIR__ . '/includes/auth.php';   // sesión + CSRF (público)

$B       = base_url();
$enviado = isset($_GET['enviado']);
$errForm = isset($_GET['error']);
$TEL      = '968 274 351';
$TEL_LINK = '+34968274351';
$WHATSAPP = 'https://wa.me/34612345678';
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>IDS Fincas — Administración de fincas en Murcia | Le cogemos el teléfono</title>
<meta name="description" content="Administración de fincas en Murcia con trato cercano: cuentas claras, juntas en paz y averías resueltas. Colegiada n.º 1.847. Sin permanencia. Pida presupuesto: 968 274 351.">
<link rel="canonical" href="<?= e($B) ?>/">
<meta property="og:type" content="website">
<meta property="og:title" content="IDS Fincas — Administración de fincas en Murcia">
<meta property="og:description" content="Cuentas claras, juntas en paz y averías resueltas. Le atiende una persona que conoce su comunidad. Sin permanencia.">
<meta property="og:url" content="<?= e($B) ?>/">
<meta property="og:image" content="<?= e($B) ?>/assets/img/og.jpg">
<meta name="twitter:card" content="summary_large_image">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600&family=Public+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e($B) ?>/assets/css/site.css">
<script type="application/ld+json">
{
  "@context":"https://schema.org","@type":"ProfessionalService",
  "name":"IDS Fincas — Administración de Fincas",
  "description":"Administración de fincas y comunidades de propietarios en Murcia.",
  "areaServed":"Murcia","telephone":"+34968274351",
  "address":{"@type":"PostalAddress","streetAddress":"Calle Trapería 12, 1.º","addressLocality":"Murcia","postalCode":"30001","addressCountry":"ES"},
  "openingHours":"Mo-Fr 09:00-14:00,16:30-19:00","url":"<?= e($B) ?>/"
}
</script>
</head>
<body>

<header class="site-header">
  <div class="container">
    <a href="#inicio" class="logo">
      <span class="mark"><span>IDS</span></span>
      <span><span class="name">IDS Fincas</span><br><span class="city">MURCIA</span></span>
    </a>
    <button class="nav-toggle" aria-label="Abrir menú" onclick="document.getElementById('nav').classList.toggle('open')"><span></span><span></span><span></span></button>
    <nav class="site-nav" id="nav">
      <a class="link" href="#servicios">Servicios</a>
      <a class="link" href="#nosotros">Nosotros</a>
      <a class="link" href="#contacto">Contacto</a>
      <a class="btn btn-ghost fx-fill" style="min-height:46px;padding:10px 22px;font-size:15px" href="<?= e($B) ?>/panel/login.php">Acceso clientes</a>
    </nav>
  </div>
</header>

<!-- HERO -->
<section id="inicio" class="hero" style="padding:0">
  <div class="container">
    <div class="hero-copy">
      <div class="eyebrow">Administración de fincas en Murcia</div>
      <h1>¿Cansado de que nadie le coja el <span class="squiggle">teléfono<svg viewBox="0 0 200 12" preserveAspectRatio="none" aria-hidden="true"><path d="M4 9 C 55 3, 140 2, 196 6" stroke="#D08A2E" stroke-width="5" fill="none" stroke-linecap="round"></path></svg></span>?</h1>
      <p class="lead">Aquí se lo cogemos. Llevamos las cuentas claras, las juntas en paz y las averías resueltas — y cuando llama, le atiende una persona que conoce su comunidad.</p>
      <div class="cta-row">
        <a class="btn btn-gold" href="#contacto">Pida presupuesto</a>
        <span style="color:var(--gris)">o llame al <a class="phone" style="font-size:26px" href="tel:<?= e($TEL_LINK) ?>"><?= e($TEL) ?></a></span>
      </div>
      <div class="micro">Sin permanencia &nbsp;·&nbsp; Presupuesto sin compromiso &nbsp;·&nbsp; Atendemos su llamada, no un contestador</div>
    </div>
    <div class="hero-art arch" aria-hidden="true"></div>
  </div>
</section>

<!-- NOSOTROS -->
<section id="nosotros" class="bg-cielo">
  <div class="container">
    <div class="nosotros-grid">
      <div class="nosotros-art arch" aria-hidden="true"></div>
      <div>
        <div class="eyebrow">Quién le atiende</div>
        <h2>Soy Isabel Domínguez, colegiada n.º 1.847, y llevo 15 años cuidando comunidades en Murcia.</h2>
        <p>Empecé llevando la finca de mis padres en el barrio de San Basilio. Hoy administro más de 120 comunidades, pero la manera de trabajar es la misma: conocer cada edificio, cada presidente y cada vecino por su nombre.</p>
        <p>Si su comunidad necesita un cambio, se lo ponemos fácil: traspaso ordenado, sin permanencia y todo por escrito.</p>
      </div>
    </div>
  </div>
</section>

<!-- SERVICIOS -->
<section id="servicios" class="bg-lino">
  <div class="container">
    <div class="eyebrow">Servicios</div>
    <h2 class="section-title">Lo que hacemos por su comunidad</h2>
    <div class="cards">
      <?php
      $servicios = [
        ['Gestión de comunidades', 'El día a día de su finca: proveedores, seguros, empleados y papeleo al día, sin que usted tenga que perseguir a nadie.'],
        ['Contabilidad y derramas', 'Cuentas que se entienden a la primera. Presupuesto anual claro, morosidad controlada y derramas explicadas por escrito.'],
        ['Actas y juntas', 'Convocamos, moderamos y dejamos el acta lista. Juntas que empiezan y acaban a su hora, con los acuerdos por escrito.'],
        ['Incidencias y mantenimiento', '¿Gotera, ascensor parado, luz fundida? Lo gestionamos con oficios de confianza y le contamos cómo va, sin que pregunte.'],
        ['Asesoría a la comunidad', 'Dudas de propiedad horizontal, ayudas a la rehabilitación, vecinos morosos… Le orientamos antes de que el problema crezca.'],
      ];
      foreach ($servicios as $s): ?>
        <div class="card-svc">
          <div class="ico"><svg width="28" height="28" viewBox="0 0 32 32" fill="none" aria-hidden="true"><rect x="7" y="6" width="18" height="22" stroke="#6260FF" stroke-width="1.8"></rect><rect x="11.5" y="11" width="3.5" height="3.5" fill="#6260FF"></rect><rect x="17" y="11" width="3.5" height="3.5" fill="#6260FF"></rect><rect x="13.5" y="23" width="5" height="5" fill="#6260FF"></rect></svg></div>
          <h3><?= e($s[0]) ?></h3>
          <p><?= e($s[1]) ?></p>
        </div>
      <?php endforeach; ?>
      <div class="card-svc dashed">
        <h3>¿Otra cosa?</h3>
        <p style="margin-bottom:16px">Cuéntenos su caso por teléfono y le decimos si podemos ayudarle. Así de simple.</p>
        <a class="phone" style="font-size:22px" href="tel:<?= e($TEL_LINK) ?>"><?= e($TEL) ?></a>
      </div>
    </div>
  </div>
</section>

<!-- CIFRAS -->
<section class="bg-tinta">
  <div class="container">
    <div class="stats">
      <div class="stat"><div class="num">15 años</div><div class="lbl">de ejercicio colegiado, siempre en Murcia</div></div>
      <div class="stat"><div class="num">+120</div><div class="lbl">comunidades que confían en nosotros</div></div>
      <div class="stat"><div class="num">+3.400</div><div class="lbl">vecinos a los que atendemos por su nombre</div></div>
    </div>
  </div>
</section>

<!-- TESTIMONIOS -->
<section class="bg-huerta">
  <div class="container">
    <div class="eyebrow" style="color:#C9C9F2">Lo que dicen las comunidades</div>
    <h2 class="section-title">Vecinos con la casa en orden</h2>
    <div class="quotes">
      <?php
      $quotes = [
        ['Antes tardaban semanas en arreglar la luz del portal. Ahora llamo por la mañana y por la tarde ya ha pasado el electricista.', 'Pedro Martínez', 'Presidente · Edificio Levante, Murcia'],
        ['Las cuentas se entienden. En la última junta no hubo ni una sola discusión por los números, y eso aquí es decir mucho.', 'Carmen Hernández', 'Propietaria · Residencial La Flota'],
        ['La derrama del ascensor nos la explicaron por escrito, cuota a cuota. Sin sorpresas y sin letra pequeña.', 'Antonio García', 'Presidente · Comunidad San Basilio'],
      ];
      foreach ($quotes as $q): ?>
        <div class="quote"><div class="mark">“</div><p><?= e($q[0]) ?></p><div class="who"><?= e($q[1]) ?></div><div class="role"><?= e($q[2]) ?></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CÓMO TRABAJAMOS -->
<section class="bg-cielo">
  <div class="container">
    <div class="eyebrow">Cómo trabajamos</div>
    <h2 class="section-title">Empezar es una llamada</h2>
    <div class="steps">
      <div class="step"><div class="n">1</div><h3>Nos llama</h3><p>Al <?= e($TEL) ?>. Le atiende una persona, le escuchamos y le hacemos las preguntas justas. Diez minutos.</p></div>
      <div class="step"><div class="n">2</div><h3>Visitamos su comunidad</h3><p>Vamos a su finca, vemos el estado real del edificio y hablamos con el presidente o con quien usted nos diga.</p></div>
      <div class="step"><div class="n">3</div><h3>Presupuesto sin compromiso</h3><p>Cerrado y por escrito, para llevarlo a su junta. Si dicen que sí, nos encargamos del traspaso. Sin permanencia.</p></div>
    </div>
  </div>
</section>

<!-- CONTACTO -->
<section id="contacto" class="bg-lino">
  <div class="container">
    <div class="eyebrow">Contacto</div>
    <h2 class="section-title">Cuéntenos qué le pasa a su comunidad</h2>
    <div class="contact-grid">
      <div class="contact-form">
        <?php if ($enviado): ?>
          <div class="success">
            <div style="font-family:'Bricolage Grotesque',sans-serif;font-weight:600;font-size:26px;margin-bottom:10px">Gracias. Le llamamos nosotros.</div>
            <p style="margin:0;color:var(--texto)">Hemos recibido su solicitud. Le llamaremos en horario de oficina, normalmente el mismo día.</p>
          </div>
        <?php else: ?>
          <form method="post" action="<?= e($B) ?>/contacto">
            <?= csrf_field() ?>
            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
            <label><span class="lbl">Su nombre</span>
              <input name="nombre" type="text" required placeholder="Nombre y apellidos"></label>
            <label><span class="lbl">Su teléfono</span>
              <input name="telefono" type="tel" required placeholder="Le llamamos nosotros"></label>
            <label><span class="lbl">¿Qué le pasa a su comunidad? <span style="font-weight:400;color:var(--gris)">(opcional)</span></span>
              <textarea name="mensaje" rows="3" placeholder="Por ejemplo: somos 24 vecinos y el administrador actual no nos responde"></textarea></label>
            <?php if ($errForm): ?><div style="font-size:15px;font-weight:600;color:var(--oro-text);margin-bottom:14px">Por favor, indíquenos su nombre y un teléfono de contacto.</div><?php endif; ?>
            <button class="btn btn-gold" type="submit" style="width:100%">Pida presupuesto</button>
            <div style="font-size:14px;color:var(--gris);margin-top:12px">Sin permanencia. Solo usamos sus datos para atender su solicitud.</div>
          </form>
        <?php endif; ?>
      </div>
      <div class="contact-info">
        <div>
          <div style="font-size:15px;font-weight:500;color:var(--gris);margin-bottom:4px">Llámenos — le atendemos nosotros</div>
          <a class="big-phone" href="tel:<?= e($TEL_LINK) ?>"><?= e($TEL) ?></a>
        </div>
        <a class="btn btn-navy" style="align-self:flex-start" href="<?= e($WHATSAPP) ?>" target="_blank" rel="noopener">Escríbanos por WhatsApp</a>
        <div class="divider">
          <div style="font-size:15px;font-weight:500;color:var(--gris);margin-bottom:4px">Horario de atención</div>
          <div>Lunes a viernes, de 9:00 a 14:00 y de 16:30 a 19:00</div>
        </div>
        <div>
          <div style="font-size:15px;font-weight:500;color:var(--gris);margin-bottom:8px">Dónde estamos</div>
          <div>Calle Trapería, 12, 1.º — 30001 Murcia</div>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="site-footer">
  <div class="container">
    <div class="footer-cols">
      <a href="#inicio" class="logo"><span class="mark"><span>IDS</span></span><span><span class="name" style="color:var(--lino)">IDS Fincas</span><br><span class="city">MURCIA</span></span></a>
      <div class="col">
        <div>Isabel Domínguez Soto</div>
        <div>Calle Trapería, 12, 1.º — 30001 Murcia</div>
        <div><?= e($TEL) ?></div>
      </div>
      <div class="col">
        <div>Colegiada n.º 1.847 — CAF Región de Murcia</div>
        <div style="display:flex;gap:16px;margin-top:6px"><a href="#contacto">Aviso legal</a><a href="#contacto">Privacidad</a></div>
      </div>
    </div>
    <div class="footer-bottom">© <?= date('Y') ?> IDS Administración de Fincas. Todos los derechos reservados.</div>
  </div>
</footer>

</body>
</html>
