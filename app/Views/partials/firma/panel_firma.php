<section class="d-center">
  <div class="d-card">

    <div class="d-actions d-actions--3" style="margin-bottom: 12px;">
      <button type="button" class="d-btn" id="btnClear" disabled>Borrar</button>
      <button type="button" class="d-btn d-btn--primary" id="btnSave" disabled>Confirmar captura</button>
    </div>

    <div class="d-pad-wrap">
      <canvas id="sigCanvas" class="d-pad"></canvas>
    </div>

<div class="d-side__title">Vista previa</div>

<div class="d-preview">
  <img id="sigPreview" alt="Vista previa" style="display:none;">
  <div id="sigPreviewEmpty" class="d-preview-empty"></div>
</div>

<div class="d-meta" style="margin-top: 12px;">
  <div class="d-meta__row">
    <span class="d-meta__label">Resultado</span>
    <span class="d-meta__value" id="saveInfo">—</span>
  </div>
</div>
  </div>
</section>
