<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Credencialización | Captura de foto</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/public-turno.css') ?>">
</head>
<body class="">

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
          <div class="pt-kicker">Paso final</div>
          <h2 class="pt-title">Captura tu fotografía</h2>
          <p class="pt-text">
            Por favor, ponte frente a la cámara, asegúrate de tener buena iluminación y trata de centrar tu rostro para la credencial.
          </p>

          <div class="pt-firma-info-row">
            <div class="pt-info-card" style="width: 100%;">
              <div class="pt-info-card__title">Datos del alumno</div>
              <div class="pt-info-list" style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="flex-direction: column; align-items:flex-start; gap: 4px; border: none; padding:0;"><span>Nombre</span><strong><?= esc($alumno['nombre'] ?? 'N/A') ?></strong></div>
                <div style="flex-direction: column; align-items:flex-start; gap: 4px; border: none; padding:0;"><span>No. control / ficha</span><strong><?= esc($alumno['identificador'] ?? 'N/A') ?></strong></div>
              </div>
            </div>
          </div>

          <!-- Camera Viewer -->
          <div style="margin-top:20px;">
            <div id="cameraWrap" style="position: relative; width: 100%; aspect-ratio: 4/3; background: #e2e8f0; border-radius: 14px; overflow: hidden; display: flex; align-items:center; justify-content:center; flex-direction:column; border: 2px dashed #cbd5e1;">
                <video id="video" playsinline autoplay muted style="position: absolute; width:100%; height:100%; object-fit: cover; display: none; transform: scaleX(-1);"></video>
                <canvas id="canvas" style="position: absolute; width:100%; height:100%; object-fit: cover; display: none; transform: scaleX(-1);"></canvas>
                <img id="preview" style="position: absolute; width:100%; height:100%; object-fit: cover; display: none; transform: scaleX(-1);" />
                
                <div id="loadingInfo" style="z-index:10; font-weight:bold; color:#475569;">Iniciando cámara... <div class="d-spinner" style="border: 2px solid #ccc; border-top-color: #2F6DF6; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; display:inline-block; vertical-align:middle; margin-left:8px;"></div></div>

                <div id="overlayCirc" style="position: absolute; inset:0; pointer-events:none; border: 40px solid rgba(255,255,255,0.7); border-radius: 20%; box-sizing: border-box; display:none;"></div>
            </div>

            <!-- Controls -->
            <div style="display:flex; justify-content: space-between; margin-top:15px; align-items:center;">
                <label style="display:flex; align-items:center; gap:8px; font-weight:bold; color:#475569; cursor:pointer; user-select: none;">
                    <input type="checkbox" id="chkWhiteBg" style="width:18px; height:18px;"> Fondo blanco mágico
                </label>
                <div style="display:flex; gap: 10px;">
                    <button class="pt-btn pt-btn--secondary" id="btnRetake" style="display:none;" type="button">Volver a tomar</button>
                    <button class="pt-btn" id="btnShot" disabled type="button">Tomar Foto</button>
                </div>
            </div>
            
            <div id="status" class="pt-firma-status" style="margin-top:15px;">Autoriza a tu navegador accionar la cámara para continuar.</div>

          </div>

          <!-- Form to submit -->
          <form id="fotoForm" method="post" action="<?= base_url('turno/foto') ?>" style="margin-top: 20px;">
            <?= csrf_field() ?>
            <input type="hidden" name="alumno_id" value="<?= esc((string) ($studentId ?? 0)) ?>">
            <input type="hidden" name="turno_id" value="<?= esc((string) ($ticketId ?? 0)) ?>">
            <input type="hidden" name="foto_png" id="fotoPngInput" value="">
            
            <div class="pt-firma-submit-group">
                <button type="submit" id="btnGuardar" class="pt-btn pt-btn--success" disabled style="width:100%;">
                    Terminar y guardar credencial
                </button>
            </div>
          </form>

        </div>
      </div>
    </section>
  </div>
</main>

<style>
@keyframes spin { 100% { transform: rotate(360deg); } }
</style>

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
    const overlayCirc = document.getElementById('overlayCirc');
    const statusEl = document.getElementById('status');
    const fotoPngInput = document.getElementById('fotoPngInput');
    const frm = document.getElementById('fotoForm');

    let stream = null;
    let started = false;
    let selfieSegmentation = null;
    let mediaPipeLoader = null;
    let whiteBgState = 'idle';
    let segmentationInFlight = false;
    let lastSegmentationAt = 0;
    let hasSegmentedFrame = false;
    let rafId = null;
    let capturedDataUrl = null;

    const setStatus = (msg) => { statusEl.textContent = msg; };

    const loadScriptOnce = (src) => new Promise((resolve, reject) => {
      const existing = document.querySelector(`script[src="${src}"]`);
      if (existing) {
        if (existing.dataset.loaded === 'true') return resolve();
        existing.addEventListener('load', () => resolve(), { once: true });
        existing.addEventListener('error', () => reject(new Error(`No se pudo cargar ${src}`)), { once: true });
        return;
      }
      const script = document.createElement('script');
      script.src = src; script.async = true;
      script.addEventListener('load', () => { script.dataset.loaded = 'true'; resolve(); }, { once: true });
      script.addEventListener('error', () => reject(new Error(`No se pudo cargar ${src}`)), { once: true });
      document.head.appendChild(script);
    });

    const warmupWhiteBackground = () => {
      if (whiteBgState === 'ready' || whiteBgState === 'loading') return;
      whiteBgState = 'loading';
      
      if (!mediaPipeLoader) {
          mediaPipeLoader = Promise.all([
            loadScriptOnce('https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js'),
            loadScriptOnce('https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation/selfie_segmentation.js')
          ]);
      }

      mediaPipeLoader.then(() => {
        if (selfieSegmentation) { whiteBgState = 'ready'; return; }
        
        selfieSegmentation = new window.SelfieSegmentation({
          locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation/${file}`
        });
        selfieSegmentation.setOptions({ modelSelection: 0 });
        selfieSegmentation.onResults((results) => {
          segmentationInFlight = false;
          drawWithWhiteBackground(results);
        });
        whiteBgState = 'ready';
        if(chkWhiteBg.checked) setStatus('Fondo mágico listo.');
      }).catch(err => {
        console.warn(err);
        whiteBgState = 'error';
        chkWhiteBg.checked = false;
        setStatus('No se pudo cargar la IA del fondo. La cámara será normal.');
      });
    };

    const SEGMENTATION_MAX_WIDTH = 480;
    const segmentationInputCanvas = document.createElement('canvas');
    const segmentationInputCtx = segmentationInputCanvas.getContext('2d', { willReadFrequently: true, alpha: false });
    const segmentationOutputCanvas = document.createElement('canvas');
    const segmentationOutputCtx = segmentationOutputCanvas.getContext('2d', { alpha: true });

    const fitSegmentationCanvas = () => {
      const w = video.videoWidth || 1280; const h = video.videoHeight || 720;
      const ratio = h / w;
      const width = Math.min(w, SEGMENTATION_MAX_WIDTH);
      const height = Math.max(1, Math.round(width * ratio));
      if (segmentationInputCanvas.width !== width) segmentationInputCanvas.width = width;
      if (segmentationInputCanvas.height !== height) segmentationInputCanvas.height = height;
      if (segmentationOutputCanvas.width !== width) segmentationOutputCanvas.width = width;
      if (segmentationOutputCanvas.height !== height) segmentationOutputCanvas.height = height;
    };

    const drawWithWhiteBackground = (results) => {
      const w = segmentationOutputCanvas.width;
      const h = segmentationOutputCanvas.height;
      segmentationOutputCtx.save();
      segmentationOutputCtx.clearRect(0, 0, w, h);
      segmentationOutputCtx.drawImage(results.segmentationMask, 0, 0, w, h);
      // Invertir máscara (queremos a la persona)
      segmentationOutputCtx.globalCompositeOperation = 'source-in';
      segmentationOutputCtx.drawImage(results.image, 0, 0, w, h);
      // Poner fondo blanco detrás
      segmentationOutputCtx.globalCompositeOperation = 'destination-over';
      segmentationOutputCtx.fillStyle = '#ffffff';
      segmentationOutputCtx.fillRect(0, 0, w, h);
      segmentationOutputCtx.globalCompositeOperation = 'source-over';
      segmentationOutputCtx.restore();
      hasSegmentedFrame = true;
    };

    const fitCanvasToVideo = () => {
      const w = video.videoWidth || 1280; const h = video.videoHeight || 720;
      if (canvas.width !== w) canvas.width = w;
      if (canvas.height !== h) canvas.height = h;
    };

    const requestSegmentationFrame = () => {
      if (!selfieSegmentation || segmentationInFlight) return;
      fitSegmentationCanvas();
      
      // Fix for mirror effect when sending to mediapipe
      segmentationInputCtx.save();
      segmentationInputCtx.scale(-1, 1);
      segmentationInputCtx.translate(-segmentationInputCanvas.width, 0);
      segmentationInputCtx.drawImage(video, 0, 0, segmentationInputCanvas.width, segmentationInputCanvas.height);
      segmentationInputCtx.restore();

      segmentationInFlight = true;
      lastSegmentationAt = performance.now();
      selfieSegmentation.send({ image: segmentationInputCanvas }).catch((error) => {
        segmentationInFlight = false;
        hasSegmentedFrame = false;
      });
    };

    const drawSegmentedFrame = () => {
      if (!hasSegmentedFrame) { fitCanvasToVideo(); ctx.save(); ctx.scale(-1,1); ctx.translate(-canvas.width,0); ctx.drawImage(video, 0, 0, canvas.width, canvas.height); ctx.restore(); return; }
      fitCanvasToVideo();
      // Segmentation output is already horizontally flipped by us above
      ctx.drawImage(segmentationOutputCanvas, 0, 0, canvas.width, canvas.height);
    };

    const drawPlain = () => {
      fitCanvasToVideo();
      ctx.save();
      ctx.scale(-1, 1);
      ctx.translate(-canvas.width, 0);
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      ctx.restore();
    };

    const loop = () => {
      if (!started) return;
      if (chkWhiteBg.checked) {
        drawSegmentedFrame();
        if (whiteBgState === 'ready') {
          if (!segmentationInFlight && (performance.now() - lastSegmentationAt) >= 150) {
            requestSegmentationFrame();
          }
        } else if (whiteBgState === 'idle') {
          warmupWhiteBackground();
        }
      } else {
        drawPlain();
      }
      rafId = requestAnimationFrame(loop);
    };

    const startCamera = async () => {
      try {
        stream = await navigator.mediaDevices.getUserMedia({
          video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' },
          audio: false
        });
        video.srcObject = stream;
        await video.play();
        started = true;
        btnShot.disabled = false;
        loadingInfo.style.display = 'none';
        
        if (chkWhiteBg.checked) {
            video.style.display = 'none';
            canvas.style.display = 'block';
            loop();
        } else {
            video.style.display = 'block';
            canvas.style.display = 'none';
        }
        overlayCirc.style.display = 'block';
        setStatus('Cámara encendida. Sonríe a la cámara.');
      } catch (e) {
        setStatus('Error al encender cámara. ¿Diste permisos?');
        console.error(e);
      }
    };

    chkWhiteBg.addEventListener('change', () => {
        if (!started) return;
        if (chkWhiteBg.checked) {
           video.style.display = 'none';
           canvas.style.display = 'block';
           if (!rafId) loop();
        } else {
           canvas.style.display = 'none';
           video.style.display = 'block';
           if (rafId) cancelAnimationFrame(rafId);
           rafId = null;
        }
    });

    btnShot.addEventListener('click', () => {
        video.style.display = 'none';
        canvas.style.display = 'none';
        overlayCirc.style.display = 'none';
        preview.style.display = 'block';

        if (!chkWhiteBg.checked) {
            drawPlain();
        }
        
        // We flip horizontally on the plain canvas, so we output it as is
        capturedDataUrl = canvas.toDataURL('image/jpeg', 0.85);
        preview.src = capturedDataUrl;
        fotoPngInput.value = capturedDataUrl;
        
        btnShot.style.display = 'none';
        btnRetake.style.display = 'block';
        btnGuardar.disabled = false;
        chkWhiteBg.disabled = true;

        setStatus('Visualiza tu foto. Si te gusta da clic en "Terminar".');
    });

    btnRetake.addEventListener('click', () => {
        preview.style.display = 'none';
        overlayCirc.style.display = 'block';
        if (chkWhiteBg.checked) {
            canvas.style.display = 'block';
        } else {
            video.style.display = 'block';
        }
        
        capturedDataUrl = null;
        fotoPngInput.value = '';
        btnShot.style.display = 'block';
        btnRetake.style.display = 'none';
        btnGuardar.disabled = true;
        chkWhiteBg.disabled = false;
        setStatus('Modo captura. Sonríe a la cámara.');
    });

    frm.addEventListener('submit', (e) => {
        if (!capturedDataUrl) {
            e.preventDefault();
            setStatus('Debes tomar la foto primero.');
            return;
        }
        btnGuardar.disabled = true;
        btnGuardar.textContent = 'Guardando datos...';
        setStatus('Procesando, por favor no recargues la página.');
    });

    startCamera();
})();
</script>
</body>
</html>
