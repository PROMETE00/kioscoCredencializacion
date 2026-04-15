<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Credencialización | Captura de firma</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/public-turno.css') ?>">
</head>
<body>
<?php
  $turno  = $turno ?? [];
  $alumno = $alumno ?? [];
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
      <div class="pt-badge">
        Folio: <?= esc($turno['folio'] ?? 'N/A') ?>
      </div>
    </div>
  </div>
</header>

<main class="pt-main">
  <div class="pt-container">
    <section class="pt-shell">
      <div class="pt-grid pt-grid--firma">

        <!-- LEFT: Signature canvas -->
        <div class="pt-panel pt-panel--main">
          <div class="pt-kicker">Paso 2 de 2</div>
          <h2 class="pt-title">Captura tu firma</h2>
          <p class="pt-text">
            Firma con tu <strong>dedo</strong> en el recuadro de abajo. Esta firma será utilizada en tu credencial.
          </p>

          <!-- Canvas -->
          <div class="pt-firma-wrap" id="firmaWrap">
            <canvas id="firmaCanvas"></canvas>
            <div class="pt-firma-placeholder" id="firmaPlaceholder">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 19l7-7 3 3-7 7-3-3z"/>
                <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/>
                <path d="M2 2l7.586 7.586"/>
                <circle cx="11" cy="11" r="2"/>
              </svg>
              <span>Toca aquí y dibuja tu firma</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="pt-firma-actions">
            <button type="button" id="btnLimpiar" class="pt-btn pt-btn--secondary pt-btn--auto" disabled>
              Limpiar firma
            </button>
          </div>

          <div id="firmaStatus" class="pt-firma-status">
            Dibuja tu firma para continuar.
          </div>

          <!-- Form to submit -->
          <form id="firmaForm" method="post" action="<?= base_url('turno/firma') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="alumno_id" value="<?= esc((string) ($studentId ?? 0)) ?>">
            <input type="hidden" name="turno_id" value="<?= esc((string) ($ticketId ?? 0)) ?>">
            <input type="hidden" name="firma_png" id="firmaPngInput" value="">

            <div class="pt-firma-submit-group">
              <button type="submit" id="btnGuardar" class="pt-btn pt-btn--success" disabled>
                Guardar firma y continuar
              </button>
              <button type="submit" id="btnOmitir" class="pt-btn pt-btn--secondary pt-btn--auto pt-btn--skip">
                Omitir firma →
              </button>
            </div>
          </form>
        </div>

        <!-- RIGHT: Student info summary -->
        <aside class="pt-panel">
          <h3 class="pt-side-title">Datos del turno</h3>

          <div class="pt-info-card">
            <div class="pt-info-card__title">Alumno</div>
            <div class="pt-info-list">
              <div><span>Nombre</span><strong><?= esc($alumno['nombre'] ?? 'N/A') ?></strong></div>
              <div><span>No. control / ficha</span><strong><?= esc($alumno['identificador'] ?? 'N/A') ?></strong></div>
              <div><span>Carrera</span><strong><?= esc($alumno['carrera'] ?? 'N/A') ?></strong></div>
              <div><span>Campus</span><strong><?= esc($alumno['campus'] ?? 'N/A') ?></strong></div>
            </div>
          </div>

          <div class="pt-info-card">
            <div class="pt-info-card__title">Turno generado</div>
            <div class="pt-info-list">
              <div><span>Folio</span><strong><?= esc($turno['folio'] ?? 'N/A') ?></strong></div>
              <div><span>Estatus</span><strong><?= esc($turno['estatus'] ?? 'N/A') ?></strong></div>
              <div><span>Etapa</span><strong><?= esc($turno['etapa'] ?? 'N/A') ?></strong></div>
            </div>
          </div>

          <div class="pt-note" style="margin-top:12px;">
            Tu firma se guardará de forma segura y será utilizada únicamente para la credencial.
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

<style>
/* ── Signature capture (public kiosk) ────────────── */
.pt-grid--firma {
  grid-template-columns: minmax(0, 1.6fr) minmax(280px, .6fr);
}

.pt-firma-wrap {
  position: relative;
  margin-top: 16px;
  border-radius: 14px;
  border: 2px dashed var(--pt-input-border, #dbe4ef);
  background: #fff;
  overflow: hidden;
  touch-action: none;
  cursor: crosshair;
  transition: border-color .2s ease, box-shadow .2s ease;
}

.pt-firma-wrap.is-active {
  border-color: rgba(47, 109, 246, .45);
  box-shadow: 0 0 0 4px rgba(47, 109, 246, .08);
  border-style: solid;
}

.pt-firma-wrap.has-ink {
  border-color: rgba(22, 163, 74, .35);
  border-style: solid;
}

#firmaCanvas {
  display: block;
  width: 100%;
  height: 220px;
}

.pt-firma-placeholder {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #9ca3af;
  font-size: 14px;
  font-weight: 700;
  pointer-events: none;
  transition: opacity .2s ease;
}

.pt-firma-wrap.has-ink .pt-firma-placeholder {
  opacity: 0;
}

.pt-firma-actions {
  margin-top: 12px;
  display: flex;
  gap: 10px;
}

.pt-firma-status {
  margin-top: 10px;
  padding: 10px 12px;
  border-radius: 10px;
  background: #f8fbff;
  border: 1px solid var(--pt-border, #e6edf5);
  color: var(--pt-muted, #6b7280);
  font-size: 13px;
  font-weight: 700;
  min-height: 40px;
}

.pt-firma-submit-group {
  margin-top: 16px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.pt-firma-submit-group .pt-btn--success {
  flex: 1;
}

.pt-btn--skip {
  opacity: .7;
  font-size: 13px;
}

.pt-btn--skip:hover {
  opacity: 1;
}

.pt-info-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.pt-info-list > div {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid #f1f5f9;
}

.pt-info-list > div:last-child {
  border-bottom: 0;
}

.pt-info-list span {
  color: var(--pt-muted, #6b7280);
  font-size: 13px;
  font-weight: 700;
}

.pt-info-list strong {
  color: var(--pt-text-strong, #111827);
  font-size: 13px;
  font-weight: 800;
  text-align: right;
}

/* Touch devices: bigger canvas */
@media (pointer: coarse) {
  #firmaCanvas {
    height: 280px;
  }
}

@media (max-width: 960px) {
  .pt-grid--firma {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  #firmaCanvas {
    height: 200px;
  }
}
</style>

<script>
(() => {
  const canvas      = document.getElementById('firmaCanvas');
  const wrap        = document.getElementById('firmaWrap');
  const placeholder = document.getElementById('firmaPlaceholder');
  const btnLimpiar  = document.getElementById('btnLimpiar');
  const btnGuardar  = document.getElementById('btnGuardar');
  const btnOmitir   = document.getElementById('btnOmitir');
  const hiddenInput = document.getElementById('firmaPngInput');
  const statusEl    = document.getElementById('firmaStatus');
  const form        = document.getElementById('firmaForm');

  if (!canvas || !wrap || !btnLimpiar || !btnGuardar || !hiddenInput || !form) return;

  const ctx = canvas.getContext('2d');
  let drawing = false;
  let last    = null;
  let hasInk  = false;

  /* ── Resize canvas to match CSS size ── */
  function resize() {
    const dpr  = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    canvas.width  = Math.floor(rect.width * dpr);
    canvas.height = Math.floor(rect.height * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    resetPad();
  }

  function resetPad() {
    const rect = canvas.getBoundingClientRect();
    ctx.clearRect(0, 0, rect.width, rect.height);
    ctx.lineCap    = 'round';
    ctx.lineJoin   = 'round';
    ctx.strokeStyle = '#111827';
    ctx.lineWidth   = 2.4;
    hasInk = false;
    wrap.classList.remove('has-ink', 'is-active');
    btnGuardar.disabled = true;
    hiddenInput.value = '';
    statusEl.textContent = 'Dibuja tu firma para continuar.';
  }

  function pos(e) {
    const rect = canvas.getBoundingClientRect();
    const touch = e.touches ? e.touches[0] : e;
    return {
      x: (touch.clientX || touch.pageX) - rect.left,
      y: (touch.clientY || touch.pageY) - rect.top,
    };
  }

  /* ── Drawing events ── */
  function down(e) {
    drawing = true;
    last = pos(e);
    wrap.classList.add('is-active');
    e.preventDefault();
  }

  function move(e) {
    if (!drawing) return;
    const p = pos(e);
    ctx.beginPath();
    ctx.moveTo(last.x, last.y);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
    last = p;

    if (!hasInk) {
      hasInk = true;
      wrap.classList.add('has-ink');
      btnLimpiar.disabled = false;
      btnGuardar.disabled = false;
      statusEl.textContent = 'Firma detectada. Cuando termines, toca "Guardar firma y continuar".';
    }
    e.preventDefault();
  }

  function up(e) {
    drawing = false;
    last = null;
    wrap.classList.remove('is-active');
    e.preventDefault();
  }

  canvas.addEventListener('pointerdown', down);
  canvas.addEventListener('pointermove', move);
  window.addEventListener('pointerup', up);
  canvas.addEventListener('touchstart', down, { passive: false });
  canvas.addEventListener('touchmove', move, { passive: false });
  window.addEventListener('touchend', up);

  /* ── Buttons ── */
  btnLimpiar.addEventListener('click', () => {
    resetPad();
    btnLimpiar.disabled = true;
  });

  /* Before form submit, serialize canvas to hidden input */
  form.addEventListener('submit', (e) => {
    // If skipping (no ink), submit with empty firma_png
    if (e.submitter === btnOmitir) {
      hiddenInput.value = '';
      statusEl.textContent = 'Omitiendo firma...';
      return; // let form submit normally
    }

    // If saving, serialize
    if (hasInk) {
      hiddenInput.value = canvas.toDataURL('image/png');
      statusEl.textContent = 'Guardando firma...';
      btnGuardar.disabled = true;
      btnOmitir.disabled = true;
    } else {
      e.preventDefault();
      statusEl.textContent = 'Dibuja tu firma antes de guardar.';
    }
  });

  /* ── Init ── */
  window.addEventListener('resize', resize);
  resize();
  btnLimpiar.disabled = true;
})();
</script>

</body>
</html>
