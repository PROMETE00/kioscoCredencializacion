<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Credencialización | Generar turno</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/public-turno.css') ?>">
</head>
<body class="">

<?php
  $consultaRealizada = $consultaRealizada ?? false;
  $alumnoEncontrado  = $alumnoEncontrado ?? false;
  $turnoExistente    = $turnoExistente ?? false;
  $alumno            = $alumno ?? null;
  $turnoActual       = $turnoActual ?? null;
  $vistaGeneral      = $vistaGeneral ?? ['total_turnos' => 0, 'en_espera' => 0, 'turno_actual' => null];
?>

<header class="pt-header">
  <div class="pt-header__inner">
    <div class="pt-header__left">
      <a href="<?= base_url('turno') ?>" aria-label="Ir al inicio público">
        <img class="d-logo" src="<?= base_url('assets/img/Instituto_Tecnologico_de_Oaxaca.png') ?>" alt="Logo"
            onerror="this.style.display='none'">
      </a>
      <a href="<?= base_url('turno') ?>" class="pt-brand pt-brand--link" aria-label="Ir al inicio público">
        <h1 class="pt-brand__title">Instituto Tecnológico de Oaxaca</h1>
        <div class="pt-brand__subtitle">
          Sistema de turnos para credencialización
        </div>
      </a>
    </div>
    <div class="pt-header__right">
      <a href="<?= base_url('admin/login') ?>" class="pt-btn pt-btn--secondary">
        Administración
      </a>
    </div>
  </div>
</header>

<main class="pt-main">
  <div class="pt-container">

    <section class="pt-shell">
      <div class="pt-grid">

        <div class="pt-panel pt-panel--main">
          <div class="pt-kicker">Turnero digital</div>

          <h2 class="pt-title">Generar turno</h2>

          <p class="pt-text">
            1.- Ingresa tu <strong>No. de control</strong> o <strong>No. de ficha</strong>.<br>
            2.- <strong>Verifica</strong> que tus datos estén <strong>correctos</strong>.<br>
            3.- Genera tu turno y <strong>guarda el QR o identificador</strong> para seguimiento.
          </p>

          <?php if (!empty($error)): ?>
            <div class="pt-error">
              <?= esc($error) ?>
            </div>
          <?php endif; ?>

          <!-- FORMULARIO DE BÚSQUEDA -->
          <form method="post" action="<?= base_url('turno/buscar') ?>" class="pt-form">
            <?= csrf_field() ?>

            <div class="pt-field">
              <label class="pt-label" for="identificador">
                No. de control / No. de ficha
              </label>

              <div class="pt-input-wrap">
                <input
                  class="pt-input"
                  id="identificador"
                  name="identificador"
                  type="text"
                  value="<?= esc(old('identificador') ?? ($alumno['identificador'] ?? '')) ?>"
                  placeholder="Ej. 21160727 o 12345"
                  inputmode="numeric"
                  autocomplete="off"
                  autocapitalize="off"
                  spellcheck="false"
                  required
                >
              </div>

              <div class="pt-help">
                Escribe únicamente números, sin espacios ni guiones.
              </div>
            </div>

            <div class="pt-actions">
              <button class="pt-btn" type="submit">
                Buscar alumno
              </button>
            </div>
          </form>

          <!-- RESULTADO DE LA CONSULTA -->
          <?php if ($consultaRealizada): ?>

            <?php if ($alumnoEncontrado && !empty($alumno)): ?>
              <div class="pt-result-block">

                <div class="pt-verified-banner">
                  <span class="pt-checkmark">✓</span>
                  <span>Información encontrada. Verifica que los datos sean correctos.</span>
                </div>

                <div class="pt-student-card">
                  <div class="pt-student-row">
                    <span class="pt-student-label">Número de control / ficha</span>
                    <strong class="pt-student-value">
                      <?= esc($alumno['identificador'] ?? 'N/A') ?>
                    </strong>
                  </div>

                  <div class="pt-student-row">
                    <span class="pt-student-label">Nombre</span>
                    <strong class="pt-student-value">
                      <?= esc($alumno['nombre'] ?? 'N/A') ?>
                    </strong>
                  </div>

                  <div class="pt-student-row">
                    <span class="pt-student-label">Carrera</span>
                    <strong class="pt-student-value">
                      <?= esc($alumno['carrera'] ?? 'N/A') ?>
                    </strong>
                  </div>

                  <div class="pt-student-row">
                    <span class="pt-student-label">Campus</span>
                    <strong class="pt-student-value">
                      <?= esc($alumno['campus'] ?? 'N/A') ?>
                    </strong>
                  </div>
                </div>

                <?php if ($turnoExistente): ?>
                  <div class="pt-existing-turn">
                    <div class="pt-existing-turn__title">
                      Ya existe un turno con estos datos
                    </div>
                    <div class="pt-existing-turn__text">
                      Identificador del turno:
                      <strong><?= esc($turnoActual['folio'] ?? $turnoActual['identificador'] ?? 'N/A') ?></strong>
                    </div>
                  </div>
                <?php else: ?>
                  <!-- FORMULARIO PARA GENERAR TURNO -->
                  <form method="post" action="<?= base_url('turno/generar') ?>" class="pt-generate-form">
                    <?= csrf_field() ?>

                    <input type="hidden" name="identificador" value="<?= esc($alumno['identificador'] ?? '') ?>">
                    <input type="hidden" name="nombre" value="<?= esc($alumno['nombre'] ?? '') ?>">
                    <input type="hidden" name="carrera" value="<?= esc($alumno['carrera'] ?? '') ?>">
                    <input type="hidden" name="campus" value="<?= esc($alumno['campus'] ?? '') ?>">

                    <div class="pt-actions">
                      <button class="pt-btn pt-btn--success" type="submit">
                        Generar turno
                      </button>
                    </div>
                  </form>
                <?php endif; ?>

              </div>

            <?php else: ?>
              <div class="pt-warning">
                No se encontró ningún alumno con ese número de control o ficha.
              </div>
            <?php endif; ?>

          <?php endif; ?>

          <div class="pt-note">
            * Tu turno expira al final del día. Guarda el QR o el enlace que se mostrará
            al finalizar el registro.
          </div>
        </div>

        <aside class="pt-panel">
          <h3 class="pt-side-title">Información importante</h3>

          <div class="pt-info-card">
            <div class="pt-info-card__title">¿Qué obtendrás?</div>
            <ul class="pt-list">
              <li>Un folio de atención.</li>
              <li>Un código QR para seguimiento.</li>
              <li>Consulta del estado del trámite desde tu celular.</li>
            </ul>
          </div>

          <div class="pt-info-card">
            <div class="pt-info-card__title">Antes de comenzar</div>
            <ul class="pt-list">
              <li>Verifica que tu número sea correcto.</li>
              <li>Ten tu celular disponible para guardar el QR.</li>
              <li>Consulta tu turno cuando seas llamado al módulo.</li>
            </ul>
          </div>
            <div class="pt-info-card">
            <div class="pt-callout__content">
              <div class="pt-info-card__title">Consulta primero la pantalla general de turnos</div>
              <div class="pt-callout__text">
                <ul class="pt-list">
                <li>Turnos vigentes: <strong><?= esc((string) ($vistaGeneral['total_turnos'] ?? 0)) ?></strong>.</li>
                <li>En espera: <strong><?= esc((string) ($vistaGeneral['en_espera'] ?? 0)) ?></strong>.</li>
                <?php if (!empty($vistaGeneral['turno_actual']['folio'])): ?>
                <li>  En atención ahora: <strong><?= esc($vistaGeneral['turno_actual']['folio']) ?></strong>.</li>
                <?php endif; ?>
                </ul>
              </div>
            </div>
            <div class="pt-callout__actions">
              <a href="<?= base_url('turnos/general') ?>" class="pt-btn pt-btn--secondary">
                Ver turnos
              </a>
            </div>
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
