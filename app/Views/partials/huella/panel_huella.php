<section class="d-card d-huella">
  <div class="d-meta" style="margin-bottom:12px;">
    <div class="d-meta__row">
      <span class="d-meta__label">Status</span>
      <span class="d-meta__value" id="fpStatus">Select a student to start fingerprint capture.</span>
    </div>
  </div>


  <!-- Stepper -->
  <div class="d-huella__steps" aria-label="Progreso">
    <div class="d-step is-active" id="stSelect">
      <span class="d-step__dot"></span>
      <span class="d-step__txt">Seleccionar</span>
    </div>
    <div class="d-step" id="stScan">
      <span class="d-step__dot"></span>
      <span class="d-step__txt">Escanear</span>
    </div>
    <div class="d-step" id="stSave">
      <span class="d-step__dot"></span>
      <span class="d-step__txt">Guardar</span>
    </div>
  </div>

  <div class="d-huella__body">

    <!-- Panel de lectura -->
    <div class="d-huella__scan">

      <div class="d-huella__screen">
        <img id="fpPreview" alt="Vista previa huella" style="display:none;">
        <div id="fpPreviewEmpty" class="d-huella__empty">
          <div class="d-huella__emptyTitle">Listo para escanear</div>
          <div class="d-huella__emptyText">Coloca el dedo en el lector.</div>
        </div>
      </div>

      <!-- “Calidad” (placeholder visual; tu JS puede actualizarlo) -->
      <div class="d-huella__quality">
        <div class="d-huella__qTop">
          <span class="d-huella__qLbl">Calidad</span>
          <span class="d-huella__qVal" id="fpQualityText">—</span>
        </div>
        <div class="d-huella__qBar">
          <div class="d-huella__qFill" id="fpQualityFill" style="width:0%;"></div>
        </div>
        <div class="d-huella__qHint">Meta: ≥ 70% para evitar rechazos.</div>
      </div>

    </div>

    <!-- Acciones -->
    <div class="d-actions d-actions--huella">
      <button class="d-btn d-btn--primary" id="btnStart">Iniciar</button>
      <button class="d-btn" id="btnRetry" disabled>Reintentar</button>
      <button class="d-btn d-btn--success" id="btnSave" disabled>Guardar</button>
    </div>

    <!-- Resultado -->
    <div class="d-meta" style="margin-top:12px;">
      <div class="d-meta__row">
        <span class="d-meta__label">Resultado</span>
        <span class="d-meta__value" id="saveInfo">—</span>
      </div>
    </div>

  </div>

</section>
