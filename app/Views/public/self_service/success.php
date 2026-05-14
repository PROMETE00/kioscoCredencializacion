<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Credentialing | Success</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/public-turno.css') ?>">
  <style>
    .pt-success-card {
      max-width: 500px;
      margin: 60px auto;
      text-align: center;
      background: #fff;
      padding: 40px;
      border-radius: 24px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.05);
      border: 1px solid #eef2f7;
    }

    .pt-success-icon {
      width: 80px;
      height: 80px;
      background: #f0fdf4;
      color: #16a34a;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px;
    }

    .pt-success-title {
      font-size: 24px;
      font-weight: 800;
      color: #111827;
      margin-bottom: 12px;
    }

    .pt-success-text {
      font-size: 15px;
      color: #64748b;
      line-height: 1.6;
      margin-bottom: 30px;
    }

    .pt-ticket-box {
      background: #f8fafc;
      border: 2px dashed #e2e8f0;
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 30px;
    }

    .pt-ticket-label {
      font-size: 12px;
      font-weight: 700;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 4px;
    }

    .pt-ticket-number {
      font-size: 32px;
      font-weight: 900;
      color: #2F6DF6;
    }
  </style>
</head>
<body>

<?php
  $ticket  = $ticket ?? [];
  $student = $student ?? [];
?>

<header class="pt-header">
  <div class="pt-header__inner">
    <div class="pt-header__left">
      <a href="<?= base_url('self-service') ?>">
        <img class="d-logo" src="<?= base_url('assets/img/Instituto_Tecnologico_de_Oaxaca.png') ?>" alt="Logo">
      </a>
      <a href="<?= base_url('self-service') ?>" class="pt-brand pt-brand--link">
        <h1 class="pt-brand__title">Instituto Tecnológico de Oaxaca</h1>
        <div class="pt-brand__subtitle">Self-Service Credentialing</div>
      </a>
    </div>
  </div>
</header>

<main class="pt-main">
  <div class="pt-container">
    <div class="pt-success-card">
      <div class="pt-success-icon">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </div>

      <h2 class="pt-success-title">All Done!</h2>
      <p class="pt-success-text">
        Your biometrics have been captured successfully. Your request is now being processed.
      </p>

      <div class="pt-ticket-box">
        <div class="pt-ticket-label">Your Ticket Number</div>
        <div class="pt-ticket-number">#<?= esc($ticket['ticket_number'] ?? '---') ?></div>
      </div>

      <a href="<?= base_url('self-service') ?>" class="pt-btn pt-btn--primary" style="width: 100%;">
        Return to Home
      </a>
    </div>
  </div>
</main>

<footer class="d-footer">
  <span>All Rights Reserved © <?= date('Y') ?> Instituto Tecnológico de Oaxaca</span>
</footer>

</body>
</html>
