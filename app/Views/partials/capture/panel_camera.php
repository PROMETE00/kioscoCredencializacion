<?php
  $overlay = $overlay ?? false; // true si quieres el círculo
  $title = $title ?? 'Cámara';
?>

<section class="d-stage d-camera">
  <div class="d-card__header">
    <div class="d-card__title"><!--?= esc($title) ?--></div>

    <label class="d-toggle">
      <input id="chkWhiteBg" type="checkbox">
      <span>Fondo blanco</span>
    </label>
  </div>

  <div class="d-viewport">
    <video id="video" playsinline autoplay muted></video>
    <canvas id="canvas"></canvas>

    <?php if ($overlay): ?>
      <div class="d-overlay" aria-hidden="true">
        <div class="d-oval"></div>
      </div>
    <?php endif; ?>

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

<div class="d-side__title">Vista previa</div>
<div class="d-preview">
  <img id="preview">
</div>

  <div class="d-meta" style="margin-top: 12px;">
    <div class="d-meta__row">
      <span class="d-meta__label">Resultado</span>
      <span class="d-meta__value" id="saveInfo">—</span>
    </div>
  </div>
</section>
