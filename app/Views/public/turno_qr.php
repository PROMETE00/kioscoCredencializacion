<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Credencialización | Turno generado</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/public-turno.css') ?>">
</head>
<body>
<?php $turno = $turno ?? []; ?>

<header class="pt-header">
  <div class="pt-header__inner">
    <div class="pt-header__left">
      <a href="<?= base_url('turno') ?>" aria-label="Ir al inicio público">
        <img class="d-logo" src="<?= base_url('assets/img/Instituto_Tecnologico_de_Oaxaca.png') ?>" alt="Logo" onerror="this.style.display='none'">
      </a>
      <a href="<?= base_url('turno') ?>" class="pt-brand pt-brand--link" aria-label="Ir al inicio público">
        <h1 class="pt-brand__title">Instituto Tecnológico de Oaxaca</h1>
        <div class="pt-brand__subtitle">Sistema de turnos para credencialización</div>
      </a>
    </div>
  </div>
</header>

<main class="pt-main">
  <div class="pt-container">
    <section class="pt-shell">
      <div class="pt-grid">
        <div class="pt-panel pt-panel--main">
          <div class="pt-kicker">Turno confirmado</div>
          <h2 class="pt-title">Tu turno fue generado correctamente</h2>
          <p class="pt-text">
            Guarda el folio, el PDF y el código QR. Con cualquiera de ellos podrás dar seguimiento al avance de tu trámite.
          </p>

          <div class="pt-verified-banner">
            <span class="pt-checkmark">✓</span>
            <span>Se generó un turno activo para <?= esc($turno['nombre_completo'] ?? 'Alumno') ?>.</span>
          </div>

          <div class="pt-folio-panel">
            <div class="pt-folio-panel__label">Folio de atención</div>
            <div class="pt-folio-display"><?= esc($turno['folio'] ?? 'N/A') ?></div>
            <div class="pt-folio-panel__subtext">Fecha de generación: <?= esc($turno['fecha_generacion_texto'] ?? 'N/A') ?></div>
          </div>

          <div class="pt-data-grid">
            <div class="pt-info-card">
              <div class="pt-info-card__title">Datos del alumno</div>
              <div class="pt-info-list">
                <div><span>Nombre</span><strong><?= esc($turno['nombre_completo'] ?? 'N/A') ?></strong></div>
                <div><span>No. control / ficha</span><strong><?= esc($turno['identificador'] ?? 'N/A') ?></strong></div>
                <div><span>Carrera</span><strong><?= esc($turno['carrera'] ?? 'N/A') ?></strong></div>
                <div><span>Campus</span><strong><?= esc($turno['campus'] ?? 'N/A') ?></strong></div>
              </div>
            </div>

            <div class="pt-info-card">
              <div class="pt-info-card__title">Datos del turno</div>
              <div class="pt-info-list">
                <div><span>Estatus</span><strong><?= esc($turno['estatus'] ?? 'N/A') ?></strong></div>
                <div><span>Etapa actual</span><strong><?= esc($turno['etapa'] ?? 'N/A') ?></strong></div>
                <div><span>Expira</span><strong><?= esc($turno['fecha_expira_texto'] ?? 'N/A') ?></strong></div>
                <div><span>Seguimiento</span><strong>Disponible en línea</strong></div>
              </div>
            </div>
          </div>

          <div class="pt-btn-group">
            <a href="<?= esc($turno['seguimiento_url'] ?? base_url('turno')) ?>" class="pt-btn pt-btn--success">
              Ver seguimiento
            </a>
            <a href="<?= esc($turno['pdf_url'] ?? '#') ?>" class="pt-btn pt-btn--secondary">
              Descargar PDF
            </a>
          </div>

          <div class="pt-note">
            Si no puedes abrir el QR, conserva este folio: <strong><?= esc($turno['folio'] ?? 'N/A') ?></strong>.
          </div>
        </div>

        <aside class="pt-panel pt-panel--aside">
          <h3 class="pt-side-title">Código QR de seguimiento</h3>
          <div class="pt-qr-container">
            <div class="pt-qr-box">
              <img src="<?= esc($turno['qr_url'] ?? '') ?>" alt="Código QR de seguimiento" class="pt-qr-img">
            </div>
          </div>
          <div class="pt-note pt-note--tight">
            Escanéalo desde tu celular o entra directamente a:<br>
            <a class="pt-link-break" href="<?= esc($turno['seguimiento_url'] ?? '#') ?>"><?= esc($turno['seguimiento_url'] ?? '') ?></a>
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
