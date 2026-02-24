<?= $this->extend('layouts/discere') ?>
<?php $this->setVar('activeMenu', 'firma'); ?>

<?= $this->section('head') ?>
  <link rel="stylesheet" href="<?= base_url('assets/css/firma.css') ?>">
  <!-- si quieres reutilizar layout/override estilo foto -->
  <link rel="stylesheet" href="<?= base_url('assets/css/capture-common.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="d-capture-wrap">
  <div class="d-grid-3">

    <?= $this->include('partials/firma/panel_alumno') ?>
    <?= $this->include('partials/firma/panel_firma') ?>
    <?= $this->include('partials/firma/panel_cola') ?>

  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  window.__FIRMA__ = {
    colaUrl: "<?= site_url('captura/firma/cola') ?>",
    guardarUrl: "<?= site_url('captura/firma/guardar') ?>",
    alumnoUrl: "<?= site_url('captura/firma/alumno') ?>",
    csrfName: "<?= csrf_token() ?>",
    csrfHash: "<?= csrf_hash() ?>",
  };
</script>
<script src="<?= base_url('assets/js/firma-ui.js') ?>"></script>
<?= $this->endSection() ?>