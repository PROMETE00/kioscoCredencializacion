<?php
  $current = $current ?? [];
?>

<aside class="d-side d-panel d-person">
  <div class="d-card-title">Alumno en captura</div>

  <div class="d-meta">
    <div class="d-meta__row">
      <span class="d-meta__label">Nombre</span>
      <span class="d-meta__value" id="alNombre"><?= esc($current['nombre'] ?? '—') ?></span>
    </div>
    <div class="d-meta__row">
      <span class="d-meta__label">No. control</span>
      <span class="d-meta__value" id="alControl"><?= esc($current['no_control'] ?? '—') ?></span>
    </div>
    <div class="d-meta__row">
      <span class="d-meta__label">Carrera</span>
      <span class="d-meta__value" id="alCarrera"><?= esc($current['carrera'] ?? '—') ?></span>
    </div>
    <div class="d-meta__row">
      <span class="d-meta__label">Semestre</span>
      <span class="d-meta__value" id="alSemestre"><?= esc($current['semestre'] ?? '—') ?></span>
    </div>
    <div class="d-meta__row">
      <span class="d-meta__label">Estatus</span>
      <span class="d-meta__value" id="alEstatus"><?= esc($current['estatus'] ?? '—') ?></span>
    </div>
  </div>

  <input type="hidden" id="studentId" value="<?= esc($current['alumno_id'] ?? $current['id'] ?? '') ?>">
  <input type="hidden" id="turnoId" value="<?= esc($current['turno_id'] ?? '') ?>">
</aside>
