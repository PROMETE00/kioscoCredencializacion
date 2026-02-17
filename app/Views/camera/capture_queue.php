<?= $this->extend('layouts/discere') ?>

<?= $this->section('bodyClass') ?>capture-dark<?= $this->endSection() ?>

<?= $this->section('head') ?>
   <link rel="stylesheet" href="<?= base_url('assets/css/capture-queue.css') ?>">
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation/selfie_segmentation.js"></script>

  <style>
    /* 3 columnas en desktop, 1 columna en móvil */
    .d-grid-3{
      display:grid;
      grid-template-columns: 320px 1fr 360px;
      gap: 14px;
      align-items:start;
    }
    @media (max-width: 1100px){
      .d-grid-3{ grid-template-columns: 1fr; }
    }
    .d-panel{ background:#fff; border:1px solid var(--border); border-radius:12px; box-shadow: var(--shadow); padding:14px; }
    .d-panel__title{ font-weight:800; color:#374151; margin:0 0 10px; }

    .d-kv{ display:grid; gap:8px; font-size:14px; color:#374151; }
    .d-kv .lbl{ color: var(--muted); font-weight:700; margin-right:6px; }
    .d-badge{ display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; background:#f3f6fb; border:1px solid var(--border); font-size:12px; color:#374151; font-weight:800; }

    .d-queueTop{ display:flex; gap:10px; align-items:center; justify-content:space-between; }
    .d-search{
      width:100%; padding:10px 12px; border-radius:10px; border:1px solid var(--border);
      outline:none; background:#fbfdff;
    }
    .d-queueList{ margin:12px 0 0; padding:0; list-style:none; display:grid; gap:8px; max-height: 520px; overflow:auto; }
    .d-qItem{
      border:1px solid var(--border); background:#fbfdff; border-radius:12px; padding:10px 12px;
      cursor:pointer;
    }
    .d-qItem:hover{ background:#f3f6fb; }
    .d-qItem.is-active{ border-color: rgba(47,109,246,.45); box-shadow: 0 0 0 3px rgba(47,109,246,.10); }
    .d-qName{ font-weight:900; color:#111827; font-size:13px; }
    .d-qMeta{ color: var(--muted); font-size:12px; margin-top:4px; display:flex; gap:8px; flex-wrap:wrap; }
  </style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="d-toolbar">
  <!--vacio de momento pero sirve para implementar toolbar global si se requiere-->
</div>

<section class="d-grid-3">

  <!-- IZQUIERDA: Datos del alumno -->
  <aside class="d-panel">
    <div class="d-panel__title">Alumno en captura</div>

    <div class="d-kv">
      <div><span class="lbl">Nombre:</span> <span id="pNombre"><?= esc($current['nombre'] ?? '—') ?></span></div>
      <div><span class="lbl">No. control:</span> <span id="pControl"><?= esc($current['no_control'] ?? '—') ?></span></div>
      <div><span class="lbl">Carrera:</span> <span id="pCarrera"><?= esc($current['carrera'] ?? '—') ?></span></div>
      <div><span class="lbl">Semestre:</span> <span id="pSemestre"><?= esc($current['semestre'] ?? '—') ?></span></div>
      <div><span class="lbl">Estatus:</span> <span class="d-badge" id="pEstatus"><?= esc($current['estatus'] ?? '—') ?></span></div>
    </div>

    <input type="hidden" id="studentId" value="<?= esc($current['id'] ?? '') ?>">
    <p style="margin:12px 0 0; color: var(--muted); font-size:13px;">
      Tip: Selecciona un alumno de la cola para cargarlo aquí.
    </p>
  </aside>

  <!-- CENTRO: Cámara + preview + botones -->
  <section class="d-card" style="padding:14px;">
    <div class="d-card__header" style="padding:0 0 12px;">
      <div class="d-card__title">Cámara</div>

      <label class="d-toggle">
        <input id="chkWhiteBg" type="checkbox" checked>
        <span>Fondo blanco</span>
      </label>
    </div>

    <div class="d-viewport">
      <video id="video" playsinline autoplay muted></video>
      <canvas id="canvas"></canvas>

      <div class="d-overlay" aria-hidden="true">
        <div class="d-oval"></div>
      </div>

      <div class="d-loading" id="loading">
        <div class="d-spinner"></div>
        <div id="loadingText">Iniciando cámara…</div>
      </div>
    </div>

    <div class="d-actions" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
      <button class="d-btn d-btn--primary" id="btnStart">Iniciar cámara</button>
      <button class="d-btn" id="btnShot" disabled>Tomar foto</button>
      <button class="d-btn d-btn--success" id="btnSave" disabled>Guardar</button>
    </div>

    <div class="d-status" id="status">Listo. Presiona “Iniciar cámara”.</div>

    <div style="margin-top:12px;">
      <div class="d-side__title">Vista previa</div>
      <div class="d-preview"><img id="preview" alt="Vista previa"></div>
      <div class="d-meta" style="margin-top:10px;">
        <div class="d-meta__row">
          <span class="d-meta__label">Resultado</span>
          <span class="d-meta__value" id="saveInfo">—</span>
        </div>
      </div>
    </div>
  </section>

  <!-- DERECHA: Cola de alumnos -->
  <aside class="d-panel">
    <div class="d-queueTop">
      <div class="d-panel__title" style="margin:0;">Cola (CAPTURA_FOTO)</div>
      <span class="d-badge" id="qCount"><?= count($queue ?? []) ?></span>
    </div>

    <input class="d-search" id="qSearch" placeholder="Buscar por nombre o control…">

    <ul class="d-queueList" id="queueList"></ul>
  </aside>

</section>

<div class="d-toast" id="toast" role="status" aria-live="polite"></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script id="cfg"
  data-save-url="<?= site_url('captura/guardar') ?>"
  data-csrf-name="<?= csrf_token() ?>"
  data-csrf-hash="<?= csrf_hash() ?>"
></script>

<script id="pageData" type="application/json">
<?= json_encode([
  'current' => $current ?? null,
  'queue'   => $queue ?? [],
], JSON_UNESCAPED_UNICODE) ?>
</script>

<script src="<?= base_url('assets/js/capture_queue.js') ?>"></script>
<?= $this->endSection() ?>