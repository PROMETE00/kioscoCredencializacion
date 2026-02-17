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
    const pNombre  = document.getElementById('pNombre');
    const pControl = document.getElementById('pControl');
    const pCarrera = document.getElementById('pCarrera');
    const pSemestre= document.getElementById('pSemestre');
    const pEstatus = document.getElementById('pEstatus');

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
      studentIdEl.value = c?.id || '';
      pNombre.textContent   = c?.nombre || '—';
      pControl.textContent  = c?.no_control || '—';
      pCarrera.textContent  = c?.carrera || '—';
      pSemestre.textContent = c?.semestre || '—';
      pEstatus.textContent  = c?.estatus || '—';
      if (saveInfo) saveInfo.textContent = '—';
      if (preview) preview.removeAttribute('src');
      btnSave.disabled = true;
    }

    // ---- Render cola ----
    function renderQueue(filter='') {
      const f = (filter || '').toLowerCase().trim();
      const items = !f ? queue : queue.filter(x =>
        String(x.nombre || '').toLowerCase().includes(f) ||
        String(x.no_control || '').toLowerCase().includes(f)
      );

      qList.innerHTML = '';
      items.forEach(x => {
        const li = document.createElement('li');
        li.className = 'd-qItem' + ((current && x.id === current.id) ? ' is-active' : '');
        li.dataset.id = x.id;

        li.innerHTML = `
          <div class="d-qName">${escapeHtml(x.nombre || '—')}</div>
          <div class="d-qMeta">
            <span>#${escapeHtml(String(x.no_control || '—'))}</span>
            <span>${escapeHtml(String(x.carrera || '—'))}</span>
            <span>Sem ${escapeHtml(String(x.semestre || '—'))}</span>
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
    let lastFrameDataUrl = null;
    let rafId = null;

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

    const initSegmentationIfAvailable = async () => {
      if (typeof window.SelfieSegmentation === 'undefined') {
        console.warn('SelfieSegmentation no está definido.');
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
    };

    const loop = async () => {
      if (!started) return;

      if (selfieSegmentation) {
        try { await selfieSegmentation.send({ image: video }); }
        catch (e) { drawPlain(); }
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

        await initSegmentationIfAvailable();
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

    const takePhoto = () => {
      if (!started) return;
      if (!studentIdEl.value) {
        showToast('Selecciona un alumno de la cola');
        setStatus('Selecciona un alumno de la cola antes de capturar.');
        return;
      }
      lastFrameDataUrl = canvas.toDataURL('image/jpeg', 0.92);
      if (preview) preview.src = lastFrameDataUrl;
      btnSave.disabled = false;
      setStatus('Foto capturada. Presiona Guardar.');
      showToast('Foto capturada');
    };

    const save = async () => {
      const sid = studentIdEl.value;
      if (!sid || !lastFrameDataUrl) return;

      btnSave.disabled = true;
      setLoading(true, 'Guardando…');
      setStatus('Guardando fotografía…');

      try {
        const fd = new FormData();
        fd.append('image', lastFrameDataUrl);
        fd.append('student_id', sid);
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
        const savedId = parseInt(sid, 10);
        queue = queue.filter(x => x.id !== savedId);

        // cargar siguiente automáticamente (primero de la cola)
        const next = queue.length ? queue[0] : null;
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