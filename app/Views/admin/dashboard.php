<?= $this->extend('layouts/discere') ?>
<?php $this->setVar('activeMenu', 'Dashboard'); ?>

<?= $this->section('head') ?>
  <link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="d-toolbar">
</div>

<div class="d-topbar">
<div class="d-card d-searchbar d-searchbar--center">
  <form method="get" action="<?= site_url('admin/dashboard') ?>" class="d-searchbar__form">
    <input
      name="q"
      value="<?= esc($q ?? '') ?>"
      class="d-searchbar__input"
      placeholder="Buscar por No. control, CURP, nombre o carrera…"
      autocomplete="off"
    >
    <!-- Fijamos stage=hoy para que siempre sea atendidos -->
    <input type="hidden" name="stage" value="hoy">
  </form>
</div>

<a class="d-kpi d-kpi--side is-active" href="<?= site_url('admin/dashboard?stage=hoy&q='.urlencode($q ?? '')) ?>">
    <div class="d-kpi__label">Atendidos hoy</div>
    <div class="d-kpi__value"><?= esc((string)($kpis['atendidos_hoy'] ?? 0)) ?></div>
    <div class="d-kpi__sub">Ver tabla</div>
  </a>
</div>

<!-- TABLA (sin estaciones) -->
<section class="d-main-grid d-main-grid--single">

  <div class="d-card d-worklist">
    <div class="d-tablewrap">
      <table class="d-table">
        <thead>
          <tr>
            <th>No. control</th>
            <th>CURP</th>
            <th>Nombre</th>
            <th>Carrera</th>
            <th>Campus</th>
            <th>Foto</th>
            <th>Firma</th>
            <th>Imprime</th>
            <th>Último</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($worklist)): ?>
          <tr><td colspan="10" class="d-empty">Sin alumnos atendidos para este filtro.</td></tr>
        <?php else: ?>
          <?php
            $badge = function(string $s){
              $map = [
                'listo'     => 'd-badge d-badge--success',
                'pendiente' => 'd-badge d-badge--muted',
                'rechazado' => 'd-badge d-badge--danger',
                'proceso'   => 'd-badge d-badge--warn',
              ];
              return $map[$s] ?? 'd-badge d-badge--muted';
            };
          ?>
          <?php foreach ($worklist as $r): ?>
            <tr>
              <td class="mono"><?= esc($r['no_control']) ?></td>
              <td class="mono"><?= esc($r['curp']) ?></td>
              <td><?= esc($r['nombre']) ?></td>
              <td><?= esc($r['carrera']) ?></td>
              <td><?= esc($r['campus']) ?></td>
              <td><span class="<?= $badge($r['foto']) ?>"><?= esc($r['foto']) ?></span></td>
              <td><span class="<?= $badge($r['firma']) ?>"><?= esc($r['firma']) ?></span></td>
              <td><span class="<?= $badge($r['imprime']) ?>"><?= esc($r['imprime']) ?></span></td>
              <td class="mono"><?= esc($r['updated_at']) ?></td>
              <td>
                <!-- TODO: aquí conectas a tu expediente 360 -->
                <a class="d-btn d-btn--tiny" href="#">Abrir</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</section>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
  <script src="<?= base_url('assets/js/dashboard.js') ?>"></script>
<?= $this->endSection() ?>