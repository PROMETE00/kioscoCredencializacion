<?= $this->extend('layouts/discere') ?>
<?php $this->setVar('activeMenu', 'Dashboard'); ?>

<?= $this->section('head') ?>
  <link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-topbar">
  <div class="d-card d-searchbar">
    <div class="d-searchbar__form">
      <input
        id="dashboardSearch"
        class="d-searchbar__input"
        placeholder="Buscar por número de control, ficha, nombre, carrera o folio…"
        autocomplete="off"
      >
    </div>
  </div>
</div>

<section class="d-main-grid d-main-grid--single">
  <div class="d-card d-worklist">
    <div class="d-tablewrap">
      <table class="d-table">
        <thead>
          <tr>
            <th>#FICHA</th>
            <th>CURP</th>
            <th>NOMBRE</th>
            <th>CARRERA</th>
            <th>CAMPUS</th>
            <th>FOTO</th>
            <th>FIRMA</th>
            <th>IMPRIME</th>
          </tr>
        </thead>
        <tbody id="dashboardRows">
        <?php if (empty($worklist)): ?>
          <tr><td colspan="8" class="d-empty">No se encontraron alumnos para este filtro.</td></tr>
        <?php else: ?>
          <?php foreach ($worklist as $r): ?>
            <tr>
              <td class="mono"><?= esc($r['identifier']) ?></td>
              <td><?= esc($r['curp'] ?? 'N/A') ?></td>
              <td><?= esc($r['name']) ?></td>
              <td><small><?= esc($r['career']) ?></small></td>
              <td><small><?= esc($r['campus']) ?></small></td>
              <td style="text-align: center;">
                <div style="display: flex; gap: 4px; align-items: center; justify-content: center;">
                  <?php if (!empty($r['has_photo'])): ?>
                    <span style="color: #10B981; font-weight: bold;">✓</span>
                  <?php else: ?>
                    <span style="color: #9CA3AF;">—</span>
                  <?php endif; ?>
                  <button type="button" class="d-btn d-btn--danger" style="padding: 4px; min-width: unset; height: auto;" data-action="clear-biometric" data-type="photo" <?= !empty($r['ticket_id']) && !empty($r['has_photo']) ? '' : 'disabled' ?> title="Borrar foto">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                  </button>
                </div>
              </td>
              <td style="text-align: center;">
                <div style="display: flex; gap: 4px; align-items: center; justify-content: center;">
                  <?php if (!empty($r['has_signature'])): ?>
                    <span style="color: #10B981; font-weight: bold;">✓</span>
                  <?php else: ?>
                    <span style="color: #9CA3AF;">—</span>
                  <?php endif; ?>
                  <button type="button" class="d-btn d-btn--danger" style="padding: 4px; min-width: unset; height: auto;" data-action="clear-biometric" data-type="signature" <?= !empty($r['ticket_id']) && !empty($r['has_signature']) ? '' : 'disabled' ?> title="Borrar firma">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                  </button>
                </div>
              </td>
              <td style="text-align: center;">
                <a href="<?= base_url('admin/credencial/imprimir/' . esc((string) ($r['student_id'] ?? ''))) ?>" target="_blank" class="d-btn d-btn--primary" style="padding: 6px 10px; font-size: 13px; white-space: nowrap;">🖨️ Imprimir</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<div class="d-toast" id="dashboardToast" role="status" aria-live="polite"></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
  <script id="dashboardConfig"
    data-fetch-url="<?= site_url('admin/dashboard/alumnos') ?>"
    data-status-url="<?= site_url('admin/dashboard/estatus') ?>"
    data-clear-url="<?= site_url('admin/dashboard/biometrico/eliminar') ?>"
    data-csrf-name="<?= csrf_token() ?>"
    data-csrf-hash="<?= csrf_hash() ?>"
  ></script>
  <script id="dashboardState" type="application/json"><?= json_encode([
      'items' => $worklist ?? [],
      'statusOptions' => $statusOptions ?? [],
      'kpis' => $kpis ?? [],
  ], JSON_UNESCAPED_UNICODE) ?></script>
  <script src="<?= base_url('assets/js/dashboard.js') ?>"></script>
<?= $this->endSection() ?>
