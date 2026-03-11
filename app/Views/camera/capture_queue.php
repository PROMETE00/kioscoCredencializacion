<?= $this->extend('layouts/discere') ?>
<?php $this->setVar('activeMenu', 'fotografia'); ?>

<?= $this->section('head') ?>
  <!-- CSS módulo + CSS común (reemplaza el <style> inline) -->
  <link rel="stylesheet" href="<?= base_url('assets/css/capture-queue.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/capture-common.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
  // Control de overlay (círculo). Cámbialo a true si lo quieres.
  $showOverlay = false;
?>

<div class="d-capture-wrap">
  <section class="d-grid-3">

  <!-- IZQUIERDA: Datos del alumno -->
  <?= $this->include('partials/capture/panel_alumno') ?>

  <!-- CENTRO: Cámara + preview + botones -->
  <?= $this->include('partials/capture/panel_camera') ?>

  <!-- DERECHA: Cola de alumnos -->
  <?= $this->include('partials/capture/panel_cola') ?>

  </section>

</div>



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
