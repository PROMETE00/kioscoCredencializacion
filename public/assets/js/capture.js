(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const cfgEl = document.getElementById('cfg');

    const SAVE_URL  = cfgEl?.dataset.saveUrl || '';
    const CSRF_NAME = cfgEl?.dataset.csrfName || '';
    const CSRF_HASH = cfgEl?.dataset.csrfHash || '';

    const video  = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const ctx    = canvas?.getContext('2d', { willReadFrequently: true });

    const btnStart = document.getElementById('btnStart');
    const btnShot  = document.getElementById('btnShot');
    const btnSave  = document.getElementById('btnSave');

    const chkWhiteBg = document.getElementById('chkWhiteBg');
    const preview = document.getElementById('preview');
    const statusEl = document.getElementById('status');
    const saveInfo = document.getElementById('saveInfo');
    const toast = document.getElementById('toast');

    const loading = document.getElementById('loading');
    const loadingText = document.getElementById('loadingText');

    if (!video || !canvas || !ctx || !btnStart || !btnShot || !btnSave) {
      console.error('Faltan elementos HTML. Revisa IDs: video, canvas, btnStart, btnShot, btnSave');
      if (statusEl) statusEl.textContent = 'Error: faltan elementos HTML (IDs). Revisa consola (F12).';
      return;
    }

    let started = false;
    let stream = null;
    let selfieSegmentation = null;

    let lastFrameDataUrl = null;
    let rafId = null;

    const setStatus = (msg) => { if (statusEl) statusEl.textContent = msg; };

    const showToast = (msg) => {
      if (!toast) return;
      toast.textContent = msg;
      toast.classList.add('is-on');
      clearTimeout(showToast._t);
      showToast._t = setTimeout(() => toast.classList.remove('is-on'), 2200);
    };

    const setLoading = (on, text = 'Cargando…') => {
      if (!loading) return;
      if (loadingText) loadingText.textContent = text;
      loading.classList.toggle('is-on', !!on);
    };

    const fitCanvasToVideo = () => {
      const w = video.videoWidth || 1280;
      const h = video.videoHeight || 720;
      if (canvas.width !== w) canvas.width = w;
      if (canvas.height !== h) canvas.height = h;
    };

    const drawPlain = () => {
      fitCanvasToVideo();
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    };

    const drawWithWhiteBackground = (results) => {
      fitCanvasToVideo();
      const w = canvas.width, h = canvas.height;

      ctx.save();
      ctx.clearRect(0, 0, w, h);

      ctx.drawImage(results.segmentationMask, 0, 0, w, h);

      ctx.globalCompositeOperation = 'source-in';
      ctx.drawImage(results.image, 0, 0, w, h);

      ctx.globalCompositeOperation = 'destination-over';
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, w, h);

      ctx.globalCompositeOperation = 'source-over';
      ctx.restore();
    };

    const loop = async () => {
      if (!started) return;

      if (selfieSegmentation) {
        try {
          await selfieSegmentation.send({ image: video });
        } catch (e) {
          drawPlain();
        }
      } else {
        drawPlain();
      }

      rafId = requestAnimationFrame(loop);
    };

    const initSegmentationIfAvailable = async () => {
      if (typeof window.SelfieSegmentation === 'undefined') {
        console.warn('SelfieSegmentation no está definido (CDN no cargó o sin internet).');
        setStatus('Cámara lista. (Sin fondo blanco: MediaPipe no cargó).');
        return;
      }

      selfieSegmentation = new window.SelfieSegmentation({
        locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation/${file}`
      });

      selfieSegmentation.setOptions({ modelSelection: 1 });

      selfieSegmentation.onResults((results) => {
        if (chkWhiteBg?.checked) drawWithWhiteBackground(results);
        else {
          fitCanvasToVideo();
          ctx.drawImage(results.image, 0, 0, canvas.width, canvas.height);
        }
      });

      setStatus('Cámara lista con fondo blanco.');
    };

    const startCamera = async () => {
      if (started) return;

      setLoading(true, 'Solicitando permisos de cámara…');
      setStatus('Solicitando permisos de cámara…');

      try {
        stream = await navigator.mediaDevices.getUserMedia({
          video: {
            width: { ideal: 1280 },
            height: { ideal: 720 },
            facingMode: 'user'
          },
          audio: false
        });

        video.srcObject = stream;
        await video.play();

        started = true;

        btnStart.disabled = true;
        btnShot.disabled = false;
        btnSave.disabled = true;

        // Limpiar preview al iniciar
        lastFrameDataUrl = null;
        preview?.removeAttribute('src');
        if (saveInfo) saveInfo.textContent = '—';

        setLoading(false);
        showToast('Cámara iniciada');

        await initSegmentationIfAvailable();
        loop();

        setStatus('Cámara lista. Presiona “Tomar foto”. (Puedes presionarlo de nuevo para re-tomar).');

      } catch (err) {
        console.error(err);
        setLoading(false);
        setStatus('No se pudo iniciar la cámara. Revisa permisos del navegador (F12 → Console).');
        showToast('Permiso de cámara denegado');
      }
    };

    // Botón único: siempre vuelve a capturar (reemplaza la anterior)
    const takePhoto = () => {
      // si no ha iniciado, no hace nada
      if (!started) return;

      // Captura lo que está en el canvas (con o sin fondo blanco)
      lastFrameDataUrl = canvas.toDataURL('image/jpeg', 0.92);

      if (preview) preview.src = lastFrameDataUrl;
      if (saveInfo) saveInfo.textContent = '—';

      btnSave.disabled = false;

      setStatus('Foto capturada.');
      showToast('Foto capturada');
    };

    const save = async () => {
      if (!lastFrameDataUrl) return;
      if (!SAVE_URL) {
        setStatus('Error: no se encontró SAVE_URL. Revisa el <script id="cfg" data-save-url="...">');
        return;
      }

      btnSave.disabled = true;
      setLoading(true, 'Guardando…');
      setStatus('Guardando fotografía…');

      try {
        const fd = new FormData();
        fd.append('image', lastFrameDataUrl);

        if (CSRF_NAME && CSRF_HASH) {
          fd.append(CSRF_NAME, CSRF_HASH);
        }

        const res = await fetch(SAVE_URL, { method: 'POST', body: fd });
        const json = await res.json().catch(() => null);

        setLoading(false);

        if (!res.ok || !json || !json.ok) {
          console.error(json);
          btnSave.disabled = false;
          setStatus('Error al guardar. Revisa CSRF/permisos/logs.');
          showToast('Error al guardar');
          return;
        }

        if (saveInfo) saveInfo.textContent = `Archivo: ${json.filename}`;
        setStatus('Guardado.');
        showToast('Guardado');

        // opcional: si quieres permitir “guardar otra vez” sin re-tomar, comenta esta línea:
        // btnSave.disabled = false;

      } catch (err) {
        console.error(err);
        setLoading(false);
        btnSave.disabled = false;
        setStatus('Error inesperado al guardar. Revisa consola (F12).');
        showToast('Error inesperado');
      }
    };

    btnStart.addEventListener('click', startCamera);
    btnShot.addEventListener('click', takePhoto);
    btnSave.addEventListener('click', save);

    setStatus('Listo. Presiona “Iniciar cámara”.');
  });
})();