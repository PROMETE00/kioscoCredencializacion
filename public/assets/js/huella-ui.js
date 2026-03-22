(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const configElement = document.getElementById('cfg');
    const stateElement = document.getElementById('pageData');

    if (!configElement || !stateElement) return;

    const queueUrl = configElement.dataset.colaUrl || '';
    const saveUrl = configElement.dataset.guardarUrl || '';
    const studentUrl = configElement.dataset.alumnoUrl || '';
    const csrfName = configElement.dataset.csrfName || '';
    let csrfHash = configElement.dataset.csrfHash || '';

    const initialState = JSON.parse(stateElement.textContent || '{}');
    let queue = Array.isArray(initialState.queue) ? initialState.queue : [];
    let currentStudent = initialState.current || null;
    let currentScan = null;
    let currentQuality = 0;
    let selectedTurnId = Number(currentStudent?.turno_id || 0);

    const queueList = document.getElementById('queueList');
    const queueSearch = document.getElementById('qSearch');
    const queueCount = document.getElementById('qCount');
    const previewImage = document.getElementById('fpPreview');
    const previewEmpty = document.getElementById('fpPreviewEmpty');
    const qualityText = document.getElementById('fpQualityText');
    const qualityFill = document.getElementById('fpQualityFill');
    const statusText = document.getElementById('fpStatus');
    const saveInfo = document.getElementById('saveInfo');
    const startButton = document.getElementById('btnStart');
    const retryButton = document.getElementById('btnRetry');
    const saveButton = document.getElementById('btnSave');
    const studentIdInput = document.getElementById('studentId');
    const turnIdInput = document.getElementById('turnoId');
    const studentName = document.getElementById('alNombre');
    const studentControl = document.getElementById('alControl');
    const studentCareer = document.getElementById('alCarrera');
    const studentSemester = document.getElementById('alSemestre');
    const studentStatus = document.getElementById('alEstatus');
    const toast = document.getElementById('toast');
    const stepSelect = document.getElementById('stSelect');
    const stepScan = document.getElementById('stScan');
    const stepSave = document.getElementById('stSave');

    if (!queueList || !queueSearch || !queueCount || !previewImage || !previewEmpty || !qualityText || !qualityFill || !statusText || !saveInfo || !startButton || !retryButton || !saveButton || !studentIdInput || !turnIdInput || !studentName || !studentControl || !studentCareer || !studentSemester || !studentStatus || !toast || !stepSelect || !stepScan || !stepSave) {
      return;
    }

    const showToast = (message) => {
      toast.textContent = message;
      toast.classList.add('is-on');
      clearTimeout(showToast.timer);
      showToast.timer = setTimeout(() => toast.classList.remove('is-on'), 2200);
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => (
      {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]
    ));

    const setStep = (active) => {
      [stepSelect, stepScan, stepSave].forEach((element) => element.classList.remove('is-active'));
      if (active === 'scan') stepScan.classList.add('is-active');
      else if (active === 'save') stepSave.classList.add('is-active');
      else stepSelect.classList.add('is-active');
    };

    const setStatus = (message) => {
      statusText.textContent = message;
    };

    const setQuality = (value) => {
      currentQuality = Math.max(0, Math.min(100, Number(value) || 0));
      qualityText.textContent = `${currentQuality}%`;
      qualityFill.style.width = `${currentQuality}%`;
    };

    const clearScan = () => {
      currentScan = null;
      previewImage.removeAttribute('src');
      previewImage.style.display = 'none';
      previewEmpty.style.display = '';
      saveButton.disabled = true;
      retryButton.disabled = !turnIdInput.value;
      saveInfo.textContent = '—';
      setQuality(0);
      setStep(turnIdInput.value ? 'scan' : 'select');
    };

    const renderPreview = (dataUrl) => {
      previewImage.src = dataUrl;
      previewImage.style.display = '';
      previewEmpty.style.display = 'none';
    };

    const updateStudentPanel = (student) => {
      currentStudent = student || null;
      selectedTurnId = Number(student?.turno_id || 0);
      studentIdInput.value = student?.alumno_id || student?.id || '';
      turnIdInput.value = student?.turno_id || '';
      studentName.textContent = student?.nombre || '—';
      studentControl.textContent = student?.no_control || '—';
      studentCareer.textContent = student?.carrera || '—';
      studentSemester.textContent = student?.semestre || '—';
      studentStatus.textContent = student?.estatus || '—';
      clearScan();
      setStatus(student ? 'Ready to capture fingerprint.' : 'Select a student to start fingerprint capture.');
      retryButton.disabled = !student;
      startButton.disabled = !student;
    };

    const renderQueue = (filter = '') => {
      const search = filter.trim().toLowerCase();
      const items = !search ? queue : queue.filter((item) => (
        String(item.nombre || '').toLowerCase().includes(search) ||
        String(item.no_control || '').toLowerCase().includes(search) ||
        String(item.turno || '').toLowerCase().includes(search)
      ));

      queueList.innerHTML = items.map((item) => `
        <div class="d-qItem ${Number(item.turno_id) === selectedTurnId ? 'is-active' : ''}"
             data-turn-id="${item.turno_id}"
             data-student-id="${item.alumno_id}">
          <div class="d-qName">${escapeHtml(item.nombre || '—')}</div>
          <div class="d-qMeta">
            <span>#${escapeHtml(item.no_control || '—')}</span>
            <span>${escapeHtml(item.carrera || '—')}</span>
            <span>${escapeHtml(item.turno || '—')}</span>
          </div>
        </div>
      `).join('');

      queueCount.textContent = String(queue.length);
    };

    const pickStudent = async (turnId, studentId) => {
      const url = new URL(studentUrl, window.location.origin);
      if (turnId) url.searchParams.set('turnoId', String(turnId));
      else if (studentId) url.searchParams.set('alumnoId', String(studentId));

      const response = await fetch(url.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const payload = await response.json().catch(() => null);
      if (!response.ok || !payload?.ok) {
        throw new Error('Could not load the selected student.');
      }

      updateStudentPanel(payload.alumno || null);
      renderQueue(queueSearch.value || '');
    };

    const generateFingerprintImage = (seed) => {
      const canvas = document.createElement('canvas');
      canvas.width = 320;
      canvas.height = 320;
      const context = canvas.getContext('2d');
      const centerX = canvas.width / 2;
      const centerY = canvas.height / 2;

      context.fillStyle = '#f8fafc';
      context.fillRect(0, 0, canvas.width, canvas.height);
      context.strokeStyle = '#0f172a';
      context.lineWidth = 2;

      for (let ring = 0; ring < 12; ring += 1) {
        context.beginPath();
        const radiusX = 42 + ring * 9;
        const radiusY = 58 + ring * 8;

        for (let angle = 0; angle <= Math.PI * 2; angle += 0.05) {
          const offset = Math.sin(angle * (3 + (seed % 5)) + (ring * 0.7)) * (4 + ring * 0.35);
          const x = centerX + Math.cos(angle) * (radiusX + offset);
          const y = centerY + Math.sin(angle) * (radiusY + offset * 0.9);
          if (angle === 0) context.moveTo(x, y);
          else context.lineTo(x, y);
        }

        context.stroke();
      }

      return canvas.toDataURL('image/png');
    };

    const buildTemplate = (studentId, turnId, quality) => {
      const payload = {
        studentId,
        turnId,
        quality,
        capturedAt: new Date().toISOString(),
        nonce: Math.random().toString(36).slice(2, 10),
      };

      return btoa(unescape(encodeURIComponent(JSON.stringify(payload))));
    };

    const startScan = () => {
      const studentId = Number(studentIdInput.value || 0);
      const turnId = Number(turnIdInput.value || 0);

      if (!studentId || !turnId) {
        setStatus('Select a student before starting fingerprint capture.');
        showToast('Select a student first');
        return;
      }

      const quality = 72 + Math.floor(Math.random() * 24);
      const image = generateFingerprintImage(studentId + turnId + quality);
      const template = buildTemplate(studentId, turnId, quality);

      currentScan = {
        image,
        template,
      };

      renderPreview(image);
      setQuality(quality);
      saveInfo.textContent = `Template ready · ${quality}% quality`;
      saveButton.disabled = false;
      retryButton.disabled = false;
      setStep('save');
      setStatus('Fingerprint captured. Review and save.');
      showToast('Fingerprint captured');
    };

    const saveFingerprint = async () => {
      if (!currentScan) return;

      const studentId = Number(studentIdInput.value || 0);
      const turnId = Number(turnIdInput.value || 0);

      if (!studentId || !turnId) return;

      saveButton.disabled = true;

      try {
        const form = new FormData();
        form.append('student_id', String(studentId));
        form.append('turn_id', String(turnId));
        form.append('template', currentScan.template);
        form.append('image', currentScan.image);
        form.append('quality', String(currentQuality));
        if (csrfName && csrfHash) form.append(csrfName, csrfHash);

        const response = await fetch(saveUrl, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: form,
        });

        const payload = await response.json().catch(() => null);
        if (!response.ok || !payload?.ok) {
          throw new Error(payload?.msg || 'Could not save the fingerprint.');
        }

        queue = Array.isArray(payload.queue) ? payload.queue : queue.filter((item) => Number(item.turno_id) !== turnId);
        updateStudentPanel(payload.current || null);
        renderQueue(queueSearch.value || '');
        setStatus(payload.current ? 'Fingerprint saved. Next student loaded.' : 'Fingerprint saved. Queue is empty.');
        showToast('Fingerprint saved');
      } catch (error) {
        console.error(error);
        saveButton.disabled = false;
        setStatus(error.message || 'Unexpected error while saving fingerprint.');
        showToast('Could not save fingerprint');
      }
    };

    const refreshQueue = async (keepSelection = true) => {
      const response = await fetch(queueUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const payload = await response.json().catch(() => null);
      if (!response.ok || !payload?.ok) return;

      queue = Array.isArray(payload.items) ? payload.items : [];
      renderQueue(queueSearch.value || '');

      if (!keepSelection) return;

      const stillExists = queue.some((item) => Number(item.turno_id) === selectedTurnId);
      if (!stillExists) {
        updateStudentPanel(queue[0] || null);
        renderQueue(queueSearch.value || '');
      }
    };

    queueList.addEventListener('click', async (event) => {
      const item = event.target.closest('[data-turn-id]');
      if (!item) return;

      try {
        await pickStudent(Number(item.dataset.turnId || 0), Number(item.dataset.studentId || 0));
        setStep('scan');
        setStatus('Student selected. Start fingerprint capture.');
      } catch (error) {
        console.error(error);
        showToast('Could not load student');
      }
    });

    queueSearch.addEventListener('input', (event) => {
      renderQueue(event.target.value || '');
    });

    startButton.addEventListener('click', startScan);
    retryButton.addEventListener('click', startScan);
    saveButton.addEventListener('click', saveFingerprint);

    updateStudentPanel(currentStudent || queue[0] || null);
    renderQueue('');
    refreshQueue(true);
  });
})();
