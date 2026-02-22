(() => {
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

  const btnStart = document.getElementById("btnStart");
  const btnClear = document.getElementById("btnClear");
  const btnSave  = document.getElementById("btnSave");

  const chkBg = document.getElementById("chkBg");

  const preview = document.getElementById("sigPreview");
  const previewEmpty = document.getElementById("sigPreviewEmpty");

  // ====== Canvas firma ======
  const canvas = document.getElementById("sigCanvas");
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

    if (chkBg.checked) {
      ctx.fillStyle = "#fff";
      ctx.fillRect(0, 0, rect.width, rect.height);
    }

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

  chkBg.addEventListener("change", () => resetPad(true));

  // ====== Estado botones ======
  function setCaptureEnabled(enabled) {
    btnClear.disabled = !enabled;
    btnSave.disabled  = !enabled;
  }

  setCaptureEnabled(false);

  btnStart.addEventListener("click", () => {
    const alumnoId = Number(alumnoIdEl.value || 0);
    const turnoId  = Number(turnoIdEl.value || 0);

    if (!alumnoId || !turnoId) {
      capStatus.textContent = "Primero selecciona un alumno desde la cola.";
      return;
    }

    resetPad(true);
    setCaptureEnabled(true);
    capStatus.textContent = "Listo. Captura la firma y presiona Guardar.";
  });

  btnClear.addEventListener("click", () => {
    resetPad(true);
    capStatus.textContent = "Firma limpiada. Captura nuevamente.";
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

      capStatus.textContent = "Firma guardada correctamente.";
      setCaptureEnabled(false); // opcional: bloquear hasta que selecciones otro
      hasInk = false;

      // refresca cola por si se mueve a siguiente estado
      fetchCola();

    } catch (e) {
      capStatus.textContent = e.message || "Error al guardar firma.";
      btnSave.disabled = false;
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
      <div class="d-queue-item"
           data-alumno-id="${a.alumno_id}"
           data-turno-id="${a.turno_id}"
           data-nombre="${escapeHtml(a.nombre)}"
           data-control="${escapeHtml(a.no_control)}"
           data-carrera="${escapeHtml(a.carrera)}"
           data-semestre="${escapeHtml(a.semestre)}"
           data-estatus="${escapeHtml(a.estatus)}">
        <div>
          <div class="d-queue-name">${escapeHtml(a.nombre)}</div>
          <div class="d-queue-meta" style="text-align:left;">
            ${escapeHtml(a.no_control)} · ${escapeHtml(a.carrera)}
          </div>
        </div>
        <div class="d-queue-meta">
          Turno <strong>${escapeHtml(a.turno)}</strong><br>
          ${escapeHtml(a.estatus)}
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

    // resetea pad y preview al cambiar alumno
    setCaptureEnabled(false);
    resetPad(false);

    capStatus.textContent = "Alumno cargado. Presiona “Preparar” para capturar firma.";
  }

  colaList.addEventListener("click", (e) => {
    const item = e.target.closest(".d-queue-item");
    if (!item) return;
    applySelected(item);
  });

  colaSearch.addEventListener("input", () => {
    const q = (colaSearch.value || "").toLowerCase().trim();
    const filtered = !q ? colaData : colaData.filter(a =>
      String(a.nombre).toLowerCase().includes(q) ||
      String(a.no_control).toLowerCase().includes(q)
    );
    renderCola(filtered);
  });

  async function fetchCola() {
    try {
      const res = await fetch(window.__FIRMA__.colaUrl, { method: "GET" });
      if (!res.ok) return;
      const json = await res.json();
      colaData = Array.isArray(json?.items) ? json.items : [];
      const q = (colaSearch.value || "").toLowerCase().trim();
      const list = !q ? colaData : colaData.filter(a =>
        String(a.nombre).toLowerCase().includes(q) ||
        String(a.no_control).toLowerCase().includes(q)
      );
      renderCola(list);
    } catch (_) {}
  }

  // polling ligero
  fetchCola();
  setInterval(fetchCola, 2500);
})();