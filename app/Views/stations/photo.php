<?= $this->extend('layouts/discere') ?>
<?php $this->setVar('activeMenu', 'photo'); ?>

<?= $this->section('head') ?>
  <link rel="stylesheet" href="<?= base_url('assets/css/capture-queue.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/capture-common.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="d-capture-wrap">
  <section class="d-grid-3">

  <!-- LEFT: Student Data -->
  <?= $this->include('partials/capture/panel_alumno') ?>

  <!-- CENTER: Camera + preview + buttons -->
  <?= $this->include('partials/capture/panel_camera') ?>

  <!-- RIGHT: Student Queue -->
  <?= $this->include('partials/capture/panel_cola') ?>

  </section>

</div>

<div class="d-toast" id="toast" role="status" aria-live="polite"></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script id="cfg"
  data-save-url="<?= site_url('stations/photo/save') ?>"
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
