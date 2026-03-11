<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Credencialización | Estado general de turnos</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/public-turno.css') ?>">
</head>
<body>
<?php $vistaGeneral = $vistaGeneral ?? ['items' => [], 'turno_actual' => null, 'total_turnos' => 0, 'en_espera' => 0, 'actualizado_en' => null]; ?>

<header class="pt-header">
  <div class="pt-header__inner">
    <div class="pt-header__left">
      <a href="<?= base_url('turno') ?>" aria-label="Ir al inicio público">
        <img class="d-logo" src="<?= base_url('assets/img/Instituto_Tecnologico_de_Oaxaca.png') ?>" alt="Logo" onerror="this.style.display='none'">
      </a>
      <a href="<?= base_url('turno') ?>" class="pt-brand pt-brand--link" aria-label="Ir al inicio público">
        <h1 class="pt-brand__title">Instituto Tecnológico de Oaxaca</h1>
        <div class="pt-brand__subtitle">Estado general de turnos de credencialización</div>
      </a>
    </div>
  </div>
</header>

<main class="pt-main">
  <div class="pt-container">
    <section class="pt-shell">
      <div class="pt-panel pt-panel--main">
        <div class="pt-kicker">Vista general</div>
        <div class="pt-title-row">
          <div>
            <h2 class="pt-title">Turnos actuales</h2>
            <p class="pt-text">
              Esta pantalla muestra el panorama general de los turnos vigentes antes de que generes el tuyo.
            </p>
          </div>
          <a href="<?= base_url('turno') ?>" class="pt-btn pt-btn--secondary pt-btn--auto">Volver a generar turno</a>
        </div>

        <div class="pt-progress-banner">
          Última actualización: <strong><?= esc($vistaGeneral['actualizado_en'] ?? 'N/A') ?></strong>. La página se refresca cada 30 segundos.
        </div>

        <div class="pt-status-grid">
          <article class="pt-metric-card">
            <span class="pt-metric-card__label">Turnos vigentes</span>
            <strong class="pt-metric-card__value"><?= esc((string) ($vistaGeneral['total_turnos'] ?? 0)) ?></strong>
            <span class="pt-metric-card__hint">Todos los turnos públicos disponibles hoy</span>
          </article>

          <article class="pt-metric-card">
            <span class="pt-metric-card__label">En atención</span>
            <strong class="pt-metric-card__value pt-metric-card__value--sm">
              <?= esc($vistaGeneral['turno_actual']['folio'] ?? 'Sin turno actual') ?>
            </strong>
            <span class="pt-metric-card__hint">
              <?= esc($vistaGeneral['turno_actual']['etapa'] ?? 'Esperando atención') ?>
            </span>
          </article>

          <article class="pt-metric-card">
            <span class="pt-metric-card__label">En espera</span>
            <strong class="pt-metric-card__value"><?= esc((string) ($vistaGeneral['en_espera'] ?? 0)) ?></strong>
            <span class="pt-metric-card__hint">Turnos pendientes detrás del que se atiende ahora</span>
          </article>
        </div>

        <?php if (!empty($vistaGeneral['turno_actual'])): ?>
          <div class="pt-current-banner">
            <div class="pt-current-banner__title">Turno siendo atendido actualmente</div>
            <div class="pt-current-banner__row">
              <strong><?= esc($vistaGeneral['turno_actual']['folio']) ?></strong>
              <span><?= esc($vistaGeneral['turno_actual']['estatus']) ?> · <?= esc($vistaGeneral['turno_actual']['etapa']) ?></span>
            </div>
          </div>
        <?php endif; ?>

        <?php if (!empty($vistaGeneral['items'])): ?>
          <div class="pt-table-wrap">
            <table class="pt-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Folio</th>
                  <th>Estatus</th>
                  <th>Etapa</th>
                  <th>Turnos antes</th>
                  <th>Espera estimada</th>
                  <th>Progreso</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($vistaGeneral['items'] as $turno): ?>
                  <tr>
                    <td><?= esc((string) $turno['posicion_general']) ?></td>
                    <td>
                      <strong><?= esc($turno['folio']) ?></strong>
                      <?php if (!empty($turno['es_turno_actual'])): ?>
                        <div class="pt-table__hint">Atendiéndose ahora</div>
                      <?php endif; ?>
                    </td>
                    <td><span class="pt-badge <?= esc($turno['badge_class']) ?>"><?= esc($turno['estatus']) ?></span></td>
                    <td><?= esc($turno['etapa']) ?></td>
                    <td><?= esc((string) $turno['turnos_antes']) ?></td>
                    <td><?= esc($turno['eta_texto']) ?></td>
                    <td><?= esc($turno['mensaje_progreso']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="pt-note">
            En este momento no hay turnos vigentes registrados. Puedes volver a la pantalla principal y generar uno nuevo.
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>
</main>

<footer class="d-footer">
  <span>Derechos Reservados © <?= date('Y') ?> Instituto Tecnológico de Oaxaca</span>
  <span>Desarrollado con <img class="d-ico-img" src="<?= base_url('assets/img/heartSVG.svg') ?>" alt=""></span>
</footer>

<script>
  window.setTimeout(() => window.location.reload(), 30000);
</script>
</body>
</html>
