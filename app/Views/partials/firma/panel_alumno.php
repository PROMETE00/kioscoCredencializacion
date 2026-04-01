<?php
  $current = $current ?? [];
?>
<aside class="d-panel d-person d-side">
  
    <div class="d-card-title">Alumno en captura</div>

    <div class="d-meta">
      <div class="d-meta__row">
        <span class="d-meta__label">Nombre</span>
        <span class="d-meta__value" id="capNombre"><?= esc($current['name'] ?? '—') ?></span>
      </div>

      <div class="d-meta__row">
        <span class="d-meta__label">No. control</span>
        <span class="d-meta__value" id="capControl"><?= esc($current['control_number'] ?? '—') ?></span>
      </div>

      <div class="d-meta__row">
        <span class="d-meta__label">Carrera</span>
        <span class="d-meta__value" id="capCarrera"><?= esc($current['career'] ?? '—') ?></span>
      </div>

      <div class="d-meta__row">
        <span class="d-meta__label">Semestre</span>
        <span class="d-meta__value" id="capSemestre"><?= esc($current['semester'] ?? '—') ?></span>
      </div>

      <div class="d-meta__row">
        <span class="d-meta__label">Estatus</span>
        <span class="d-meta__value" id="capEstatus"><?= esc($current['status'] ?? '—') ?></span>
      </div>
    </div>

    <!-- CLAVE: hidratar si viene current -->
    <input type="hidden" id="alumnoId" value="<?= esc($current['id'] ?? 0) ?>">
    <input type="hidden" id="turnoId" value="<?= esc($current['ticket_id'] ?? 0) ?>">

    <div class="d-status" id="capStatus" style="margin-top:12px;">
      <?= !empty($current) ? 'Alumno listo para capturar firma. Dibuja y confirma la captura.' : 'Selecciona un alumno de la cola para comenzar la captura.' ?>
    </div>

</aside>
