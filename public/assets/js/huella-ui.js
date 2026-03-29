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
  const alumnoIdEl = $('alumnoId');
  const turnoIdEl  = $('turnoId');

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

  let selectedTurnoId = Number(turnoIdEl?.value || 0);
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
      <div class="d-queue-item ${Number(a.turno_id) === selectedTurnoId ? 'is-active' : ''}"
           data-alumno-id="${a.alumno_id}"
           data-turno-id="${a.turno_id}"
           data-nombre="${escapeHtml(a.nombre)}"
           data-control="${escapeHtml(a.no_control)}"
           data-carrera="${escapeHtml(a.carrera)}"
           data-estatus="${escapeHtml(a.estatus)}">
        <div class="d-queue-main">
          <div class="d-queue-name">${escapeHtml(a.nombre)}</div>
          <div class="d-queue-info">${escapeHtml(a.no_control)} · ${escapeHtml(a.carrera)}</div>
        </div>
        <div class="d-queue-side">
          <div class="d-queue-folio">${escapeHtml(a.turno)}</div>
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
        String(a.nombre).toLowerCase().includes(q) ||
        String(a.no_control).toLowerCase().includes(q)
      );
      
      renderCola(filtered);

      if (selectAfterLoad && filtered.length > 0 && !selectedTurnoId) {
        applySelected(filtered[0]);
      } else if (selectedTurnoId) {
        // Asegurar que el elemento activo se mantenga
        const stillInQueue = colaData.find(a => Number(a.turno_id) === selectedTurnoId);
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

    alumnoIdEl.value = data.alumno_id || data.id || "0";
    turnoIdEl.value  = data.turno_id || "0";

    if (alNombre) alNombre.textContent = data.nombre || "—";
    if (alControl) alControl.textContent = data.no_control || "—";
    if (alCarrera) alCarrera.textContent = data.carrera || "—";
    if (alEstatus) alEstatus.textContent = data.estatus || "—";
    if (alStatus) alStatus.textContent = "Listo para capturar huella.";

    selectedTurnoId = Number(data.turno_id || 0);

    clearPreview();
    currentHuellaB64 = "";
    if (btnSave) btnSave.disabled = true;
    if (btnStart) btnStart.disabled = false;
    
    setStatus("Esperando captura...");
    setSaveInfo("—");

    // Marcar como activo en la lista visual
    document.querySelectorAll('.d-queue-item').forEach(el => {
      el.classList.remove('is-active');
      if (Number(el.dataset.turnoId) === selectedTurnoId) el.classList.add('is-active');
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
      String(a.nombre).toLowerCase().includes(q) ||
      String(a.no_control).toLowerCase().includes(q)
    );
    renderCola(filtered);
  });

  btnStart?.addEventListener('click', () => {
    setStatus("Capturando... coloca el dedo en el lector.");
    btnStart.disabled = true;

    // SIMULACIÓN: En una implementación real, aquí llamarías al SDK del lector.
    // Simulamos un retraso de captura.
    setTimeout(() => {
        // Imagen de huella genérica para la demo/simulación
        currentHuellaB64 = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGQAAABkCAYAAABw4pVUAAAACXBIWXMAAAsTAAALEwEAmpwYAAAFm0lEQVR4nO2dS2hcVRjHf+fOnZm8m8m0SdOkSdukSZqmSdO0SZpE6gNBpSii4EIQXbiI4K6IC0V0IbgpLhTRhYuCuFBXgguFuhNcKLoQXCiqG0V1o6huFNcNbS39mEyTyfS9587v4mI6L82bmZskd/7f7zCHe885k/v7/9/v3HO+M0FhGIYIggDP85AkCRH0Y7PZEAgC4rX7vHovSRLi6zRNiW9TkiTE9/0B+74/YN/3B+z7fnY9N7mOR9O0zHo0TUMQBCRJQvL/Bq0pTdPMeoZhaIbhyG/u3Xv33nOnp0/NzEznZmamF7Pzp6dnFm9N385Xvvn2h6Vbt1YWHnx88vS5s+d7XfH8+Z7evr7+8f6B/onBwcH80NDQQGFoaOjrwaGhU76P779yM1/55tsf763fvHX89NnzvefO9/R2v+879O9/H95/D99/5Xbu3vrt44NPmD9+5Z7f7OOfv3R+6Y03j584fOTU9InTp3unp0/NzU6fmtu8vX5/669f9r777ofljU8/vX/309WfV1Z+619ZfWvlj5+Xf/vldv+/799vO+S3P7YfDtt/2P7DdtghDtv/sh867LBDfOew7ZDH3L8B63nIfB4yn8fM5zHzecw8Zp7HzOOYOR4zx2PmeMx8Xj6PF8/jxfP4PJ4vj+fL4/ny+DxePI8Xz8fj8fF4fDweb6/H2+vx9nq8vR5vr8v7D9578On06bPne8f7B/pXvvn2h8Vbt1Y8p8P277X9u23/Xtt922vX+XfX9bp1vS9d76vS9X7v+z9S7773YfnMv9tIkoQ4Yf58T+8nn64+XN749FO7v+mQW+f7rvR93/8uej973u+9Y9f7unX99u31++/7DvvfRe+/0vshPv6/j0qf/3uNf358On367Pne8f6B/uefO3e+v7//C3zH29P38XfX8+3p+/nNfOf/XuN7X7euf/99fK+/i7/f9X7vff9H8f77v89Xv72+/+DBJ6ePHDk1e+709KlMhvT09X9vD8j+D5S97v9A2f8fDEnX9drf/Xdd1+vW9X+UrtftB69X58+fO3++r79/PDXkDv8P8p1PZcgY/w7z6fRp9A7yYfT677Kvx776ve7vsq/9n0Wv977v/S9G76fT6fR6vV6vV6vX2+v19np9Pp9Pp9Pr9fl8Pp9PJ/nPJ/mD16s8vSrvvXjvwYNPXnrpzaX88S97Th/790OHT5pPpk8fOfL6zOnTvZMTp+Yyb80vffv993u/fvnN/V7ff6V//6tO990fdf79UXf98Wnnf6Xzf8f96N/vV/73Xf973Xf/B/p+9v3v93796D98vTp8Pq9X7v0vX+/P6/25SrvvI1/vI1/vy+v9uR6v96Xp+uT9PnH/N16fH69P6vV6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6v1+v1er1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/X6/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1er9fr9Xq9Xq/X6/V6vV6v1+v1er1f+A5FqD6O35m8oAAAAAElFTkSuQmCC";
        
        showPreview(currentHuellaB64);
        setStatus("Captura exitosa. Revisa la imagen y confirma.");
        setSaveInfo("Huella detectada");
        btnSave.disabled = false;
        showToast("¡Huella capturada con éxito!");
    }, 1500);
  });

  btnSave?.addEventListener('click', async () => {
    const alumnoId = Number(alumnoIdEl.value || 0);
    const turnoId  = Number(turnoIdEl.value || 0);

    if (!alumnoId || !turnoId || !currentHuellaB64) {
      showToast("Error: No hay captura válida.");
      return;
    }

    btnSave.disabled = true;
    setStatus("Guardando en servidor...");

    try {
      const fd = new FormData();
      fd.append("alumno_id", String(alumnoId));
      fd.append("turno_id", String(turnoId));
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
