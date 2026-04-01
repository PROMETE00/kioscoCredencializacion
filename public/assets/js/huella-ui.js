(() => {
  const cfg = document.getElementById('cfg');
  if (!cfg) return;

  const COLA_URL    = cfg.dataset.colaUrl;
  const GUARDAR_URL = cfg.dataset.guardarUrl;
  const ALUMNO_URL  = cfg.dataset.alumnoUrl;
  const csrfName    = cfg.dataset.csrfName;
  let csrfHash      = cfg.dataset.csrfHash;

  const $ = (id) => document.getElementById(id);

  // Elementos UI Alumno (Panel Izquierdo)
  const alNombre   = $('alNombre');
  const alControl  = $('alControl');
  const alCarrera  = $('alCarrera');
  const alSemestre = $('alSemestre');
  const alEstatus  = $('alEstatus');
  const alStatus   = $('alStatus');
  const studentIdEl = $('studentId');
  const ticketIdEl  = $('turnoId');

  // Elementos UI Captura (Panel Central)
  const fpStatus   = $('fpStatus');
  const saveInfo   = $('saveInfo');
  const btnStart   = $('btnStart');
  const btnSave    = $('btnSave');
  const fpPreview  = $('fpPreview');
  const fpPreviewEmpty = $('fpPreviewEmpty');
  const toast      = $('toast');

  // Elementos UI Cola (Panel Derecho)
  const queueList  = $('queueList');
  const queueCount = $('queueCount');
  const queueSearch= $('queueSearch');

  let selectedTurnoId = Number(ticketIdEl?.value || 0);
  let colaData = [];
  let currentHuellaB64 = "";

  function showToast(msg, duration = 3000) {
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add('is-visible');
    setTimeout(() => toast.classList.remove('is-visible'), duration);
  }

  function setStatus(msg) { if (fpStatus) fpStatus.textContent = msg; }
  function setSaveInfo(msg) { if (saveInfo) saveInfo.textContent = msg; }

  function showPreview(src) {
    if (!fpPreview || !fpPreviewEmpty) return;
    fpPreview.src = src;
    fpPreview.style.display = '';
    fpPreviewEmpty.style.display = 'none';
  }

  function clearPreview() {
    if (!fpPreview || !fpPreviewEmpty) return;
    fpPreview.removeAttribute('src');
    fpPreview.style.display = 'none';
    fpPreviewEmpty.style.display = '';
  }

  // ====== Lógica de Cola ======
  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({
      "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"
    }[c]));
  }

  function renderCola(list) {
    if (!queueList || !queueCount) return;
    queueCount.textContent = String(list.length);

    if (!list.length) {
      queueList.innerHTML = `<div style="color:#94a3b8;padding:1rem;text-align:center;">Sin alumnos en cola.</div>`;
      return;
    }

    queueList.innerHTML = list.map(a => `
      <div class="d-queue-item ${Number(a.ticket_id) === selectedTurnoId ? 'is-active' : ''}"
           data-student-id="${a.student_id}"
           data-ticket-id="${a.ticket_id}"
           data-name="${escapeHtml(a.name)}"
           data-control="${escapeHtml(a.control_number)}"
           data-career="${escapeHtml(a.career)}"
           data-status="${escapeHtml(a.status)}">
        <div class="d-queue-main">
          <div class="d-queue-name">${escapeHtml(a.name)}</div>
          <div class="d-queue-info">${escapeHtml(a.control_number)} · ${escapeHtml(a.career)}</div>
        </div>
        <div class="d-queue-side">
          <div class="d-queue-folio">${escapeHtml(a.ticket_folio)}</div>
          <div class="d-queue-label">Listo</div>
        </div>
      </div>
    `).join("");
  }

  async function fetchCola(selectAfterLoad = false) {
    try {
      const res = await fetch(COLA_URL);
      if (!res.ok) return;
      const json = await res.json();
      colaData = Array.isArray(json?.items) ? json.items : [];
      
      const q = (queueSearch?.value || "").toLowerCase().trim();
      const filtered = !q ? colaData : colaData.filter(a =>
        String(a.name).toLowerCase().includes(q) ||
        String(a.control_number).toLowerCase().includes(q)
      );
      
      renderCola(filtered);

      if (selectAfterLoad && filtered.length > 0 && !selectedTurnoId) {
        applySelected(filtered[0]);
      } else if (selectedTurnoId) {
        // Asegurar que el elemento activo se mantenga
        const stillInQueue = colaData.find(a => Number(a.ticket_id) === selectedTurnoId);
        if (!stillInQueue && filtered.length > 0) {
            applySelected(filtered[0]);
        }
      }
    } catch (e) {
      console.error("[huella] error fetch cola", e);
    }
  }

  function applySelected(data) {
    if (!data) return;

    studentIdEl.value = data.student_id || data.id || "0";
    ticketIdEl.value  = data.ticket_id || "0";

    if (alNombre) alNombre.textContent = data.name || "—";
    if (alControl) alControl.textContent = data.control || "—";
    if (alCarrera) alCarrera.textContent = data.career || "—";
    if (alEstatus) alEstatus.textContent = data.status || "—";
    if (alStatus) alStatus.textContent = "Listo para capturar huella.";

    selectedTurnoId = Number(data.ticket_id || 0);

    clearPreview();
    currentHuellaB64 = "";
    if (btnSave) btnSave.disabled = true;
    if (btnStart) btnStart.disabled = false;
    
    setStatus("Esperando captura...");
    setSaveInfo("—");

    // Marcar como activo en la lista visual
    document.querySelectorAll('.d-queue-item').forEach(el => {
      el.classList.remove('is-active');
      if (Number(el.dataset.ticketId) === selectedTurnoId) el.classList.add('is-active');
    });
  }

  // ====== Eventos UI ======
  queueList?.addEventListener('click', (e) => {
    const item = e.target.closest('.d-queue-item');
    if (item) applySelected(item.dataset);
  });

  queueSearch?.addEventListener('input', () => {
    const q = queueSearch.value.toLowerCase().trim();
    const filtered = !q ? colaData : colaData.filter(a =>
      String(a.name).toLowerCase().includes(q) ||
      String(a.control_number).toLowerCase().includes(q)
    );
    renderCola(filtered);
  });

  btnStart?.addEventListener('click', () => {
    setStatus("Capturando... coloca el dedo en el lector.");
    btnStart.disabled = true;

    // SIMULACIÓN
    setTimeout(() => {
        currentHuellaB64 = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGQAAABkCAYAAABw4pVU...";
        
        showPreview(currentHuellaB64);
        setStatus("Captura exitosa. Revisa la imagen y confirma.");
        setSaveInfo("Huella detectada");
        btnSave.disabled = false;
        showToast("¡Huella capturada con éxito!");
    }, 1500);
  });

  btnSave?.addEventListener('click', async () => {
    const studentId = Number(studentIdEl.value || 0);
    const ticketId  = Number(ticketIdEl.value || 0);

    if (!studentId || !ticketId || !currentHuellaB64) {
      showToast("Error: No hay captura válida.");
      return;
    }

    btnSave.disabled = true;
    setStatus("Guardando en servidor...");

    try {
      const fd = new FormData();
      fd.append("student_id", String(studentId));
      fd.append("turno_id", String(ticketId));
      fd.append("huella_b64", currentHuellaB64);

      if (csrfName && csrfHash) {
        fd.append(csrfName, csrfHash);
      }

      const res = await fetch(GUARDAR_URL, { method: "POST", body: fd });
      const json = await res.json().catch(() => null);

      if (!res.ok || !json?.ok) throw new Error(json?.message || "Error al guardar huella.");

      showToast("¡Huella guardada correctamente!");
      alStatus.textContent = "Huella guardada. Selecciona el siguiente alumno.";
      
      selectedTurnoId = 0; // Reset selección
      clearPreview();
      currentHuellaB64 = "";
      
      await fetchCola(true);
    } catch (e) {
      showToast(e.message);
      setStatus("Error al guardar.");
      btnSave.disabled = false;
    }
  });

  // ====== Inicialización ======
  fetchCola(true);
  setInterval(fetchCola, 10000); // Polling cada 10s

})();
