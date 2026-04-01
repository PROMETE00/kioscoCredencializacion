(() => {
  if (!window.__FIRMA__) {
    return;
  }

  // ====== refs UI ======
  const colaList   = document.getElementById("colaList");
  const colaSearch = document.getElementById("colaSearch");
  const colaCount  = document.getElementById("colaCount");

  const capNombre  = document.getElementById("capNombre");
  const capControl = document.getElementById("capControl");
  const capCarrera = document.getElementById("capCarrera");
  const capSemestre= document.getElementById("capSemestre");
  const capEstatus = document.getElementById("capEstatus");
  const capStatus  = document.getElementById("capStatus");

  const alumnoIdEl = document.getElementById("alumnoId");
  const turnoIdEl  = document.getElementById("turnoId");

  const btnClear = document.getElementById("btnClear");
  const btnSave  = document.getElementById("btnSave");

  const preview = document.getElementById("sigPreview");
  const previewEmpty = document.getElementById("sigPreviewEmpty");
  const canvas = document.getElementById("sigCanvas");
  let selectedTurnoId = Number(turnoIdEl.value || 0);

  if (!colaList || !colaSearch || !colaCount || !capNombre || !capControl || !capCarrera || !capSemestre || !capEstatus || !capStatus || !alumnoIdEl || !turnoIdEl || !btnClear || !btnSave || !preview || !previewEmpty || !canvas) {
    console.error("[firma] Faltan elementos requeridos en la vista.");
    return;
  }

  // ====== Canvas firma ======
  const ctx = canvas.getContext("2d");

  let drawing = false;
  let last = null;
  let hasInk = false;

  function resizeCanvas() {
    const dpr = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    canvas.width  = Math.floor(rect.width * dpr);
    canvas.height = Math.floor(rect.height * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

    resetPad(false);
  }

  function resetPad(keepPreview = true) {
    const rect = canvas.getBoundingClientRect();
    ctx.clearRect(0, 0, rect.width, rect.height);

    ctx.lineCap = "round";
    ctx.lineJoin = "round";
    ctx.strokeStyle = "#000";
    ctx.lineWidth = 2.2;

    hasInk = false;

    if (!keepPreview) {
      preview.style.display = "none";
      previewEmpty.style.display = "";
      previewEmpty.textContent = "Resultado —";
    }
  }

  function pos(e) {
    const rect = canvas.getBoundingClientRect();
    return { x: e.clientX - rect.left, y: e.clientY - rect.top };
  }

  function down(e) {
    if (btnClear.disabled) return;
    drawing = true;
    last = pos(e);
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
    hasInk = true;
    if (!btnClear.disabled) {
      btnSave.disabled = false;
    }
    e.preventDefault();
  }
  function up(e) {
    drawing = false;
    last = null;
    e.preventDefault();
  }

  canvas.addEventListener("pointerdown", down);
  canvas.addEventListener("pointermove", move);
  window.addEventListener("pointerup", up);

  window.addEventListener("resize", resizeCanvas);
  resizeCanvas();

  // ====== Estado botones ======
  function setCaptureEnabled(enabled) {
    btnClear.disabled = !enabled;
    btnSave.disabled  = !enabled || !hasInk;
  }

  setCaptureEnabled(false);

  btnClear.addEventListener("click", () => {
    resetPad(true);
    setCaptureEnabled(Number(alumnoIdEl.value || 0) > 0 && Number(turnoIdEl.value || 0) > 0);
    capStatus.textContent = "Firma limpiada. Vuelve a capturar y confirma la firma.";
  });

  // ====== Guardar firma ======
  btnSave.addEventListener("click", async () => {
    const alumnoId = Number(alumnoIdEl.value || 0);
    const turnoId  = Number(turnoIdEl.value || 0);

    if (!alumnoId || !turnoId) {
      capStatus.textContent = "Selecciona un alumno desde la cola.";
      return;
    }

    if (!hasInk) {
      capStatus.textContent = "Firma vacía. Dibuja la firma antes de guardar.";
      return;
    }

    btnSave.disabled = true;

    try {
      const dataUrl = canvas.toDataURL("image/png");

      const fd = new FormData();
      fd.append("alumno_id", String(alumnoId));
      fd.append("turno_id", String(turnoId));
      fd.append("firma_png", dataUrl);

      // CSRF CI4
      if (window.__FIRMA__?.csrfName && window.__FIRMA__?.csrfHash) {
        fd.append(window.__FIRMA__.csrfName, window.__FIRMA__.csrfHash);
      }

      const res = await fetch(window.__FIRMA__.guardarUrl, { method: "POST", body: fd });
      const json = await res.json().catch(() => null);

      if (!res.ok || !json?.ok) throw new Error(json?.message || "No se pudo guardar.");

      preview.src = json.url;
      preview.style.display = "";
      previewEmpty.style.display = "none";

      capStatus.textContent = "Firma guardada correctamente. Selecciona el siguiente alumno para continuar.";
      setCaptureEnabled(false);
      hasInk = false;
      selectedTurnoId = Number(json?.current?.ticket_id || 0);

      // refresca cola por si se mueve a siguiente estado
      await fetchCola(true);

    } catch (e) {
      capStatus.textContent = e.message || "Error al guardar firma.";
      btnSave.disabled = !hasInk;
    }
  });

  // ====== Cola ======
  let colaData = [];

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({
      "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"
    }[c]));
  }

  function renderCola(list) {
    colaCount.textContent = String(list.length);

    if (!list.length) {
      colaList.innerHTML = `<div style="color:#64748b;font-weight:700;padding:10px;">Sin alumnos en cola.</div>`;
      return;
    }

    colaList.innerHTML = list.map(a => `
      <div class="${Number(a.ticket_id) === selectedTurnoId ? "d-queue-item is-active" : "d-queue-item"}"
           data-alumno-id="${a.student_id}"
           data-turno-id="${a.ticket_id}"
           data-nombre="${escapeHtml(a.name)}"
           data-control="${escapeHtml(a.control_number)}"
           data-carrera="${escapeHtml(a.career)}"
           data-semestre="${escapeHtml(a.semester)}"
           data-estatus="${escapeHtml(a.status)}">
        <div>
          <div class="d-queue-name">${escapeHtml(a.name)}</div>
          <div class="d-queue-meta" style="text-align:left;">
            ${escapeHtml(a.control_number)} · ${escapeHtml(a.career)}
          </div>
        </div>
        <div class="d-queue-meta">
          Turno <strong>${escapeHtml(a.ticket_folio)}</strong><br>
          Listo para capturar
        </div>
      </div>
    `).join("");
  }

  function applySelected(el) {
    const data = el.dataset;

    alumnoIdEl.value = data.alumnoId || "0";
    turnoIdEl.value  = data.turnoId || "0";

    capNombre.textContent   = data.nombre || "—";
    capControl.textContent  = data.control || "—";
    capCarrera.textContent  = data.carrera || "—";
    capSemestre.textContent = data.semestre || "—";
    capEstatus.textContent  = data.estatus || "—";
    selectedTurnoId = Number(data.turnoId || 0);

    // resetea pad y deja la captura lista al cambiar alumno
    resetPad(false);
    setCaptureEnabled(true);

    colaList.querySelectorAll(".d-queue-item").forEach((item) => item.classList.remove("is-active"));
    el.classList.add("is-active");

    capStatus.textContent = "Alumno seleccionado. Captura la firma y confirma la captura.";
  }

  colaList.addEventListener("click", (e) => {
    const item = e.target.closest(".d-queue-item");
    if (!item) return;
    applySelected(item);
  });

  colaSearch.addEventListener("input", () => {
    const q = (colaSearch.value || "").toLowerCase().trim();
    const filtered = !q ? colaData : colaData.filter(a =>
      String(a.name).toLowerCase().includes(q) ||
      String(a.control_number).toLowerCase().includes(q)
    );
    renderCola(filtered);
  });

  function filterQueue(items) {
    const q = (colaSearch.value || "").toLowerCase().trim();

    return !q ? items : items.filter(a =>
      String(a.name).toLowerCase().includes(q) ||
      String(a.control_number).toLowerCase().includes(q)
    );
  }

  async function fetchCola(selectAfterLoad = false) {
    try {
      const res = await fetch(window.__FIRMA__.colaUrl, { method: "GET" });
      if (!res.ok) return;
      const json = await res.json();
      colaData = Array.isArray(json?.items) ? json.items : [];
      const list = filterQueue(colaData);
      renderCola(list);

      const selectedStillExists = colaData.some((item) => Number(item.ticket_id) === selectedTurnoId);

      if (selectedStillExists) {
        const selectedNode = colaList.querySelector(`[data-turno-id="${selectedTurnoId}"]`);
        if (selectedNode) {
          colaList.querySelectorAll(".d-queue-item").forEach((item) => item.classList.remove("is-active"));
          selectedNode.classList.add("is-active");
          return;
        }
      }

      if (selectAfterLoad || (!selectedStillExists && list.length > 0)) {
        const firstNode = colaList.querySelector(".d-queue-item");
        if (firstNode) {
          applySelected(firstNode);
          return;
        }
      }

      if (!list.length) {
        alumnoIdEl.value = "0";
        turnoIdEl.value = "0";
        selectedTurnoId = 0;
        capNombre.textContent = "—";
        capControl.textContent = "—";
        capCarrera.textContent = "—";
        capSemestre.textContent = "—";
        capEstatus.textContent = "—";
        resetPad(false);
        setCaptureEnabled(false);
        capStatus.textContent = "No hay alumnos pendientes de firma en este momento.";
      }
    } catch (_) {}
  }

  if (Number(alumnoIdEl.value || 0) > 0 && Number(turnoIdEl.value || 0) > 0) {
    setCaptureEnabled(true);
    capStatus.textContent = "Alumno listo para capturar firma. Dibuja y confirma la captura.";
  }

  // polling ligero
  fetchCola(true);
  setInterval(fetchCola, 5000);
})();
