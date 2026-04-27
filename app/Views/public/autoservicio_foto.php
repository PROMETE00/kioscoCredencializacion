<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Credencialización | Captura de foto</title>
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

    /* ── Mejoras UI/UX Cámara ── */
    .pt-camera-container {
      max-width: 500px; /* Tamaño más contenido */
      margin: 0 auto;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.05);
      padding: 24px;
      border: 1px solid #eef2f7;
    }
    
    .pt-camera-viewport {
      position: relative;
      width: 100%;
      aspect-ratio: 1 / 1; /* Formato cuadrado para credencial */
      background: #f1f5f9;
      border-radius: 100%; /* Círculo visual */
      overflow: hidden;
      border: 4px solid #fff;
      box-shadow: 0 0 0 2px #2F6DF6;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    #video, #canvas, #preview {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transform: scaleX(-1);
    }

    .pt-camera-overlay {
      position: absolute;
      inset: 0;
      border: 20px solid rgba(255,255,255,0.3);
      border-radius: 50%;
      pointer-events: none;
    }

    .pt-camera-controls {
      display: flex;
      flex-direction: column;
      gap: 16px;
      align-items: center;
    }

    .pt-btn-capture {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background: #2F6DF6;
      border: 6px solid #e0e7ff;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
      box-shadow: 0 4px 12px rgba(47, 109, 246, 0.3);
    }
    
    .pt-btn-capture:hover {
      transform: scale(1.05);
      background: #1d4ed8;
    }

    .pt-btn-capture:disabled {
      background: #cbd5e1;
      border-color: #f1f5f9;
      cursor: not-allowed;
    }

    .pt-camera-status {
      font-size: 13px;
      font-weight: 600;
      color: #64748b;
      text-align: center;
    }

    .pt-magic-switch {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 13px;
      font-weight: 700;
      color: #334155;
      background: #f8fafc;
      padding: 8px 16px;
      border-radius: 20px;
      cursor: pointer;
    }

    @keyframes spin { 100% { transform: rotate(360deg); } }
    .d-spinner {
      border: 3px solid #f3f3f3;
      border-top: 3px solid #2F6DF6;
      border-radius: 50%;
      width: 24px;
      height: 24px;
      animation: spin 1s linear infinite;
    }
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
    <div class="pt-camera-container">
      <!-- Stepper de Progreso -->
      <div class="pt-stepper">
        <div class="pt-step pt-step--done">1<span>Firma</span></div>
        <div class="pt-step pt-step--done">2<span>Huella</span></div>
        <div class="pt-step pt-step--active">3<span>Foto</span></div>
      </div>

      <div style="text-align:center; margin-bottom: 20px;">
        <h2 class="pt-title" style="font-size: 20px;">Captura tu fotografía</h2>
        <p class="pt-text" style="font-size: 13px;">Centra tu rostro en el círculo y asegúrate de tener buena luz.</p>
      </div>

      <!-- Viewport de Cámara -->
      <div class="pt-camera-viewport" id="cameraWrap">
          <video id="video" playsinline autoplay muted></video>
          <canvas id="canvas" style="display:none;"></canvas>
          <img id="preview" style="display:none;" />
          
          <div id="loadingInfo" style="z-index:10; position:absolute; display:flex; flex-direction:column; align-items:center; gap:10px;">
            <div class="d-spinner"></div>
            <span style="font-size:12px; font-weight:700; color:#64748b;">Iniciando cámara...</span>
          </div>

          <div class="pt-camera-overlay"></div>
      </div>

      <!-- Controles -->
      <div class="pt-camera-controls">
          <label class="pt-magic-switch">
              <input type="checkbox" id="chkWhiteBg"> ✨ Fondo blanco mágico
          </label>

          <div id="status" class="pt-camera-status">Espera a que la cámara esté lista</div>

          <div style="display:flex; align-items:center; gap:20px;">
              <button class="pt-btn pt-btn--secondary" id="btnRetake" style="display:none;" type="button">Repetir</button>
              
              <button class="pt-btn-capture" id="btnShot" disabled type="button" title="Tomar Foto">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
              </button>

              <form id="fotoForm" method="post" action="<?= base_url('turno/foto') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="alumno_id" value="<?= esc((string) ($studentId ?? 0)) ?>">
                <input type="hidden" name="turno_id" value="<?= esc((string) ($ticketId ?? 0)) ?>">
                <input type="hidden" name="foto_png" id="fotoPngInput" value="">
                <button type="submit" id="btnGuardar" class="pt-btn pt-btn--success" style="display:none;">Listo</button>
              </form>
          </div>
      </div>
    </div>
  </div>
</main>

<script>
(() => {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    
    const btnShot = document.getElementById('btnShot');
    const btnRetake = document.getElementById('btnRetake');
    const btnGuardar = document.getElementById('btnGuardar');
    const chkWhiteBg = document.getElementById('chkWhiteBg');
    
    const preview = document.getElementById('preview');
    const loadingInfo = document.getElementById('loadingInfo');
    const statusEl = document.getElementById('status');
    const fotoPngInput = document.getElementById('fotoPngInput');
    const frm = document.getElementById('fotoForm');

    let stream = null;
    let started = false;
    let selfieSegmentation = null;
    let whiteBgState = 'idle';

    const setStatus = (msg) => { statusEl.textContent = msg; };

    const startCamera = async () => {
      try {
        stream = await navigator.mediaDevices.getUserMedia({
          video: { width: 640, height: 640, facingMode: 'user' },
          audio: false
        });
        video.srcObject = stream;
        await video.play();
        started = true;
        btnShot.disabled = false;
        loadingInfo.style.display = 'none';
        setStatus('¡Listo! Captura tu mejor ángulo.');
      } catch (e) {
        setStatus('Error: No se pudo acceder a la cámara.');
      }
    };

    btnShot.addEventListener('click', () => {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        ctx.save();
        ctx.scale(-1, 1);
        ctx.translate(-canvas.width, 0);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        ctx.restore();

        const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
        preview.src = dataUrl;
        fotoPngInput.value = dataUrl;
        
        video.style.display = 'none';
        preview.style.display = 'block';
        btnShot.style.display = 'none';
        btnRetake.style.display = 'block';
        btnGuardar.style.display = 'block';
        setStatus('¿Te gusta esta foto?');
    });

    btnRetake.addEventListener('click', () => {
        preview.style.display = 'none';
        video.style.display = 'block';
        btnShot.style.display = 'flex';
        btnRetake.style.display = 'none';
        btnGuardar.style.display = 'none';
        setStatus('Captura tu mejor ángulo.');
    });

    startCamera();
})();
</script>
</body>
</html>
