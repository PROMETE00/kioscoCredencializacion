const cfg = document.getElementById('cfg');

const COLA_URL   = cfg?.dataset.colaUrl;
const GUARDAR_URL= cfg?.dataset.guardarUrl;
const ALUMNO_URL = cfg?.dataset.alumnoUrl;

const csrfName = cfg?.dataset.csrfName;
const csrfHash = cfg?.dataset.csrfHash;

const $ = (id) => document.getElementById(id);

function setStatus(msg){ $('fpStatus').textContent = msg; }
function setSaveInfo(msg){ $('saveInfo').textContent = msg; }

function showPreview(src){
  const img = $('fpPreview');
  const empty = $('fpPreviewEmpty');
  img.src = src;
  img.style.display = '';
  empty.style.display = 'none';
}

function clearPreview(){
  const img = $('fpPreview');
  const empty = $('fpPreviewEmpty');
  img.removeAttribute('src');
  img.style.display = 'none';
  empty.style.display = '';
}

console.log('[huella] ready', { COLA_URL, GUARDAR_URL, ALUMNO_URL });

/*
  TODO:
  - fetch cola -> render items en #queueList
  - click item -> set alumno UI (#alNombre, #alControl...) + hidden (#alumnoId/#turnoId)
  - btnStart -> iniciar captura (depende de tu lector/SDK)
  - btnSave -> POST plantilla/imagen + alumnoId/turnoId
*/