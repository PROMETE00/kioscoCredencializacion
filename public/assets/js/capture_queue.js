(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const cfgEl = document.getElementById('cfg');
    const SAVE_URL  = cfgEl?.dataset.saveUrl || '';
    const CSRF_NAME = cfgEl?.dataset.csrfName || '';
    const CSRF_HASH = cfgEl?.dataset.csrfHash || '';

    const data = JSON.parse(document.getElementById('pageData')?.textContent || '{}');
    let queue = Array.isArray(data.queue) ? data.queue : [];
    let current = data.current || null;

    // UI: datos persona
    const studentIdEl = document.getElementById('studentId');
    const turnoIdEl = document.getElementById('turnoId');
    const pNombre  = document.getElementById('alNombre');
    const pControl = document.getElementById('alControl');
    const pCarrera = document.getElementById('alCarrera');
    const pSemestre= document.getElementById('alSemestre');
    const pEstatus = document.getElementById('alEstatus');

    // UI cola
    const qList   = document.getElementById('queueList');
    const qSearch = document.getElementById('qSearch');
    const qCount  = document.getElementById('qCount');

    // UI cámara existente
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

    const setStatus = (msg) => { if (statusEl) statusEl.textContent = msg; };
    const setLoading = (on, text='Cargando…') => {
      if (!loading) return;
      if (loadingText) loadingText.textContent = text;
      loading.classList.toggle('is-on', !!on);
    };
    const showToast = (msg) => {
      if (!toast) return;
      toast.textContent = msg;
      toast.classList.add('is-on');
      clearTimeout(showToast._t);
      showToast._t = setTimeout(() => toast.classList.remove('is-on'), 2200);
    };

    // ---- Render datos alumno ----
    function renderCurrent(c) {
      current = c || null;
      studentIdEl.value = c?.student_id || c?.id || '';
      if (turnoIdEl) turnoIdEl.value = c?.ticket_id || '';
      pNombre.textContent   = c?.name || '—';
      pControl.textContent  = c?.control_number || '—';
      pCarrera.textContent  = c?.career || '—';
      pSemestre.textContent = c?.semester || '—';
      pEstatus.textContent  = c?.status || '—';
      if (saveInfo) saveInfo.textContent = '—';
      if (preview) preview.removeAttribute('src');
      btnSave.disabled = true;
      lastFrameBlob = null;
      lastFrameFilename = null;
    }

    // ---- Render cola ----
    function renderQueue(filter='') {
      const f = (filter || '').toLowerCase().trim();
      const items = !f ? queue : queue.filter(x =>
        String(x.name || '').toLowerCase().includes(f) ||
        String(x.control_number || '').toLowerCase().includes(f)
      );

      qList.innerHTML = '';
      items.forEach(x => {
        const li = document.createElement('li');
          li.className = 'd-qItem' + ((current && Number(x.ticket_id) === Number(current.ticket_id)) ? ' is-active' : '');
          li.dataset.id = x.student_id;
          li.dataset.turnoId = x.ticket_id;

        li.innerHTML = `
          <div class="d-qName">${escapeHtml(x.name || '—')}</div>
          <div class="d-qMeta">
            <span>#${escapeHtml(String(x.control_number || '—'))}</span>
            <span>${escapeHtml(String(x.career || '—'))}</span>
            <span>Sem ${escapeHtml(String(x.semester || '—'))}</span>
          </div>
        `;

        li.addEventListener('click', () => {
          renderCurrent(x);
          // marcar activo
          document.querySelectorAll('.d-qItem').forEach(el => el.classList.remove('is-active'));
          li.classList.add('is-active');
          setStatus('Alumno cargado. Captura y guarda la foto.');
          showToast('Alumno seleccionado');
        });

        qList.appendChild(li);
      });

      if (qCount) qCount.textContent = String(queue.length);
    }

    function escapeHtml(s){
      return String(s).replace(/[&<>"']/g, (m) =>
        ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])
      );
    }

    // ---- Cámara (igual que tu versión robusta) ----
     let started = false;
     let stream = null;
     let selfieSegmentation = null;
     let mediaPipeLoader = null;
     let whiteBgState = 'idle';
     let segmentationInFlight = false;
     let lastSegmentationAt = 0;
     let hasSegmentedFrame = false;
     let lastFrameBlob = null;
     let lastFrameFilename = null;
     let rafId = null;

     const SEGMENTATION_INTERVAL_MS = 180; // Un poco más de tiempo entre frames de segmentación para no saturar CPU
     const SEGMENTATION_MAX_WIDTH = 480; // Reducir resolución de entrada para segmentación (más rápido)
     const SEGMENTATION_LOAD_TIMEOUT_MS = 15000;

     const segmentationInputCanvas = document.createElement('canvas');
     const segmentationInputCtx = segmentationInputCanvas.getContext('2d', { willReadFrequently: true, alpha: false });
     const segmentationOutputCanvas = document.createElement('canvas');
     const segmentationOutputCtx = segmentationOutputCanvas.getContext('2d', { alpha: true });

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

    const fitSegmentationCanvas = () => {
      const sourceWidth = video.videoWidth || 1280;
      const sourceHeight = video.videoHeight || 720;
      const ratio = sourceHeight / sourceWidth;
      const width = Math.min(sourceWidth, SEGMENTATION_MAX_WIDTH);
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
      segmentationOutputCtx.globalCompositeOperation = 'source-in';
      segmentationOutputCtx.drawImage(results.image, 0, 0, w, h);
      segmentationOutputCtx.globalCompositeOperation = 'destination-over';
      segmentationOutputCtx.fillStyle = '#ffffff';
      segmentationOutputCtx.fillRect(0, 0, w, h);
      segmentationOutputCtx.globalCompositeOperation = 'source-over';
      segmentationOutputCtx.restore();

      hasSegmentedFrame = true;
    };

    const drawSegmentedFrame = () => {
      if (!hasSegmentedFrame) {
        drawPlain();
        return;
      }

      fitCanvasToVideo();
      ctx.drawImage(segmentationOutputCanvas, 0, 0, canvas.width, canvas.height);
    };

    const loadScriptOnce = (src) => new Promise((resolve, reject) => {
      const existing = document.querySelector(`script[src="${src}"]`);
      if (existing) {
        if (existing.dataset.loaded === 'true') return resolve();
        existing.addEventListener('load', () => resolve(), { once: true });
        existing.addEventListener('error', () => reject(new Error(`No se pudo cargar ${src}`)), { once: true });
        return;
      }

      const script = document.createElement('script');
      script.src = src;
      script.async = true;
      script.addEventListener('load', () => {
        script.dataset.loaded = 'true';
        resolve();
      }, { once: true });
      script.addEventListener('error', () => reject(new Error(`No se pudo cargar ${src}`)), { once: true });
      document.head.appendChild(script);
    });

    const withTimeout = (promise, ms, label) => Promise.race([
      promise,
      new Promise((_, reject) => setTimeout(() => reject(new Error(`${label} tardó demasiado en cargar`)), ms)),
    ]);

    const warmupWhiteBackground = () => {
      if (whiteBgState === 'ready' || whiteBgState === 'loading') return;

      whiteBgState = 'loading';

      if (!mediaPipeLoader) {
        mediaPipeLoader = withTimeout((async () => {
          await loadScriptOnce('https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js');
          await loadScriptOnce('https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation/selfie_segmentation.js');
          return true;
        })(), SEGMENTATION_LOAD_TIMEOUT_MS, 'El fondo blanco');
      }

      mediaPipeLoader.then(() => {
        if (selfieSegmentation || typeof window.SelfieSegmentation === 'undefined') {
          whiteBgState = selfieSegmentation ? 'ready' : 'error';
          return;
        }

        fitSegmentationCanvas();
        selfieSegmentation = new window.SelfieSegmentation({
          locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation/${file}`
        });
        selfieSegmentation.setOptions({ modelSelection: 0 });
        selfieSegmentation.onResults((results) => {
          segmentationInFlight = false;
          drawWithWhiteBackground(results);
        });
        whiteBgState = 'ready';

        if (chkWhiteBg?.checked) {
          setStatus('Fondo blanco activado.');
          showToast('Fondo blanco listo');
        }
      }).catch((error) => {
        console.warn(error);
        whiteBgState = 'error';
        segmentationInFlight = false;
        hasSegmentedFrame = false;
        if (chkWhiteBg) chkWhiteBg.checked = false;
        setStatus('No se pudo activar el fondo blanco. La cámara seguirá normal.');
        showToast('Fondo blanco no disponible');
      });
    };

    const requestSegmentationFrame = () => {
      if (!selfieSegmentation || segmentationInFlight) return;

      fitSegmentationCanvas();
      segmentationInputCtx.drawImage(video, 0, 0, segmentationInputCanvas.width, segmentationInputCanvas.height);
      segmentationInFlight = true;
      lastSegmentationAt = performance.now();

      selfieSegmentation.send({ image: segmentationInputCanvas }).catch((error) => {
        console.warn(error);
        segmentationInFlight = false;
        hasSegmentedFrame = false;
      });
    };

    const loop = () => {
      if (!started) return;

      if (chkWhiteBg?.checked) {
        drawSegmentedFrame();

        if (whiteBgState === 'ready') {
          const now = performance.now();
          if (!segmentationInFlight && (now - lastSegmentationAt) >= SEGMENTATION_INTERVAL_MS) {
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
      if (started) return;

      setLoading(true, 'Solicitando permisos…');
      try {
        stream = await navigator.mediaDevices.getUserMedia({
          video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' },
          audio: false
        });

        video.srcObject = stream;
        await video.play();

        started = true;
        btnStart.disabled = true;
        btnShot.disabled = false;
        warmupWhiteBackground();

        setLoading(false);
        setStatus('Cámara lista. Selecciona alumno (cola) y captura foto.');
        showToast('Cámara iniciada');
        loop();
      } catch (err) {
        console.error(err);
        setLoading(false);
        setStatus('No se pudo iniciar cámara. Revisa permisos (F12).');
      }
    };

    chkWhiteBg?.addEventListener('change', () => {
      if (!started) return;

      if (!chkWhiteBg.checked) {
        hasSegmentedFrame = false;
        setStatus('Fondo blanco desactivado.');
        return;
      }

      if (whiteBgState === 'ready') {
        setStatus('Fondo blanco activado.');
        requestSegmentationFrame();
        return;
      }

      if (whiteBgState === 'error') {
        chkWhiteBg.checked = false;
        setStatus('No se pudo activar el fondo blanco.');
        showToast('Fondo blanco no disponible');
        return;
      }

      setStatus('Activando fondo blanco…');
      warmupWhiteBackground();
    });

    const takePhoto = () => {
      if (!started) return;
      if (!studentIdEl.value) {
        showToast('Selecciona un alumno de la cola');
        setStatus('Selecciona un alumno de la cola antes de capturar.');
        return;
      }
      canvas.toBlob((blob) => {
        if (!blob) {
          setStatus('No se pudo capturar la foto.');
          showToast('Error al capturar');
          return;
        }

        if (lastFrameFilename) {
          URL.revokeObjectURL(lastFrameFilename);
        }

        lastFrameBlob = blob;
        lastFrameFilename = URL.createObjectURL(blob);

        if (preview) preview.src = lastFrameFilename;
        btnSave.disabled = false;
        setStatus('Foto capturada. Presiona Guardar.');
        showToast('Foto capturada');
      }, 'image/jpeg', 0.85);
    };

    const save = async () => {
      const sid = studentIdEl.value;
      const ticketId = turnoIdEl?.value || '';
      if (!sid || !ticketId || !lastFrameBlob) return;

      btnSave.disabled = true;
      setLoading(true, 'Guardando…');
      setStatus('Guardando fotografía…');

      try {
        const fd = new FormData();
        fd.append('image', await blobToDataUrl(lastFrameBlob));
        fd.append('student_id', sid);
        fd.append('turno_id', ticketId);
        if (CSRF_NAME && CSRF_HASH) fd.append(CSRF_NAME, CSRF_HASH);

        const res = await fetch(SAVE_URL, { method: 'POST', body: fd });
        const json = await res.json().catch(() => null);

        setLoading(false);

        if (!res.ok || !json || !json.ok) {
          btnSave.disabled = false;
          setStatus('Error al guardar. Revisa logs/CSRF.');
          showToast('Error al guardar');
          console.error(json);
          return;
        }

        if (saveInfo) saveInfo.textContent = `Archivo: ${json.filename}`;
        showToast('Guardado ✅');

        // quitar de cola al alumno guardado
        queue = Array.isArray(json.queue) ? json.queue : queue.filter(x => Number(x.ticket_id) !== Number(ticketId));

        const next = json.current || (queue.length ? queue[0] : null);
        renderCurrent(next);

        renderQueue(qSearch?.value || '');

        setStatus(next ? 'Guardado. Siguiente alumno cargado.' : 'Guardado. Cola vacía.');
      } catch (err) {
        console.error(err);
        setLoading(false);
        btnSave.disabled = false;
        setStatus('Error inesperado al guardar.');
      }
    };

    const blobToDataUrl = (blob) => new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onloadend = () => typeof reader.result === 'string' ? resolve(reader.result) : reject(new Error('No se pudo leer la imagen.'));
      reader.onerror = () => reject(new Error('No se pudo leer la imagen.'));
      reader.readAsDataURL(blob);
    });

    // eventos
    btnStart.addEventListener('click', startCamera);
    btnShot.addEventListener('click', takePhoto);
    btnSave.addEventListener('click', save);

    qSearch?.addEventListener('input', (e) => renderQueue(e.target.value));

    // init UI
    renderCurrent(current || (queue.length ? queue[0] : null));
    renderQueue('');
    setStatus('Listo. Inicia cámara, selecciona alumno de cola y captura.');
  });
})();
