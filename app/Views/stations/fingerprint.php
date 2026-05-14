<?= $this->extend('layouts/discere') ?>
<?php $this->setVar('activeMenu', 'fingerprint'); ?>

<?= $this->section('bodyClass') ?>capture-dark<?= $this->endSection() ?>

<?= $this->section('head') ?>
  <link rel="stylesheet" href="<?= base_url('assets/css/capture-layout.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/capture-common.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/huella.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="d-capture-wrap">
  <section class="d-grid-3">

    <!-- LEFT: Student Panel -->
    <?= $this->include('partials/capture/panel_alumno') ?>

    <!-- CENTER: Fingerprint Capture -->
    <?= $this->include('partials/huella/panel_huella') ?>

    <!-- RIGHT: Fingerprint Queue -->
    <?= $this->include('partials/huella/panel_cola') ?>

  </section>
</div>

<div class="d-toast" id="toast" role="status" aria-live="polite"></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script id="cfg"
  data-csrf-name="<?= csrf_token() ?>"
  data-csrf-hash="<?= csrf_hash() ?>"
  data-cola-url="<?= site_url('stations/fingerprint/queue') ?>"
  data-guardar-url="<?= site_url('stations/fingerprint/save') ?>"
  data-alumno-url="<?= site_url('stations/fingerprint/student') ?>"
></script>

<script src="<?= base_url('assets/js/huella-ui.js') ?>"></script>
<?= $this->endSection() ?>
