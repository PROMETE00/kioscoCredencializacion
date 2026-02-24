<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= esc($title ?? 'Kiosco Credencialización') ?></title>

  <link rel="stylesheet" href="<?= base_url('assets/css/discere-theme.css') ?>">
  <?= $this->renderSection('head') ?>
</head>

<body class="<?= trim($this->renderSection('bodyClass')) ?>">

<header class="d-header">
  <div class="d-header__inner">
    <div class="d-header__left">
      <img class="d-logo" src="<?= base_url('assets/img/Instituto_Tecnologico_de_Oaxaca.png') ?>" alt="Logo"
           onerror="this.style.display='none'">

      <div class="d-brand">
        <div class="d-title">DISCERE :: Tecnológico Nacional de México</div>
      </div>
    </div>

    <div class="d-header__right">
      <div class="d-user">
        <img class="d-avatar" src="<?= base_url('assets/img/user.png') ?>" alt="Usuario"
             onerror="this.style.display='none'">
        <div class="d-user__text">
          <div class="d-user__hello">Hola, <a href="#" class="d-link"><?= esc($userName ?? 'Usuario') ?></a></div>
        </div>
      </div>
    </div>
  </div>
</header>
<nav class="d-nav">
 <a class="d-nav__item <?= ($activeMenu ?? '') === 'Dashboard' ? 'is-active' : '' ?>"
   href="<?= site_url('admin') ?>">

  <span class="d-nav__icon" aria-hidden="true">
    <img class="d-ico-img" src="<?= base_url('assets/img/homeSVG.svg') ?>" alt="">
  </span>

  General
</a>

  <div class="d-nav__sep"></div>

  <a class="d-nav__item <?= ($activeMenu ?? '') === 'fotografia' ? 'is-active' : '' ?>"
     href="<?= site_url('captura') ?>">

    <span class="d-nav__icon" aria-hidden="true">
      <img class="d-ico-img" src="<?= base_url('assets/img/cameraSVG.svg') ?>" alt="">
    </span>

    Fotografia
  </a>

  <div class="d-nav__sep"></div>

  <a class="d-nav__item <?= ($activeMenu ?? '') === 'firma' ? 'is-active' : '' ?>"
   href="<?= site_url('captura/firma') ?>">

    <span class="d-nav__icon" aria-hidden="true">
      <img class="d-ico-img" src="<?= base_url('assets/img/signatureSVG.svg') ?>" alt="">
    </span>

    Firma
  </a>

  <div class="d-nav__sep"></div>

  <a class="d-nav__item <?= ($activeMenu ?? '') === 'huella' ? 'is-active' : '' ?>"
     href="#">

    <span class="d-nav__icon" aria-hidden="true">
      <img class="d-ico-img" src="<?= base_url('assets/img/touchSVG.svg') ?>" alt="">
    </span>

    Huella
  </a>

  <div class="d-nav__sep"></div>

  <a class="d-nav__item <?= ($activeMenu ?? '') === 'Imprimir' ? 'is-active' : '' ?>"
     href="#">

    <span class="d-nav__icon" aria-hidden="true">
      <img class="d-ico-img" src="<?= base_url('assets/img/printSVG.svg') ?>" alt="">
    </span>

    Imprimir
  </a>

</nav>


  <!-- Contenido -->
  <main class="d-page">
    <?= $this->renderSection('content') ?>
  </main>

  <footer class="d-footer">
    <span>Derechos Reservados © <?= date('Y') ?> DISCERE.</span>
    <span>Desarrollado con <img class="d-ico-img" src="<?= base_url('assets/img/heartSVG.svg') ?>" alt=""></span>
  </footer>

  <?= $this->renderSection('scripts') ?>
</body>
</html>
