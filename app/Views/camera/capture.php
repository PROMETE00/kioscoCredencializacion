<?= $this->extend('layouts/discere') ?>

<?= $this->section('head') ?>
  <!-- MediaPipe -->
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation/selfie_segmentation.js"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<section class="d-card">
  <div class="d-card__header">
    <div class="d-card__title">Captura de fotografía</div>

    <label class="d-toggle">
      <input id="chkWhiteBg" type="checkbox" checked>
      <span>Fondo blanco</span>
    </label>
  </div>

  <!-- ✅ 3 columnas: Alumno (izq) / Cámara (centro) / Cola (der) -->
  <div class="d-grid d-grid--wide">

    <!-- ===== Col IZQ: Alumno en captura ===== -->
    <aside class="d-side d-person">
      <div class="d-side__title">Alumno en captura</div>

      <div class="d-meta">
        <div class="d-meta__row">
          <span class="d-meta__label">Nombre</span>
          <span class="d-meta__value" id="alNombre">—</span>
        </div>
        <div class="d-meta__row">
          <span class="d-meta__label">No. control</span>
          <span class="d-meta__value" id="alControl">—</span>
        </div>
        <div class="d-meta__row">
          <span class="d-meta__label">Carrera</span>
          <span class="d-meta__value" id="alCarrera">—</span>
        </div>
        <div class="d-meta__row">
          <span class="d-meta__label">Semestre</span>
          <span class="d-meta__value" id="alSemestre">—</span>
        </div>
        <div class="d-meta__row">
          <span class="d-meta__label">Estatus</span>
          <span class="d-meta__value" id="alEstatus">—</span>
        </div>

        <div class="d-tipbox">
          <div class="d-tipbox__title">Tip</div>
          Selecciona un alumno de la cola para cargarlo aquí.
        </div>
      </div>
    </aside>

    <!-- ===== Col CENTRO: Cámara ===== -->
    <div class="d-stage d-camera">
      <div class="d-card__title" style="margin: 0 0 10px;">Cámara</div>

      <div class="d-viewport">
        <video id="video" playsinline autoplay muted></video>
        <canvas id="canvas"></canvas>

        <!-- ✅ SIN CÍRCULO: se elimina el overlay -->
        <!-- <div class="d-overlay" aria-hidden="true">
          <div class="d-oval"></div>
        </div> -->

        <div class="d-loading" id="loading">
          <div class="d-spinner"></div>
          <div id="loadingText">Iniciando cámara…</div>
        </div>
      </div>

      <div class="d-actions">
        <button class="d-btn d-btn--primary" id="btnStart">Iniciar cámara</button>
        <button class="d-btn" id="btnShot" disabled>Tomar foto</button>
        <button class="d-btn d-btn--success" id="btnSave" disabled>Guardar</button>
      </div>

      <div class="d-status" id="status">Listo. Inicia cámara, selecciona alumno de cola y captura.</div>

      <!-- Preview abajo (si quieres conservarlo aquí en centro) -->
      <div class="d-side__title" style="margin-top: 14px;">Vista previa</div>
      <div class="d-preview">
        <img id="preview" alt="Vista previa">
      </div>

      <div class="d-meta" style="margin-top: 12px;">
        <div class="d-meta__row">
          <span class="d-meta__label">Resultado</span>
          <span class="d-meta__value" id="saveInfo">—</span>
        </div>
      </div>
    </div>

    <!-- ===== Col DER: Cola ===== -->
    <aside class="d-side d-queue">
      <div class="d-side__title">Cola (foto_registrada)</div>

      <div class="d-tipbox" style="margin-top: 0;">
        <input id="qSearch" type="text" placeholder="Buscar por nombre o control..."
               style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:10px; outline:none;">
      </div>

      <!-- Aquí luego pintas la lista con PHP o JS -->
      <div id="queueList" class="d-meta" style="margin-top: 12px;">
        <div class="d-tipbox">Sin alumnos en cola.</div>
      </div>
    </aside>

  </div>
</section>

<div class="d-toast" id="toast" role="status" aria-live="polite"></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script
  id="cfg"
  data-save-url="<?= site_url('captura/guardar') ?>"
  data-csrf-name="<?= csrf_token() ?>"
  data-csrf-hash="<?= csrf_hash() ?>"
></script>

<script src="<?= base_url('assets/js/capture.js') ?>"></script>
<?= $this->endSection() ?>