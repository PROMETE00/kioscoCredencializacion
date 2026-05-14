<?= $this->extend('layouts/discere') ?>
<?php $this->setVar('activeMenu', 'signature'); ?>

<?= $this->section('head') ?>
  <link rel="stylesheet" href="<?= base_url('assets/css/firma.css') ?>">
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
    colaUrl: "<?= site_url('stations/signature/queue') ?>",
    guardarUrl: "<?= site_url('stations/signature/save') ?>",
    alumnoUrl: "<?= site_url('stations/signature/student') ?>",
    csrfName: "<?= csrf_token() ?>",
    csrfHash: "<?= csrf_hash() ?>",
  };
</script>
<script src="<?= base_url('assets/js/firma-ui.js') ?>"></script>
<?= $this->endSection() ?>
