<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Credencialización | Captura de huella</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/public-turno.css') ?>">
  <style>
    /* ── Stepper de Progreso ── */
    .pt-stepper { display: flex; justify-content: center; gap: 40px; margin-bottom: 25px; position: relative; }
    .pt-stepper::before { content: ""; position: absolute; top: 15px; left: 50%; transform: translateX(-50%); width: 70%; height: 2px; background: #e2e8f0; z-index: 1; }
    .pt-step { position: relative; z-index: 2; width: 32px; height: 32px; background: #fff; border: 2px solid #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; color: #64748b; }
    .pt-step span { position: absolute; top: 38px; left: 50%; transform: translateX(-50%); font-size: 11px; white-space: nowrap; color: #64748b; font-weight: 700; text-transform: uppercase; }
    .pt-step--active { border-color: #2F6DF6; background: #2F6DF6; color: #fff; }
    .pt-step--active span { color: #2F6DF6; }
    .pt-step--done { border-color: #16a34a; background: #16a34a; color: #fff; }
    .pt-step--done span { color: #16a34a; }

    .pt-firma-layout { max-width: 600px; margin: 0 auto; }
    .pt-info-card { background: #f8fbff; border: 1px solid #dbe4ef; border-radius: 12px; padding: 16px; margin-top: 15px; }
    .pt-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .pt-info-item dt { font-size: 11px; color: #6b7280; font-weight: 700; text-transform: uppercase; }
    .pt-info-item dd { font-size: 14px; font-weight: 700; color: #111827; margin: 0; }
    
    .pt-confirm { display: flex; align-items: flex-start; gap: 12px; margin-top: 20px; padding: 15px; border: 2px solid #dbe4ef; border-radius: 12px; cursor: pointer; background: #fff; transition: all 0.2s; }
    .pt-confirm.is-checked { border-color: #16a34a; background: #f0fdf4; }
    .pt-confirm__box { width: 22px; height: 22px; border: 2px solid #cbd5e1; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
    .pt-confirm.is-checked .pt-confirm__box { background: #16a34a; border-color: #16a34a; color: #fff; }

    /* ── Panel de Huella Persistente ── */
    .pt-huella-panel {
      position: relative;
      background: #fff;
      border: 2px dashed #cbd5e1;
      border-radius: 24px;
      padding: 30px;
      margin-top: 20px;
      text-align: center;
      transition: all 0.3s ease;
      overflow: hidden;
    }
    
    .pt-huella-panel--locked { opacity: 0.5; pointer-events: none; filter: grayscale(1); }
    .pt-huella-panel--active { border-color: #2F6DF6; border-style: solid; box-shadow: 0 10px 30px rgba(47, 109, 246, 0.1); }
    .pt-huella-panel--done { border-color: #16a34a; border-style: solid; background: #f0fdf4; }

    /* Óvalo de Huella */
    .huella-oval {
      width: 120px;
      height: 160px;
      margin: 0 auto 20px;
      border: 4px solid #f1f5f9;
      border-radius: 50% / 40%;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      background: #f8fafc;
      transition: all 0.3s;
    }
    
    .pt-huella-panel--active .huella-oval { border-color: #dbeafe; background: #fff; }
    .pt-huella-panel--done .huella-oval { border-color: #bbf7d0; background: #fff; }

    .huella-icon { width: 70px; height: 90px; color: #cbd5e1; transition: color 0.3s; position: relative; }
    .pt-huella-panel--active .huella-icon { color: #2F6DF6; }
    .pt-huella-panel--done .huella-icon { color: #16a34a; }

    /* Progreso de Escaneo */
    .scan-line {
      position: absolute;
      width: 100%;
      height: 3px;
      background: #2F6DF6;
      top: 0;
      left: 0;
      z-index: 5;
      box-shadow: 0 0 15px #2F6DF6;
      animation: scan 2.5s linear infinite;
      display: none;
    }
    .is-scanning .scan-line { display: block; }

    /* Barra de Progreso (Simulación de toques) */
    .pt-progress-bar {
      width: 180px;
      height: 8px;
      background: #f1f5f9;
      border-radius: 10px;
      margin: 10px auto;
      overflow: hidden;
      display: none;
    }
    .pt-progress-fill { width: 0%; height: 100%; background: #2F6DF6; transition: width 0.5s ease; }
    .is-scanning .pt-progress-bar { display: block; }

    @keyframes scan { 0% { top: 10%; } 50% { top: 85%; } 100% { top: 10%; } }

    .pt-lock-overlay {
      position: absolute;
      inset: 0;
      background: rgba(255,255,255,0.7);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 10;
      font-weight: 700;
      color: #64748b;
    }
    .pt-huella-panel:not(.pt-huella-panel--locked) .pt-lock-overlay { display: none; }
  </style>
</head>
<body>

<header class="pt-header">
  <div class="pt-header__inner">
    <div class="pt-header__left">
      <a href="<?= base_url('turno') ?>">
        <img class="d-logo" src="<?= base_url('assets/img/Instituto_Tecnologico_de_Oaxaca.png') ?>" alt="Logo">
      </a>
      <a href="<?= base_url('turno') ?>" class="pt-brand pt-brand--link">
        <h1 class="pt-brand__title">Instituto Tecnológico de Oaxaca</h1>
        <div class="pt-brand__subtitle">Autoservicio de credencialización</div>
      </a>
    </div>
  </div>
</header>

<main class="pt-main">
  <div class="pt-container">
    <section class="pt-shell">
      <div class="pt-firma-layout">

        <div class="pt-panel pt-panel--main">
          <!-- Stepper de Progreso -->
          <div class="pt-stepper">
            <div class="pt-step pt-step--done">1<span>Firma</span></div>
            <div class="pt-step pt-step--active">2<span>Huella</span></div>
            <div class="pt-step">3<span>Foto</span></div>
          </div>

          <h2 class="pt-title">Captura de Huella Digital</h2>
          <p class="pt-text">Confirma tus datos para desbloquear el sensor de huella HID.</p>

          <div class="pt-info-card">
            <h3 class="pt-info-card__title">Resumen de datos</h3>
            <dl class="pt-info-grid">
              <div class="pt-info-item"><dt>Nombre</dt><dd><?= esc($alumno['nombre'] ?? 'N/A') ?></dd></div>
              <div class="pt-info-item"><dt>No. Control</dt><dd><?= esc($alumno['identificador'] ?? 'N/A') ?></dd></div>
            </dl>
          </div>

          <label class="pt-confirm" id="confirmLabel">
            <input type="checkbox" id="confirmCheck">
            <span class="pt-confirm__box"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
            <span class="pt-confirm__text">Confirmo que mis datos son <strong>correctos</strong>.</span>
          </label>

          <!-- PANEL DE HUELLA PERSISTENTE -->
          <div class="pt-huella-panel pt-huella-panel--locked" id="huellaPanel">
            <div class="pt-lock-overlay" id="lockOverlay">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom:10px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <span>Confirma arriba para desbloquear</span>
            </div>

            <div class="huella-oval" id="huellaOval">
              <div class="scan-line"></div>
              <svg class="huella-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12c0-4.4 3.6-8 8-8s8 3.6 8 8M5 12c0-2.8 2.2-5 5-5s5 2.2 5 5M8 12c0-1.1.9-2 2-2s2 .9 2 2M22 12c0 5.5-4.5 10-10 10S2 17.5 2 12M15 12c0 1.7-1.3 3-3 3s-3-1.3-3-3"/>
              </svg>
            </div>

            <h3 class="pt-modal-title" id="statusTitle">Sensor Bloqueado</h3>
            <p id="huellaStatusText" class="pt-firma-status">Espera la activación.</p>
            
            <div class="pt-progress-bar"><div class="pt-progress-fill" id="progressFill"></div></div>

            <div style="display:flex; gap:10px; justify-content:center; margin-top:20px;">
              <button type="button" id="btnIniciarCaptura" class="pt-btn pt-btn--secondary" style="display:none; width:100%">Iniciar Escaneo de Huella</button>
            </div>
          </div>

          <form id="huellaForm" method="post" action="<?= base_url('huella/finalizar') ?>" style="margin-top:20px;">
            <?= csrf_field() ?>
            <input type="hidden" name="alumno_id" value="<?= esc((string) ($studentId ?? 0)) ?>">
            <input type="hidden" name="turno_id" value="<?= esc((string) ($ticketId ?? 0)) ?>">
            <button type="submit" id="btnFinalizar" class="pt-btn pt-btn--success" disabled style="width:100%">Finalizar y continuar a Foto</button>
          </form>
        </div>
      </div>
    </section>
  </div>
</main>

<script>
(() => {
  const confirmCheck = document.getElementById('confirmCheck');
  const huellaPanel  = document.getElementById('huellaPanel');
  const huellaStatus = document.getElementById('huellaStatusText');
  const statusTitle  = document.getElementById('statusTitle');
  const btnCapturar  = document.getElementById('btnIniciarCaptura');
  const progressFill = document.getElementById('progressFill');
  const btnFinalizar = document.getElementById('btnFinalizar');
  const alumnoId     = '<?= esc((string) ($studentId ?? 0)) ?>';

  // Helpers WebAuthn
  function b64ToBuf(str) {
    if (!str) return new Uint8Array(0).buffer;
    const bin = atob(str.replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from(bin, c => c.charCodeAt(0));
  }
  function bufToB64(buf) {
    return btoa(String.fromCharCode(...new Uint8Array(buf))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
  }
  function serializar(cred) {
    const obj = { id: cred.id, type: cred.type, rawId: bufToB64(cred.rawId), response: {} };
    if (cred.response.clientDataJSON) obj.response.clientDataJSON = bufToB64(cred.response.clientDataJSON);
    if (cred.response.attestationObject) obj.response.attestationObject = bufToB64(cred.response.attestationObject);
    if (cred.response.authenticatorData) obj.response.authenticatorData = bufToB64(cred.response.authenticatorData);
    if (cred.response.signature) obj.response.signature = bufToB64(cred.response.signature);
    return obj;
  }

  confirmCheck.addEventListener('change', () => {
    if (confirmCheck.checked) {
      document.getElementById('confirmLabel').classList.add('is-checked');
      huellaPanel.classList.remove('pt-huella-panel--locked');
      statusTitle.textContent = 'Sensor Desbloqueado';
      huellaStatus.textContent = 'Preparando conexión...';
      verificarStatus();
    } else {
      document.getElementById('confirmLabel').classList.remove('is-checked');
      huellaPanel.classList.add('pt-huella-panel--locked');
      btnCapturar.style.display = 'none';
    }
  });

  async function verificarStatus() {
    try {
      const res = await fetch('<?= base_url('huella/tiene-huella') ?>', {
        method: 'POST', body: new URLSearchParams({ alumno_id: alumnoId })
      });
      const data = await res.json();
      btnCapturar.style.display = 'block';
      btnCapturar.textContent = data.tiene_huella ? '👆 Verificar Identidad' : '📱 Registrar Nueva Huella';
      huellaStatus.textContent = 'Listo. Presiona el botón para usar el lector HID.';
    } catch (e) {
      huellaStatus.textContent = 'Error al conectar con el servidor.';
    }
  }

  btnCapturar.onclick = async () => {
    huellaPanel.classList.add('is-scanning', 'pt-huella-panel--active');
    btnCapturar.disabled = true;
    statusTitle.textContent = 'Capturando...';
    huellaStatus.textContent = 'Pon tu dedo en el lector HID. Levántalo y ponlo varias veces si es necesario.';
    
    // Simulación de progreso visual (WebAuthn no da progreso real, pero guiamos al usuario)
    let progress = 0;
    const interval = setInterval(() => {
      if(progress < 90) { progress += 1.5; progressFill.style.width = progress + '%'; }
    }, 100);

    try {
      const isRegister = btnCapturar.textContent.includes('Registrar');
      
      if (isRegister) {
        // REGISTRO
        const opt = await fetch('<?= base_url('huella/registro-challenge') ?>', {
          method: 'POST', body: new URLSearchParams({ alumno_id: alumnoId, nombre: '<?= esc($alumno['nombre']) ?>' })
        }).then(r => r.json());

        // FIX: Garantizar que pubKeyCredParams exista para evitar error de 'id' o 'map'
        if (!opt.pubKeyCredParams) {
          opt.pubKeyCredParams = [
            { type: 'public-key', alg: -7 },  // ES256
            { type: 'public-key', alg: -257 } // RS256
          ];
        }

        opt.challenge = b64ToBuf(opt.challenge);
        if (opt.user) opt.user.id = b64ToBuf(opt.user.id);
        
        if (opt.excludeCredentials) {
          opt.excludeCredentials = opt.excludeCredentials.map(c => ({
            ...c, id: b64ToBuf(c.id)
          }));
        }

        const cred = await navigator.credentials.create({ publicKey: opt });
        
        huellaStatus.textContent = 'Verificando con el servidor...';
        const ver = await fetch('<?= base_url('huella/registro-verificar') ?>', {
          method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(serializar(cred))
        }).then(r => r.json());

        if (!ver.success) throw new Error(ver.error || 'Error al guardar');
      } else {
        // VERIFICACIÓN
        const opt = await fetch('<?= base_url('huella/auth-challenge') ?>', {
          method: 'POST', body: new URLSearchParams({ alumno_id: alumnoId })
        }).then(r => r.json());

        opt.challenge = b64ToBuf(opt.challenge);
        if (opt.allowCredentials) {
          opt.allowCredentials = opt.allowCredentials.map(c => ({ ...c, id: b64ToBuf(c.id) }));
        }

        const assertion = await navigator.credentials.get({ publicKey: opt });
        const ver = await fetch('<?= base_url('huella/auth-verificar') ?>', {
          method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(serializar(assertion))
        }).then(r => r.json());

        if (!ver.verified) throw new Error('No se pudo verificar la huella.');
      }

      // ÉXITO FINAL
      clearInterval(interval);
      progressFill.style.width = '100%';
      huellaPanel.classList.remove('is-scanning');
      huellaPanel.classList.add('pt-huella-panel--done');
      statusTitle.textContent = '¡Captura Exitosa!';
      huellaStatus.textContent = 'Tu huella ha sido procesada correctamente.';
      btnCapturar.style.display = 'none';
      btnFinalizar.disabled = false;

    } catch (err) {
      clearInterval(interval);
      progressFill.style.width = '0%';
      huellaPanel.classList.remove('is-scanning', 'pt-huella-panel--active');
      btnCapturar.disabled = false;
      statusTitle.textContent = 'Error de Captura';
      huellaStatus.textContent = err.message || 'El proceso fue cancelado o falló.';
    }
  };
})();
</script>
</body>
</html>
