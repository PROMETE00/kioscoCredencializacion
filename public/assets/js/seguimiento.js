document.addEventListener('DOMContentLoaded', () => {
  const container = document.querySelector('[data-seguimiento-endpoint]');
  const refreshButton = document.querySelector('[data-refresh-seguimiento]');

  if (!container) {
    return;
  }

  const endpoint = container.getAttribute('data-seguimiento-endpoint');

  if (!endpoint) {
    return;
  }

  const fields = {
    badge: document.getElementById('estatus-badge'),
    etapa: document.getElementById('etapa-texto'),
    mensaje: document.getElementById('mensaje-progreso'),
    eta: document.getElementById('eta-texto'),
    turnosAntes: document.getElementById('turnos-antes'),
    turnoActualFolio: document.getElementById('turno-actual-folio'),
    turnoActualEtapa: document.getElementById('turno-actual-etapa'),
    llamado: document.getElementById('llamado-texto'),
  };

  const applyTurnoState = (turno) => {
    if (fields.badge) {
      fields.badge.textContent = turno.estatus ?? 'N/A';
      fields.badge.className = `pt-badge ${turno.badge_class ?? 'pt-badge--waiting'}`;
    }

    if (fields.etapa) {
      fields.etapa.textContent = turno.etapa ?? 'N/A';
    }

    if (fields.mensaje) {
      fields.mensaje.textContent = turno.mensaje_progreso ?? 'Seguimiento disponible';
    }

    if (fields.eta) {
      fields.eta.textContent = turno.eta_texto ?? 'N/A';
    }

    if (fields.turnosAntes) {
      fields.turnosAntes.textContent = `${turno.turnos_antes ?? 0}`;
    }

    if (fields.turnoActualFolio) {
      fields.turnoActualFolio.textContent = turno.turno_actual_folio ?? 'Sin turno en atención';
    }

    if (fields.turnoActualEtapa) {
      fields.turnoActualEtapa.textContent = turno.turno_actual_etapa ?? 'En espera de atención';
    }

    if (fields.llamado) {
      fields.llamado.textContent = turno.llamado_at_texto ?? 'Pendiente';
    }
  };

  const fetchTurno = async () => {
    const response = await fetch(endpoint, {
      headers: {
        Accept: 'application/json',
      },
      cache: 'no-store',
    });

    if (!response.ok) {
      return;
    }

    const payload = await response.json();

    if (!payload.ok || !payload.turno) {
      return;
    }

    applyTurnoState(payload.turno);
  };

  if (refreshButton) {
    refreshButton.addEventListener('click', () => {
      fetchTurno();
    });
  }

  window.setInterval(fetchTurno, 30000);
});
