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
          Autoservicio de credencialización
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
          <div class="pt-kicker">Autoservicio</div>

          <h2 class="pt-title">Iniciar proceso</h2>

          <p class="pt-text">
            1.- Ingresa tu <strong>No. de control</strong> o <strong>No. de ficha</strong>.<br>
            2.- <strong>Verifica</strong> que tus datos estén <strong>correctos</strong>.<br>
            3.- Continúa para registrar y <strong>capturar tu firma</strong> táctil.
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
                  placeholder="Ej. 21160727"
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
          <?php
            require_once __DIR__ . '/keyboardComponent.php';
            echo renderKeyboard('#identificador');
          ?>  
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
                      Ya iniciaste un proceso hoy
                    </div>
                    <div class="pt-existing-turn__text">
                      Por favor presiona el botón para continuar y capturar tu firma.
                    </div>
                  </div>
                  <!-- FORMULARIO PARA CONTINUAR -->
                  <form method="post" action="<?= base_url('turno/generar') ?>" class="pt-generate-form">
                    <?= csrf_field() ?>

                    <input type="hidden" name="identificador" value="<?= esc($alumno['identificador'] ?? '') ?>">
                    <input type="hidden" name="nombre" value="<?= esc($alumno['nombre'] ?? '') ?>">
                    <input type="hidden" name="carrera" value="<?= esc($alumno['carrera'] ?? '') ?>">
                    <input type="hidden" name="campus" value="<?= esc($alumno['campus'] ?? '') ?>">

                    <div class="pt-actions">
                      <button class="pt-btn pt-btn--success" type="submit">
                        Continuar
                      </button>
                    </div>
                  </form>
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
                        Continuar
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
            * El acceso a tu registro expira al final del día. Debes completar la captura el día de hoy.
          </div>
        </div>

        <aside class="pt-panel">
          <h3 class="pt-side-title">Información importante</h3>

          <div class="pt-info-card">
            <div class="pt-info-card__title">¿Qué realizarás aquí?</div>
            <ul class="pt-list">
              <li>Identificación como alumno del Instituto.</li>
              <li>Validación de datos en sistema.</li>
              <li>Captura de firma para tu credencial oficial.</li>
            </ul>
          </div>

          <div class="pt-info-card">
            <div class="pt-info-card__title">Antes de comenzar</div>
            <ul class="pt-list">
              <li>Verifica que tu número de control o ficha sea correcto.</li>
              <li>Una vez verificados los datos, procede a capturar tu firma.</li>
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
