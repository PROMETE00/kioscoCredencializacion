<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= esc($title ?? 'Kiosco Credencialización') ?></title>

  <link rel="stylesheet" href="<?= base_url('assets/css/discere-theme.css') ?>">
  <?= $this->renderSection('head') ?>
  <link rel="stylesheet" href="<?= base_url('assets/css/user-menu.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/user-dropdown.css') ?>">
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
<?php $auth = session()->get('auth') ?? []; ?>
<?php
  $userName  = $auth['full_name'] ?? ($auth['username'] ?? 'Usuario');
  $rolCodigo = $auth['role_code'] ?? null;   // si aún no lo guardas, abajo te doy fallback
  $isAdmin   = ($rolCodigo === 'ADMIN') || (($auth['role_id'] ?? 0) == 1); // fallback temporal
?>

<div class="d-header__right">
  <div class="u-menu" id="uMenu">
    <button class="u-trigger" type="button" aria-haspopup="menu" aria-expanded="false">
      <span class="u-hello">Hola, <strong><?= esc($userName) ?></strong></span>
      <span class="u-avatar" aria-hidden="true"></span>
    </button>

    <div class="u-dd" role="menu" aria-label="Menú de usuario">
      <a class="u-item" role="menuitem" href="<?= site_url('perfil') ?>">
        <span class="u-ico" aria-hidden="true">
          <img class="u-ico-img" src="<?= base_url('assets/img/userSVG.svg') ?>" alt="">
        </span>
        Perfil
      </a>

      <a class="u-item" role="menuitem" href="<?= site_url('config') ?>">
        <span class="u-ico" aria-hidden="true">
          <img class="u-ico-img" src="<?= base_url('assets/img/settingsSVG.svg') ?>" alt="">
        </span>
        Configuración
      </a>

      <?php if ($isAdmin): ?>
        <div class="u-sep" aria-hidden="true"></div>
        <a class="u-item" role="menuitem" href="<?= site_url('admin/usuarios') ?>">
          <span class="u-ico" aria-hidden="true">
            <img class="u-ico-img" src="<?= base_url('assets/img/createUserSVG.svg') ?>" alt="">
          </span>
          Usuarios
        </a>
      <?php endif; ?>

      <div class="u-sep" aria-hidden="true"></div>

     <a class="u-item u-item--danger" role="menuitem" href="<?= site_url('logout') ?>">
      <span class="u-ico" aria-hidden="true">
        <img class="u-ico-img" src="<?= base_url('assets/img/LogOutSVG.svg') ?>" alt="">
      </span>
        Cerrar sesión
      </a>
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
   href="<?= site_url('captura/huella') ?>">

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
    <span>Derechos Reservados © <?= date('Y') ?> Instituto Tecnológico de Oaxaca</span>
    <span>Desarrollado con <img class="d-ico-img" src="<?= base_url('assets/img/heartSVG.svg') ?>" alt=""></span>
  </footer>

  <?= $this->renderSection('scripts') ?>

<script>
(function(){
  const menu = document.getElementById('uMenu');
  if(!menu) return;

  const btn = menu.querySelector('.u-trigger');

  function close(){
    menu.classList.remove('is-open');
    btn?.setAttribute('aria-expanded','false');
  }
  function toggle(){
    const open = menu.classList.toggle('is-open');
    btn?.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  btn?.addEventListener('click', (e)=>{ e.stopPropagation(); toggle(); });
  document.addEventListener('click', close);
  document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') close(); });
})();
</script>
</body>
</html>
