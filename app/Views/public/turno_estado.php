<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Credencialización | Seguimiento del turno</title>
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
        <div class="pt-brand__subtitle">Seguimiento público de credencialización</div>
      </a>
    </div>
  </div>
</header>

<main class="pt-main">
  <div class="pt-container">
    <section class="pt-shell">
      <?php if ($notFound): ?>
        <div class="pt-panel pt-panel--main pt-panel--single">
          <h2 class="pt-title">Turno no encontrado</h2>
          <div class="pt-error">
            El enlace proporcionado no es válido, el turno ya expiró o el proceso ya no está disponible para consulta.
          </div>
          <div class="pt-actions">
            <a href="<?= base_url('turno') ?>" class="pt-btn">Volver al inicio</a>
          </div>
        </div>
      <?php else: ?>
        <div class="pt-grid">
          <div class="pt-panel pt-panel--main" data-seguimiento-endpoint="<?= esc($turno['seguimiento_endpoint'] ?? '') ?>">
            <div class="pt-kicker">Seguimiento de turno</div>
            <div class="pt-title-row">
              <h2 class="pt-title">Folio: <span id="folio-text"><?= esc($turno['folio'] ?? 'N/A') ?></span></h2>
              <span id="estatus-badge" class="pt-badge <?= esc($turno['badge_class'] ?? 'pt-badge--waiting') ?>">
                <?= esc($turno['estatus'] ?? 'N/A') ?>
              </span>
            </div>

            <div class="pt-progress-banner" id="mensaje-progreso"><?= esc($turno['mensaje_progreso'] ?? 'Seguimiento disponible') ?></div>

            <div class="pt-status-grid">
              <article class="pt-metric-card">
                <span class="pt-metric-card__label">Tiempo estimado restante</span>
                <strong id="eta-texto" class="pt-metric-card__value"><?= esc($turno['eta_texto'] ?? 'N/A') ?></strong>
                <span class="pt-metric-card__hint">Cálculo dinámico según la atención actual</span>
              </article>

              <article class="pt-metric-card">
                <span class="pt-metric-card__label">Turnos antes que tú</span>
                <strong id="turnos-antes" class="pt-metric-card__value"><?= esc((string) ($turno['turnos_antes'] ?? 0)) ?></strong>
                <span class="pt-metric-card__hint">No incluye el turno que ya está en atención</span>
              </article>

              <article class="pt-metric-card">
                <span class="pt-metric-card__label">Turno atendido ahora</span>
                <strong id="turno-actual-folio" class="pt-metric-card__value pt-metric-card__value--sm">
                  <?= esc($turno['turno_actual_folio'] ?? 'Sin turno en atención') ?>
                </strong>
                <span id="turno-actual-etapa" class="pt-metric-card__hint">
                  <?= esc($turno['turno_actual_etapa'] ?? 'En espera de atención') ?>
                </span>
              </article>
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
                  <div><span>Etapa actual</span><strong id="etapa-texto"><?= esc($turno['etapa'] ?? 'N/A') ?></strong></div>
                  <div><span>Generado</span><strong><?= esc($turno['fecha_generacion_texto'] ?? 'N/A') ?></strong></div>
                  <div><span>Llamado</span><strong id="llamado-texto"><?= esc($turno['llamado_at_texto'] ?? 'Pendiente') ?></strong></div>
                  <div><span>Expira</span><strong><?= esc($turno['fecha_expira_texto'] ?? 'N/A') ?></strong></div>
                </div>
              </div>
            </div>

            <div class="pt-btn-group">
              <a href="<?= esc($turno['pdf_url'] ?? '#') ?>" class="pt-btn pt-btn--secondary">Descargar PDF</a>
            </div>

            <div class="pt-note">
              Esta vista se refresca automáticamente cada 30 segundos para ajustar el turno en atención y el tiempo estimado.
            </div>
          </div>

          <aside class="pt-panel pt-panel--aside">
            <h3 class="pt-side-title">QR y acceso rápido</h3>
            <div class="pt-qr-container">
              <div class="pt-qr-box">
                <img src="<?= esc($turno['qr_url'] ?? '') ?>" alt="Código QR de seguimiento" class="pt-qr-img">
              </div>
            </div>
            <div class="pt-note pt-note--tight">
              Escanea el QR o conserva este folio de respaldo:
              <strong><?= esc($turno['folio'] ?? 'N/A') ?></strong>
            </div>
          </aside>
        </div>
      <?php endif; ?>
    </section>
  </div>
</main>

<footer class="d-footer">
  <span>Derechos Reservados © <?= date('Y') ?> Instituto Tecnológico de Oaxaca</span>
  <span>Desarrollado con <img class="d-ico-img" src="<?= base_url('assets/img/heartSVG.svg') ?>" alt=""></span>
</footer>

<?php if (!$notFound): ?>
  <script src="<?= base_url('assets/js/seguimiento.js') ?>"></script>
<?php endif; ?>
</body>
</html>
