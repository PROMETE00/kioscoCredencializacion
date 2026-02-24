<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= esc($title ?? 'Acceso Kiosco') ?></title>

  <link rel="stylesheet" href="<?= base_url('assets/css/kiosco-login.css') ?>">
</head>
<body class="k-login">

  <main class="k-login__wrap">

    <section class="k-hero" aria-label="Información del sistema">
      <div class="k-hero__brand">
        <img class="k-hero__logo" src="<?= base_url('assets/img/Instituto_Tecnologico_de_Oaxaca.png') ?>"
             alt="TecNM" onerror="this.style.display='none'">
        <div class="k-hero__titles">
          <div class="k-hero__title">Kiosco de Credencialización</div>
          <div class="k-hero__subtitle">TecNM · DISCERE</div>
        </div>
      </div>

      <p class="k-hero__desc">
        Acceso para estaciones: <strong>Foto</strong>, <strong>Firma</strong>, <strong>Huella</strong> e <strong>Impresión</strong>.
        Inicia sesión para continuar.
      </p>

      <div class="k-pill" role="note">
        <span class="k-dot" aria-hidden="true"></span>
        Acceso restringido · Personal autorizado
      </div>

      <div class="k-hero__meta">
        <div class="k-meta">
          <div class="k-meta__k">Entorno</div>
          <div class="k-meta__v">Producción / Local</div>
        </div>
        <div class="k-meta">
          <div class="k-meta__k">Seguridad</div>
          <div class="k-meta__v">Contraseñas cifradas</div>
        </div>
      </div>
    </section>

    <section class="k-card" aria-label="Formulario de inicio de sesión">
      <header class="k-card__header">
        <h1 class="k-card__title">Iniciar sesión</h1>
        <p class="k-card__sub">Usa tu usuario de estación o administrador.</p>
      </header>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="k-alert k-alert--error" role="alert">
          <?= esc(session()->getFlashdata('error')) ?>
        </div>
      <?php endif; ?>

      <form class="k-form" method="post" action="<?= site_url('login') ?>">
        <?= csrf_field() ?>

        <label class="k-field">
          <span class="k-field__label">Usuario</span>
          <input class="k-field__input"
                 name="usuario"
                 value="<?= esc(old('usuario')) ?>"
                 autocomplete="username"
                 placeholder="Ej. admin, foto, firma…">
        </label>

        <label class="k-field">
          <span class="k-field__label">Contraseña</span>
          <input class="k-field__input"
                 type="password"
                 name="password"
                 autocomplete="current-password"
                 placeholder="••••••••">
        </label>

        <button class="k-btn k-btn--primary" type="submit">
          Entrar
        </button>

        <footer class="k-card__foot">
          <span>© <?= date('Y') ?> Kiosco</span>
          <span class="k-sep">·</span>
          <span>Soporte: Área de Sistemas</span>
        </footer>
      </form>
    </section>

  </main>

</body>
</html>