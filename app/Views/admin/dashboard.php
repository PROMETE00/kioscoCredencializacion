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
            <th>Alumno</th>
            <th>Turno</th>
            <th>Biométricos</th>
           <!-- <th>Estatus</th> -->
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="dashboardRows">
        <?php if (empty($worklist)): ?>
          <tr><td colspan="5" class="d-empty">No se encontraron alumnos para este filtro.</td></tr>
        <?php else: ?>
          <?php foreach ($worklist as $r): ?>
            <tr>
              <td>
                <div class="d-row-title"><?= esc($r['name']) ?></div>
                <div class="d-row-sub mono"><?= esc($r['identifier']) ?></div>
                <div class="d-row-sub"><?= esc($r['career']) ?> · <?= esc($r['campus']) ?></div>
              </td>
              <td>
                <div class="d-row-title"><?= esc($r['folio'] ?: 'Sin turno activo') ?></div>
                <div class="d-row-sub"><?= esc($r['stage_name']) ?></div>
                <div class="d-row-sub mono"><?= esc((string) ($r['updated_at'] ?? '—')) ?></div>
              </td>
              <td>
                <div class="d-checklist">
                  <?php 
                  $biometrics = [
                    ['Foto', !empty($r['has_photo'])],
                    ['Firma', !empty($r['has_signature'])],
                    ['Huella', !empty($r['has_fingerprint'])],
                  ]; 
                  foreach($biometrics as [$label, $ready]): ?>
                    <div class="d-checklist-item <?= $ready ? 'is-ready' : '' ?>">
                      <div class="d-checklist-icon">
                        <?php if($ready): ?>
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <?php else: ?>
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/></svg>
                        <?php endif; ?>
                      </div>
                      <span><?= esc($label) ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              </td>
              <!-- <td>
                <div class="d-actions-group">
                  <select class="d-select" data-role="status-select" <?=!empty($r['ticket_id']) ? '' : 'disabled' ?>>
                    <?php foreach (($statusOptions ?? []) as $statusOption): ?>
                      <option value="<?= esc((string) $statusOption['id']) ?>" <?= (int) ($statusOption['id'] ?? 0) === (int) ($r['status_id'] ?? 0) ? 'selected' : '' ?>>
                        <?= esc($statusOption['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button class="d-btn d-btn--primary" data-action="save-status" <?= !empty($r['ticket_id']) ? '' : 'disabled' ?>>Guardar estatus</button>
                </div>
              </td> -->
              <td>
                <div class="d-actions-column">
                  <button class="d-btn d-btn--danger" data-action="clear-biometric" data-type="photo" <?= !empty($r['ticket_id']) && !empty($r['has_photo']) ? '' : 'disabled' ?>>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> Borrar foto
                  </button>
                  <button class="d-btn d-btn--danger" data-action="clear-biometric" data-type="signature" <?= !empty($r['ticket_id']) && !empty($r['has_signature']) ? '' : 'disabled' ?>>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> Borrar firma
                  </button>
                  <button class="d-btn d-btn--danger" data-action="clear-biometric" data-type="fingerprint" <?= !empty($r['ticket_id']) && !empty($r['has_fingerprint']) ? '' : 'disabled' ?>>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> Borrar huella
                  </button>
                </div>
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
