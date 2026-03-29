<?= $this->extend('layouts/discere') ?>
<?php $this->setVar('activeMenu', 'Dashboard'); ?>

<?= $this->section('head') ?>
  <link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-topbar d-topbar--grid">
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

  <div class="d-kpi-grid" style="grid-template-columns: repeat(3, 1fr); gap: 1rem;">
    <div class="d-kpi is-active">
      <div class="d-kpi__label">Alumnos</div>
      <div class="d-kpi__value" id="kpiTotalAlumnos"><?= esc((string) ($kpis['total_alumnos'] ?? 0)) ?></div>
      <div class="d-kpi__sub">Registros en BD</div>
    </div>
    <div class="d-kpi is-active">
      <div class="d-kpi__label">Turnos Hoy</div>
      <div class="d-kpi__value" id="kpiTurnosHoy"><?= esc((string) ($kpis['turnos_hoy'] ?? 0)) ?></div>
      <div class="d-kpi__sub">Generados hoy</div>
    </div>
    <div class="d-kpi is-active" style="background-color: #ecfdf5; border-color: #10b981;">
      <div class="d-kpi__label" style="color: #065f46;">Completados Hoy</div>
      <div class="d-kpi__value" id="kpiCompletadosHoy" style="color: #047857;"><?= esc((string) ($kpis['completados_hoy'] ?? 0)) ?></div>
      <div class="d-kpi__sub" style="color: #065f46;">Trámites finalizados</div>
    </div>
    <div class="d-kpi">
      <div class="d-kpi__label">Fotos Hoy</div>
      <div class="d-kpi__value" id="kpiFotosHoy"><?= esc((string) ($kpis['fotos_hoy'] ?? 0)) ?></div>
      <div class="d-kpi__sub">Capturadas hoy</div>
    </div>
    <div class="d-kpi">
      <div class="d-kpi__label">Firmas Hoy</div>
      <div class="d-kpi__value" id="kpiFirmasHoy"><?= esc((string) ($kpis['firmas_hoy'] ?? 0)) ?></div>
      <div class="d-kpi__sub">Capturadas hoy</div>
    </div>
    <div class="d-kpi">
      <div class="d-kpi__label">Huellas Hoy</div>
      <div class="d-kpi__value" id="kpiHuellasHoy"><?= esc((string) ($kpis['huellas_hoy'] ?? 0)) ?></div>
      <div class="d-kpi__sub">Capturadas hoy</div>
    </div>
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
                <div class="d-row-title"><?= esc($r['nombre']) ?></div>
                <div class="d-row-sub mono"><?= esc($r['identificador']) ?><?= !empty($r['numero_ficha']) ? ' · Ficha ' . esc($r['numero_ficha']) : '' ?></div>
                <div class="d-row-sub"><?= esc($r['carrera']) ?> · <?= esc($r['campus']) ?></div>
              </td>
              <td>
                <div class="d-row-title"><?= esc($r['folio'] ?: 'Sin turno activo') ?></div>
                <div class="d-row-sub"><?= esc($r['etapa_nombre']) ?></div>
                <div class="d-row-sub mono"><?= esc((string) ($r['updated_at'] ?? '—')) ?></div>
              </td>
              <td>
                <div class="d-badge-group">
                  <span class="d-badge <?= !empty($r['has_foto']) ? 'd-badge--success' : 'd-badge--muted' ?>">Foto</span>
                  <span class="d-badge <?= !empty($r['has_firma']) ? 'd-badge--success' : 'd-badge--muted' ?>">Firma</span>
                  <span class="d-badge <?= !empty($r['has_huella']) ? 'd-badge--success' : 'd-badge--muted' ?>">Huella</span>
                </div>
              </td>
              <td>
                <div class="d-actions-group">
                  <select class="d-select" data-role="status-select" <?= !empty($r['turno_id']) ? '' : 'disabled' ?>>
                    <?php foreach (($statusOptions ?? []) as $statusOption): ?>
                      <option value="<?= esc((string) $statusOption['id_estatus']) ?>" <?= (int) ($statusOption['id_estatus'] ?? 0) === (int) ($r['estatus_id'] ?? 0) ? 'selected' : '' ?>>
                        <?= esc($statusOption['nombre']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button class="d-btn d-btn--primary" data-action="save-status" <?= !empty($r['turno_id']) ? '' : 'disabled' ?>>Guardar estatus</button>
                </div>
              </td>
              <td>
                <div class="d-actions-group">
                  <button class="d-btn d-btn--danger" data-action="clear-biometric" data-type="foto" <?= !empty($r['turno_id']) && !empty($r['has_foto']) ? '' : 'disabled' ?>>Borrar foto</button>
                  <button class="d-btn d-btn--danger" data-action="clear-biometric" data-type="firma" <?= !empty($r['turno_id']) && !empty($r['has_firma']) ? '' : 'disabled' ?>>Borrar firma</button>
                  <button class="d-btn d-btn--danger" data-action="clear-biometric" data-type="huella" <?= !empty($r['turno_id']) && !empty($r['has_huella']) ? '' : 'disabled' ?>>Borrar huella</button>
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
