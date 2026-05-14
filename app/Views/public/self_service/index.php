<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Credencialización | Start Process</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/public-turno.css') ?>">
</head>
<body class="">

<?php
  $identifier = $identifier ?? '';
  $notFound = $notFound ?? false;
  $error = session()->getFlashdata('error') ?? null;
?>

<header class="pt-header">
  <div class="pt-header__inner">
    <div class="pt-header__left">
      <a href="<?= base_url('self-service') ?>" aria-label="Home">
        <img class="d-logo" src="<?= base_url('assets/img/Instituto_Tecnologico_de_Oaxaca.png') ?>" alt="Logo"
            onerror="this.style.display='none'">
      </a>
      <a href="<?= base_url('self-service') ?>" class="pt-brand pt-brand--link" aria-label="Home">
        <h1 class="pt-brand__title">Instituto Tecnológico de Oaxaca</h1>
        <div class="pt-brand__subtitle">
          Self-Service Credentialing
        </div>
      </a>
    </div>
    <div class="pt-header__right">
      <a href="<?= base_url('admin/login') ?>" class="pt-btn pt-btn--secondary">
        Administration
      </a>
    </div>
  </div>
</header>

<main class="pt-main">
  <div class="pt-container">

    <section class="pt-shell">
      <div class="pt-grid">

        <div class="pt-panel pt-panel--main">
          <div class="pt-kicker">Self-Service</div>

          <h2 class="pt-title">Start process</h2>

          <p class="pt-text">
            1.- Enter your <strong>Control No.</strong> or <strong>Registration No.</strong>.<br>
            2.- <strong>Verify</strong> that your information is <strong>correct</strong>.<br>
            3.- Continue to register and <strong>capture your signature</strong>.
          </p>

          <?php if (!empty($error)): ?>
            <div class="pt-error">
              <?= esc($error) ?>
            </div>
          <?php endif; ?>

          <!-- SEARCH FORM -->
          <form method="post" action="<?= base_url('self-service/identify') ?>" class="pt-form">
            <?= csrf_field() ?>

            <div class="pt-field">
              <label class="pt-label" for="identifier">
                Control No. / Registration No.
              </label>

              <div class="pt-input-wrap">
                <input
                  class="pt-input"
                  id="identifier"
                  name="identifier"
                  type="text"
                  value="<?= esc($identifier) ?>"
                  placeholder="e.g., 21160727"
                  inputmode="numeric"
                  autocomplete="off"
                  autocapitalize="off"
                  spellcheck="false"
                  required
                >
              </div>

              <div class="pt-help">
                Enter only numbers, without spaces or dashes.
              </div>
            </div>

            <div class="pt-actions">
              <button class="pt-btn" type="submit">
                Find Student
              </button>
            </div>
          </form>

          <?php if ($notFound): ?>
            <div class="pt-warning">
              No student was found with that control or registration number.
            </div>
          <?php endif; ?>

          <div class="pt-note">
            * Access to your record expires at the end of the day. You must complete the capture today.
          </div>
        </div>

        <aside class="pt-panel">
          <h3 class="pt-side-title">Important Information</h3>

          <div class="pt-info-card">
            <div class="pt-info-card__title">What will you do here?</div>
            <ul class="pt-list">
              <li>Identification as a student of the Institute.</li>
              <li>Validation of data in the system.</li>
              <li>Signature capture for your official credential.</li>
            </ul>
          </div>

          <div class="pt-info-card">
            <div class="pt-info-card__title">Before you begin</div>
            <ul class="pt-list">
              <li>Verify that your control or registration number is correct.</li>
              <li>Once the data is verified, proceed to capture your signature.</li>
            </ul>
          </div>
        </aside>

      </div>
    </section>
  </div>
</main>

  <footer class="d-footer">
    <span>Derechos Reservados © <?= date('Y') ?> Instituto Tecnológico de Oaxaca</span>
    <span>Desarrollado con <img class="d-ico-img" src="<?= base_url('assets/img/heartSVG.svg') ?>" alt=""></span>
  </footer>

</body>
</html>
