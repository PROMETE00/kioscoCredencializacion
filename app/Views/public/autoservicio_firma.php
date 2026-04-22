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
          Autoservicio de credencialización
        </div>
      </a>
    </div>
  </div>
</header>

<main class="pt-main">
  <div class="pt-container">
    <section class="pt-shell">
      <div class="pt-firma-layout">

        <div class="pt-panel pt-panel--main">
          <div class="pt-kicker">Paso 2 de 2</div>
          <h2 class="pt-title">Verifica tus datos y captura tu firma</h2>
          <p class="pt-text">
            Revisa que tu información sea correcta, confirma tus datos y firma con tu <strong>dedo</strong> en el recuadro.
          </p>

          <!-- ── Student info ── -->
          <div class="pt-info-card">
            <h3 class="pt-info-card__title">Datos del alumno</h3>
            <dl class="pt-info-grid">
              <div class="pt-info-item">
                <dt>Nombre</dt>
                <dd><?= esc($alumno['nombre'] ?? 'N/A') ?></dd>
              </div>
              <div class="pt-info-item">
                <dt>No. control / ficha</dt>
                <dd><?= esc($alumno['identificador'] ?? 'N/A') ?></dd>
              </div>
              <div class="pt-info-item">
                <dt>Carrera</dt>
                <dd><?= esc($alumno['carrera'] ?? 'N/A') ?></dd>
              </div>
              <div class="pt-info-item">
                <dt>Campus</dt>
                <dd><?= esc($alumno['campus'] ?? 'N/A') ?></dd>
              </div>
            </dl>
          </div>

          <!-- ── Mandatory confirmation ── -->
          <label class="pt-confirm" id="confirmLabel">
            <input type="checkbox" id="confirmCheck">
            <span class="pt-confirm__box">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </span>
            <span class="pt-confirm__text">
              Confirmo que mis datos son <strong>correctos</strong> y acepto que mi firma será utilizada en la credencial.
            </span>
          </label>

          <!-- ── Canvas (disabled until confirmed) ── -->
          <div class="pt-firma-wrap pt-firma-wrap--locked" id="firmaWrap">
            <canvas id="firmaCanvas"></canvas>
            <div class="pt-firma-placeholder" id="firmaPlaceholder">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 19l7-7 3 3-7 7-3-3z"/>
                <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/>
                <path d="M2 2l7.586 7.586"/>
                <circle cx="11" cy="11" r="2"/>
              </svg>
              <span id="placeholderText">Primero confirma tus datos para poder firmar</span>
            </div>
            <div class="pt-firma-lock-overlay" id="firmaLock">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <span>Confirma tus datos arriba para desbloquear</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="pt-firma-actions">
            <button type="button" id="btnLimpiar" class="pt-btn pt-btn--secondary pt-btn--auto" disabled>
              Limpiar firma
            </button>
          </div>

          <div id="firmaStatus" class="pt-firma-status">
            Confirma tus datos y dibuja tu firma para continuar.
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
            </div>
          </form>

          <div class="pt-note" style="margin-top:14px;">
            Tu firma se guardará de forma segura y será utilizada únicamente para la credencial.
          </div>
        </div>

      </div>
    </section>
  </div>
</main>

<footer class="d-footer">
  <span>Derechos Reservados © <?= date('Y') ?> Instituto Tecnológico de Oaxaca</span>
  <span>Desarrollado con <img class="d-ico-img" src="<?= base_url('assets/img/heartSVG.svg') ?>" alt=""></span>
</footer>

<style>
/* ── Signature capture layout (single column) ──── */
.pt-firma-layout {
  max-width: 720px;
  margin: 0 auto;
}

/* ── Info Card Layout ── */
.pt-info-card {
  background: #f8fbff;
  border: 1px solid var(--pt-input-border, #dbe4ef);
  border-radius: 12px;
  padding: 16px 20px;
  margin-top: 18px;
}

.pt-info-card__title {
  margin: 0 0 14px 0;
  font-size: 15px;
  font-weight: 700;
  color: #1f2937;
}

.pt-info-grid {
  display: grid;
  /* El uso de auto-fit y minmax calcula columnas automáticamente */
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px 24px;
  margin: 0;
}

.pt-info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.pt-info-item dt {
  color: var(--pt-muted, #6b7280);
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 700;
}

.pt-info-item dd {
  margin: 0;
  color: var(--pt-text-strong, #111827);
  font-size: 14px;
  font-weight: 700;
  word-break: break-word;
}

/* ── Confirmation checkbox ── */
.pt-confirm {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-top: 18px;
  padding: 14px 16px;
  border-radius: 12px;
  border: 2px solid var(--pt-input-border, #dbe4ef);
  background: #f8fbff;
  cursor: pointer;
  transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
  user-select: none;
  -webkit-user-select: none;
}

.pt-confirm:hover {
  border-color: rgba(47, 109, 246, .35);
  background: #f0f6ff;
}

.pt-confirm.is-checked {
  border-color: rgba(22, 163, 74, .4);
  background: rgba(22, 163, 74, .05);
  box-shadow: 0 0 0 3px rgba(22, 163, 74, .08);
}

.pt-confirm input[type="checkbox"] {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.pt-confirm__box {
  flex-shrink: 0;
  width: 26px;
  height: 26px;
  border-radius: 8px;
  border: 2px solid #cbd5e1;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background .15s ease, border-color .15s ease, transform .1s ease;
  margin-top: 1px;
}

.pt-confirm__box svg {
  opacity: 0;
  transform: scale(.5);
  transition: opacity .15s ease, transform .15s ease;
  color: #fff;
}

.pt-confirm.is-checked .pt-confirm__box {
  background: #16a34a;
  border-color: #16a34a;
  transform: scale(1.05);
}

.pt-confirm.is-checked .pt-confirm__box svg {
  opacity: 1;
  transform: scale(1);
}

.pt-confirm__text {
  color: #374151;
  font-size: 14px;
  font-weight: 600;
  line-height: 1.45;
}

/* ── Canvas wrapper ── */
.pt-firma-wrap {
  position: relative;
  margin-top: 16px;
  border-radius: 14px;
  border: 2px dashed var(--pt-input-border, #dbe4ef);
  background: #fff;
  overflow: hidden;
  touch-action: none;
  cursor: crosshair;
  transition: border-color .2s ease, box-shadow .2s ease, opacity .2s ease;
}

.pt-firma-wrap--locked {
  cursor: not-allowed;
  opacity: .55;
  pointer-events: none;
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

/* Lock overlay */
.pt-firma-lock-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #94a3b8;
  font-size: 13px;
  font-weight: 700;
  background: rgba(248, 250, 252, .85);
  backdrop-filter: blur(2px);
  -webkit-backdrop-filter: blur(2px);
  transition: opacity .25s ease;
  pointer-events: none;
}

.pt-firma-wrap--unlocked .pt-firma-lock-overlay {
  opacity: 0;
  pointer-events: none;
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

/* ── Responsive ── */
@media (pointer: coarse) {
  #firmaCanvas {
    height: 280px;
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
  const lockOverlay = document.getElementById('firmaLock');
  const placeholder = document.getElementById('firmaPlaceholder');
  const placeholderText = document.getElementById('placeholderText');
  const btnLimpiar  = document.getElementById('btnLimpiar');
  const btnGuardar  = document.getElementById('btnGuardar');
  const hiddenInput = document.getElementById('firmaPngInput');
  const statusEl    = document.getElementById('firmaStatus');
  const form        = document.getElementById('firmaForm');
  const confirmCheck = document.getElementById('confirmCheck');
  const confirmLabel = document.getElementById('confirmLabel');

  if (!canvas || !wrap || !btnLimpiar || !btnGuardar || !hiddenInput || !form || !confirmCheck) return;

  const ctx = canvas.getContext('2d');
  let drawing  = false;
  let last     = null;
  let hasInk   = false;
  let unlocked = false;

  /* ── Resize canvas ── */
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
    if (unlocked) {
      statusEl.textContent = 'Dibuja tu firma para continuar.';
    }
  }

  function pos(e) {
    const rect = canvas.getBoundingClientRect();
    const touch = e.touches ? e.touches[0] : e;
    return {
      x: (touch.clientX || touch.pageX) - rect.left,
      y: (touch.clientY || touch.pageY) - rect.top,
    };
  }

  /* ── Confirmation checkbox ── */
  confirmCheck.addEventListener('change', () => {
    const checked = confirmCheck.checked;
    confirmLabel.classList.toggle('is-checked', checked);

    if (checked) {
      unlocked = true;
      wrap.classList.remove('pt-firma-wrap--locked');
      wrap.classList.add('pt-firma-wrap--unlocked');
      wrap.style.opacity = '';
      wrap.style.pointerEvents = '';
      placeholderText.textContent = 'Toca aquí y dibuja tu firma';
      statusEl.textContent = 'Datos confirmados ✓ — Dibuja tu firma para continuar.';
    } else {
      unlocked = false;
      wrap.classList.add('pt-firma-wrap--locked');
      wrap.classList.remove('pt-firma-wrap--unlocked');
      resetPad();
      btnLimpiar.disabled = true;
      statusEl.textContent = 'Confirma tus datos para poder firmar.';
    }
  });

  /* ── Drawing events ── */
  function down(e) {
    if (!unlocked) return;
    drawing = true;
    last = pos(e);
    wrap.classList.add('is-active');
    e.preventDefault();
  }

  function move(e) {
    if (!drawing || !unlocked) return;
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
    if (drawing){
      if (e.cancelable) e.preventDefault();
    }
    drawing = false;
    last = null;
    wrap.classList.remove('is-active');
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

  /* ── Form submit ── */
  form.addEventListener('submit', (e) => {
    if (!unlocked) {
      e.preventDefault();
      statusEl.textContent = 'Debes confirmar que tus datos son correctos antes de continuar.';
      confirmLabel.style.animation = 'shake .4s ease';
      setTimeout(() => confirmLabel.style.animation = '', 400);
      return;
    }

    if (hasInk) {
      hiddenInput.value = canvas.toDataURL('image/png');
      statusEl.textContent = 'Guardando firma...';
      btnGuardar.disabled = true;
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
