(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const configEl = document.getElementById('dashboardConfig');
    const stateEl = document.getElementById('dashboardState');

    if (!configEl || !stateEl) return;

    const FETCH_URL = configEl.dataset.fetchUrl || '';
    const STATUS_URL = configEl.dataset.statusUrl || '';
    const CLEAR_URL = configEl.dataset.clearUrl || '';
    const CSRF_NAME = configEl.dataset.csrfName || '';
    let csrfHash = configEl.dataset.csrfHash || '';

    const initialState = JSON.parse(stateEl.textContent || '{}');
    let rows = Array.isArray(initialState.items) ? initialState.items : [];
    const statusOptions = Array.isArray(initialState.statusOptions) ? initialState.statusOptions : [];
    let currentQuery = '';
    let fetchController = null;

    const searchInput = document.getElementById('dashboardSearch');
    const tbody = document.getElementById('dashboardRows');
    const meta = document.getElementById('dashboardMeta');
    const toast = document.getElementById('dashboardToast');

    const showToast = (message) => {
      if (!toast) return;
      toast.textContent = message;
      toast.classList.add('is-on');
      clearTimeout(showToast.timer);
      showToast.timer = setTimeout(() => toast.classList.remove('is-on'), 2200);
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => (
      {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]
    ));

    const renderChecklistItem = (label, ready) => {
      const icon = ready 
        ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`
        : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/></svg>`;
        
      return `
        <div class="d-checklist-item ${ready ? 'is-ready' : ''}">
          <div class="d-checklist-icon">${icon}</div>
          <span>${escapeHtml(label)}</span>
        </div>
      `;
    };

    const renderStatusOptions = (selectedId) => statusOptions.map((option) => `
      <option value="${option.id}" ${Number(option.id) === Number(selectedId) ? 'selected' : ''}>
        ${escapeHtml(option.name)}
      </option>
    `).join('');

    const renderRows = () => {
      if (!tbody) return;

      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="d-empty">No se encontraron alumnos para este filtro.</td></tr>';
        if (meta) meta.textContent = currentQuery ? 'Sin resultados para la búsqueda actual.' : 'No hay alumnos disponibles.';
        return;
      }

      tbody.innerHTML = rows.map((row) => {
        const hasTurn = Number(row.ticket_id) > 0;
        return `
          <tr data-alumno-id="${row.student_id}" data-turno-id="${row.ticket_id || ''}">
            <td>
              <div class="d-row-title">${escapeHtml(row.name)}</div>
              <div class="d-row-sub mono">${escapeHtml(row.identifier)}</div>
              <div class="d-row-sub">${escapeHtml(row.career)} · ${escapeHtml(row.campus)}</div>
            </td>
            <td>
              <div class="d-row-title">${escapeHtml(row.folio || 'Sin turno activo')}</div>
              <div class="d-row-sub">${escapeHtml(row.stage_name)}</div>
              <div class="d-row-sub mono">${escapeHtml(row.updated_at || '—')}</div>
            </td>
            <td>
              <div class="d-checklist">
                ${renderChecklistItem('Foto', !!row.has_photo)}
                ${renderChecklistItem('Firma', !!row.has_signature)}
                ${renderChecklistItem('Huella', !!row.has_fingerprint)}
              </div>
            </td>
            <!-- <td>
              <div class="d-actions-group">
                <select class="d-select" data-role="status-select" ${hasTurn ? '' : 'disabled'}>
                  ${renderStatusOptions(row.status_id)}
                </select>
                <button class="d-btn d-btn--primary" data-action="save-status" ${hasTurn ? '' : 'disabled'}>Guardar estatus</button>
              </div>
            </td> -->
            <td>
              <div class="d-actions-column">
                <button class="d-btn d-btn--danger" data-action="clear-biometric" data-type="photo" ${hasTurn && row.has_photo ? '' : 'disabled'}>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> Borrar foto
                </button>
                <button class="d-btn d-btn--danger" data-action="clear-biometric" data-type="signature" ${hasTurn && row.has_signature ? '' : 'disabled'}>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> Borrar firma
                </button>
                <button class="d-btn d-btn--danger" data-action="clear-biometric" data-type="fingerprint" ${hasTurn && row.has_fingerprint ? '' : 'disabled'}>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> Borrar huella
                </button>
              </div>
            </td>
          </tr>
        `;
      }).join('');

      if (meta) {
        meta.textContent = currentQuery
          ? `Mostrando hasta ${rows.length} coincidencias para "${currentQuery}".`
          : `Mostrando los primeros ${rows.length} alumnos encontrados.`;
      }
    };

    const updateKpis = (kpis) => {
      if (!kpis) return;
      const mapping = {
        'kpiTotalAlumnos': kpis.total_students,
        'kpiTurnosHoy': kpis.tickets_today,
        'kpiCompletadosHoy': kpis.completed_today,
        'kpiFotosHoy': kpis.photos_today,
        'kpiFirmasHoy': kpis.signatures_today,
        'kpiHuellasHoy': kpis.fingerprints_today
      };

      Object.entries(mapping).forEach(([id, value]) => {
        const el = document.getElementById(id);
        if (el) el.textContent = String(value ?? 0);
      });
    };

    const debounce = (fn, wait) => {
      let timer;
      return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), wait);
      };
    };

    const loadRows = async (query = '') => {
      currentQuery = query.trim();

      if (fetchController) fetchController.abort();
      fetchController = new AbortController();

      const url = new URL(FETCH_URL, window.location.origin);
      url.searchParams.set('q', currentQuery);
      url.searchParams.set('limit', '8');

      try {
        const response = await fetch(url.toString(), {
          method: 'GET',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          signal: fetchController.signal,
        });

        const json = await response.json().catch(() => null);
        if (!response.ok || !json?.ok) {
          throw new Error('No se pudo cargar la lista administrativa.');
        }

        rows = Array.isArray(json.items) ? json.items : [];
        renderRows();
        updateKpis(json.kpis);
      } catch (error) {
        if (error.name === 'AbortError') return;
        console.error(error);
        showToast('Error al cargar alumnos');
      }
    };

    const postAction = async (url, payload, successMessage) => {
      const form = new FormData();
      Object.entries(payload).forEach(([key, value]) => form.append(key, String(value ?? '')));
      if (CSRF_NAME && csrfHash) form.append(CSRF_NAME, csrfHash);

      const response = await fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: form,
      });

      const json = await response.json().catch(() => null);
      if (json?.csrfHash) csrfHash = json.csrfHash;

      if (!response.ok || !json?.ok) {
        throw new Error(json?.message || 'No se pudo completar la acción.');
      }

      showToast(successMessage || json.message || 'Acción completada');
      await loadRows(currentQuery);
    };

    tbody?.addEventListener('click', async (event) => {
      const button = event.target.closest('button[data-action]');
      if (!button) return;

      const row = button.closest('tr[data-alumno-id]');
      if (!row) return;

      const alumnoId = Number(row.dataset.alumnoId || 0);
      const turnoId = Number(row.dataset.turnoId || 0);

      if (!alumnoId || !turnoId) return;

      button.disabled = true;

      try {
        if (button.dataset.action === 'save-status') {
          const select = row.querySelector('[data-role="status-select"]');
          const estatusId = Number(select?.value || 0);
          await postAction(STATUS_URL, { turno_id: turnoId, estatus_id: estatusId }, 'Estatus actualizado');
        }

        if (button.dataset.action === 'clear-biometric') {
          await postAction(CLEAR_URL, {
            alumno_id: alumnoId,
            turno_id: turnoId,
            tipo: button.dataset.type || '',
          }, `${button.dataset.type || 'Biométrico'} borrado`);
        }
      } catch (error) {
        console.error(error);
        showToast(error.message || 'No se pudo completar la acción');
      } finally {
        button.disabled = false;
      }
    });

    searchInput?.addEventListener('input', debounce((event) => {
      loadRows(event.target.value || '');
    }, 220));

    updateKpis(initialState.kpis || {});
    renderRows();
  });
})();
