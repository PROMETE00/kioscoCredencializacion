<?= $this->extend('layouts/discere') ?>

<?= $this->section('head') ?>
  <link rel="stylesheet" href="<?= base_url('assets/css/firma.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="d-capture-wrap">
  <div class="d-capture-grid">

    <!-- IZQUIERDA: Alumno en captura -->
    <aside class="d-panel">
      <div class="d-card">
        <div class="d-card-title">Alumno en captura</div>

        <dl class="d-dl">
          <div class="d-dl-row">
            <dt>Nombre:</dt>
            <dd id="capNombre">—</dd>
          </div>

          <div class="d-dl-row">
            <dt>No. control:</dt>
            <dd id="capControl">—</dd>
          </div>

          <div class="d-dl-row">
            <dt>Carrera:</dt>
            <dd id="capCarrera">—</dd>
          </div>

          <div class="d-dl-row">
            <dt>Semestre:</dt>
            <dd id="capSemestre">—</dd>
          </div>

          <div class="d-dl-row">
            <dt>Estatus:</dt>
            <dd><span class="d-pill" id="capEstatus">—</span></dd>
          </div>
        </dl>

        <p class="d-tip">Tip: Selecciona un alumno de la cola para cargarlo aquí.</p>

        <!-- Estos IDs los llena el JS cuando seleccionas de la cola -->
        <input type="hidden" id="alumnoId" value="0">
        <input type="hidden" id="turnoId" value="0">
      </div>
    </aside>

    <!-- CENTRO: Captura firma -->
    <section class="d-center">
      <div class="d-card">
        <div class="d-card-head">
          <div class="d-card-title">Firma</div>

          <label class="d-check">
            <input type="checkbox" id="chkBg" checked>
            <span>Fondo blanco</span>
          </label>
        </div>

        <div class="d-pad-wrap">
          <canvas id="sigCanvas" class="d-pad"></canvas>
        </div>

        <div class="d-actions">
          <button type="button" class="d-btn" id="btnStart">Preparar</button>
          <button type="button" class="d-btn" id="btnClear" disabled>Borrar</button>
          <button type="button" class="d-btn d-btn--primary" id="btnSave" disabled>Guardar</button>
        </div>

        <div class="d-status" id="capStatus">
          Listo. Selecciona alumno de cola y captura firma.
        </div>

        <div class="d-subtitle">Vista previa</div>
        <div class="d-preview">
          <img id="sigPreview" alt="Vista previa" style="display:none;">
          <div id="sigPreviewEmpty" class="d-preview-empty">Resultado —</div>
        </div>
      </div>
    </section>

    <!-- DERECHA: Cola -->
    <aside class="d-panel">
      <div class="d-card">
        <div class="d-card-head">
          <div class="d-card-title">Cola (FIRMA)</div>
          <span class="d-badge" id="colaCount">0</span>
        </div>

        <input class="d-input" id="colaSearch" placeholder="Buscar por nombre o control..." />

        <div class="d-queue" id="colaList">
          <!-- items por JS -->
        </div>
      </div>
    </aside>

  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  window.__FIRMA__ = {
    colaUrl: "<?= site_url('captura/firma/cola') ?>",
    guardarUrl: "<?= site_url('captura/firma/guardar') ?>",
    // opcional: si quieres endpoint para traer alumno por turno:
    alumnoUrl: "<?= site_url('captura/firma/alumno') ?>",
    csrfName: "<?= csrf_token() ?>",
    csrfHash: "<?= csrf_hash() ?>",
  };
</script>
<script src="<?= base_url('assets/js/firma-ui.js') ?>"></script>
<?= $this->endSection() ?>