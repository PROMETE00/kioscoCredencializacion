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