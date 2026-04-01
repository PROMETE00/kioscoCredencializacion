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
    <div class="d-subhint">La tabla se actualiza automáticamente al escribir. Se cargan 8 alumnos por consulta para mantenerla ligera.</div>
  </div>
</div>

<section class="d-main-grid d-main-grid--single">
  <div class="d-card d-worklist">
    <div class="d-worklist__top">
      <div>
        <div class="d-card-title">Panel general administrativo</div>
        <div class="d-subhint" id="dashboardMeta">Mostrando los primeros <?= esc((string) count($worklist)) ?> alumnos encontrados.</div>
      </div>
    </div>

    <div class="d-tablewrap">
      <table class="d-table">
        <thead>
          <tr>
            <th>Alumno</th>
            <th>Turno</th>
            <th>Biométricos</th>
            <th>Estatus</th>
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
                <div class="d-badge-group">
                  <span class="d-badge <?= !empty($r['has_photo']) ? 'd-badge--success' : 'd-badge--muted' ?>">Foto</span>
                  <span class="d-badge <?= !empty($r['has_signature']) ? 'd-badge--success' : 'd-badge--muted' ?>">Firma</span>
                  <span class="d-badge <?= !empty($r['has_fingerprint']) ? 'd-badge--success' : 'd-badge--muted' ?>">Huella</span>
                </div>
              </td>
              <td>
                <div class="d-actions-group">
                  <select class="d-select" data-role="status-select" <?= !empty($r['ticket_id']) ? '' : 'disabled' ?>>
                    <?php foreach (($statusOptions ?? []) as $statusOption): ?>
                      <option value="<?= esc((string) $statusOption['id']) ?>" <?= (int) ($statusOption['id'] ?? 0) === (int) ($r['status_id'] ?? 0) ? 'selected' : '' ?>>
                        <?= esc($statusOption['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button class="d-btn d-btn--primary" data-action="save-status" <?= !empty($r['ticket_id']) ? '' : 'disabled' ?>>Guardar estatus</button>
                </div>
              </td>
              <td>
                <div class="d-actions-group">
                  <button class="d-btn d-btn--danger" data-action="clear-biometric" data-type="photo" <?= !empty($r['ticket_id']) && !empty($r['has_photo']) ? '' : 'disabled' ?>>Borrar foto</button>
                  <button class="d-btn d-btn--danger" data-action="clear-biometric" data-type="signature" <?= !empty($r['ticket_id']) && !empty($r['has_signature']) ? '' : 'disabled' ?>>Borrar firma</button>
                  <button class="d-btn d-btn--danger" data-action="clear-biometric" data-type="fingerprint" <?= !empty($r['ticket_id']) && !empty($r['has_fingerprint']) ? '' : 'disabled' ?>>Borrar huella</button>
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
