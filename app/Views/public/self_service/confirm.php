<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Credencialización | Verify Information</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/public-turno.css') ?>">
</head>
<body class="">

<header class="pt-header">
  <div class="pt-header__inner">
    <div class="pt-header__left">
      <a href="<?= base_url('self-service') ?>">
        <img class="d-logo" src="<?= base_url('assets/img/Instituto_Tecnologico_de_Oaxaca.png') ?>" alt="Logo">
      </a>
      <div class="pt-brand">
        <h1 class="pt-brand__title">Instituto Tecnológico de Oaxaca</h1>
        <div class="pt-brand__subtitle">Self-Service Credentialing</div>
      </div>
    </div>
  </div>
</header>

<main class="pt-main">
  <div class="pt-container">
    <section class="pt-shell">
      <div class="pt-panel pt-panel--main">
        <h2 class="pt-title">Verify your information</h2>
        
        <div class="pt-verified-banner">
          <span class="pt-checkmark">✓</span>
          <span>Information found. Please verify that the following data is correct.</span>
        </div>

        <div class="pt-student-card">
          <div class="pt-student-row">
            <span class="pt-student-label">Control / Registration No.</span>
            <strong class="pt-student-value"><?= esc($student['control_number'] ?? $student['registration_number']) ?></strong>
          </div>
          <div class="pt-student-row">
            <span class="pt-student-label">Full Name</span>
            <strong class="pt-student-value"><?= esc($student['full_name']) ?></strong>
          </div>
          <div class="pt-student-row">
            <span class="pt-student-label">Major</span>
            <strong class="pt-student-value"><?= esc($student['major_name']) ?></strong>
          </div>
        </div>

        <div class="pt-actions">
          <a href="<?= base_url('self-service') ?>" class="pt-btn pt-btn--secondary">Back</a>
          <a href="<?= base_url('self-service/signature') ?>" class="pt-btn pt-btn--success">Confirm & Continue</a>
        </div>
      </div>
    </section>
  </div>
</main>

</body>
</html>
