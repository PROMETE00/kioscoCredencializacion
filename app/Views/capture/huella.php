<?= $this->extend('layouts/discere') ?>
<?php $this->setVar('activeMenu', 'huella'); ?>

<?= $this->section('bodyClass') ?>capture-dark<?= $this->endSection() ?>

<?= $this->section('head') ?>
  <link rel="stylesheet" href="<?= base_url('assets/css/capture-layout.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/capture-common.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/huella.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="d-capture-wrap">
  <section class="d-grid-3">

    <!-- IZQUIERDA: Panel alumno (reutiliza el mismo que Foto) -->
    <?= $this->include('partials/capture/panel_alumno') ?>

    <!-- CENTRO: Captura huella -->
    <?= $this->include('partials/huella/panel_huella') ?>

    <!-- DERECHA: Cola huella -->
    <?= $this->include('partials/huella/panel_cola') ?>

  </section>
</div>

<div class="d-toast" id="toast" role="status" aria-live="polite"></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script id="cfg"
  data-csrf-name="<?= csrf_token() ?>"
  data-csrf-hash="<?= csrf_hash() ?>"
  data-cola-url="<?= site_url('captura/huella/cola') ?>"
  data-guardar-url="<?= site_url('captura/huella/guardar') ?>"
  data-alumno-url="<?= site_url('captura/huella/alumno') ?>"
></script>

<script id="pageData" type="application/json">
<?= json_encode([
  'current' => $current ?? null,
  'queue'   => $queue ?? [],
], JSON_UNESCAPED_UNICODE) ?>
</script>

<script src="<?= base_url('assets/js/huella-ui.js') ?>"></script>
<?= $this->endSection() ?>
