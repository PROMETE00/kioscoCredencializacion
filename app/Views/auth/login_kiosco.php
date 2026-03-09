<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= esc($title ?? 'Acceso Kiosco') ?></title>

  <link rel="stylesheet" href="<?= base_url('assets/css/kiosco-login-v2.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/floating-lines.css') ?>">
  <script type="module" src="<?= base_url('assets/js/floating-lines.js') ?>"></script>
</head>

<body class="kl2">

  <!-- Fondo (solo canvas, NO contenido dentro) -->
  <div id="floatingLines" class="floating-lines-bg" aria-hidden="true"></div>

  <!-- Contenido centrado -->
  <main class="kl2-wrap">

    <!-- IZQUIERDA -->
    <section class="kl2-left" aria-label="Inicio de sesión">
      <div class="kl2-brand">
        <span class="kl2-dot" aria-hidden="true"></span>
        <span class="kl2-brand__name">Kiosco credencialización</span>
      </div>

      <h1 class="kl2-h1">Bienvenido a credencializacion</h1>
      <p class="kl2-sub">Acceso institucional para estaciones de credencialización.</p>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="kl2-alert" role="alert">
          <?= esc(session()->getFlashdata('error')) ?>
        </div>
      <?php endif; ?>

      <form class="kl2-form" method="post" action="<?= site_url('login') ?>">
        <?= csrf_field() ?>

        <label class="kl2-field">
          <span class="kl2-label">Usuario</span>
          <input class="kl2-input" name="usuario" value="<?= esc(old('usuario')) ?>"
                 autocomplete="username" placeholder="Ej. admin, foto, firma…">
        </label>

        <label class="kl2-field">
          <span class="kl2-label">Contraseña</span>
          <input class="kl2-input" type="password" name="password"
                 autocomplete="current-password" placeholder="••••••••">
        </label>

        <div class="kl2-row">
          <label class="kl2-check">
            <input type="checkbox" name="remember" value="1">
            <span>Recordarme</span>
          </label>

          <a class="kl2-link" href="#" aria-disabled="true">¿Olvidaste tu contraseña?</a>
        </div>

        <button class="kl2-btn" type="submit">Iniciar sesión</button>

        <div class="kl2-foot">
          © <?= date('Y') ?> TecNM · DISCERE · Kiosco
        </div>
      </form>
    </section>

    <!-- DERECHA -->
    <section class="kl2-right" aria-label="Panel visual">
      <div class="kl2-illustration">
        <img class="kl2-heroImg"
             src="<?= base_url('assets/img/login.svg') ?>"
             alt="Instituto Tecnológico de Oaxaca"
             onerror="this.style.display='none'">
      </div>

      <div class="kl2-cloud c1" aria-hidden="true"></div>
      <div class="kl2-cloud c2" aria-hidden="true"></div>
      <div class="kl2-cloud c3" aria-hidden="true"></div>
    </section>

  </main>

</body>
</html>