<?= $this->extend('layouts/discere') ?>


<?= $this->section('head') ?>
  <link rel="stylesheet" href="<?= base_url('assets/css/admin-users.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<section class="d-card">
  <div class="au-head">
    <div>
      <div class="au-title">Usuarios</div>
    </div>

    <div class="au-actions">
      <div class="au-search">
        <input id="userSearch" class="au-search__input" placeholder="Buscar usuario, nombre o rol..." autocomplete="off">
      </div>
    </div>
  </div>

  <?php if (session()->getFlashdata('ok')): ?>
    <div class="au-alert au-alert--ok">
      <?= esc(session()->getFlashdata('ok')) ?>
    </div>
  <?php endif; ?>

  <div class="au-tablewrap">
    <table class="au-table" id="usersTable">
      <thead>
        <tr>
          <th class="au-col-id">ID</th>
          <th>Usuario</th>
          <th>Nombre</th>
          <th>Email</th>
          <th>Rol</th>
          <th class="au-col-status">Estado</th>
          <th class="au-col-date">Creado</th>
        </tr>
      </thead>

      <tbody>
        <?php if (empty($users ?? [])): ?>
          <tr><td colspan="7" class="au-empty">No hay usuarios.</td></tr>
        <?php else: ?>
          <?php foreach ($users as $u): ?>
            <?php
              $rol = $u['rol_nombre'] ?? ($u['rol_codigo'] ?? (string)($u['rol_id'] ?? '—'));
              $activo = ((int)($u['activo'] ?? 0) === 1);
            ?>
            <tr class="au-row"
                data-q="<?= esc(mb_strtolower(($u['usuario'] ?? '').' '.($u['nombre'] ?? '').' '.($u['email'] ?? '').' '.$rol)) ?>">
              <td class="mono au-id"><?= esc($u['id']) ?></td>

              <td class="mono">
                <div class="au-user">
                  <span class="au-user__avatar" aria-hidden="true"></span>
                  <div class="au-user__txt">
                    <div class="au-user__u"><?= esc($u['usuario']) ?></div>
                    <div class="au-user__meta">ID: <?= esc($u['id']) ?></div>
                  </div>
                </div>
              </td>

              <td><?= esc($u['nombre'] ?? '—') ?></td>

              <td class="au-muted">
                <?= esc($u['email'] ?? '—') ?>
              </td>

              <td>
                <span class="au-badge"><?= esc($rol) ?></span>
              </td>

              <td>
                <?php if ($activo): ?>
                  <span class="au-pill au-pill--on">Activo</span>
                <?php else: ?>
                  <span class="au-pill au-pill--off">Inactivo</span>
                <?php endif; ?>
              </td>

              <td class="mono au-muted"><?= esc($u['created_at'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="au-actions">
  <a class="d-btn d-btn--primary" href="<?= site_url('admin/usuarios/create') ?>">
    <span class="u-ico" aria-hidden="true">
      <img class="u-ico-img" src="<?= base_url('assets/img/userSVG.svg') ?>" alt="">
    </span>
    Crear usuario
  </a>
</div>
</section>

<script>
  // Búsqueda instantánea (sin backend)
  (function(){
    const input = document.getElementById('userSearch');
    const rows  = document.querySelectorAll('#usersTable tbody .au-row');
    if(!input || !rows.length) return;

    input.addEventListener('input', () => {
      const q = (input.value || '').trim().toLowerCase();
      rows.forEach(r => {
        const hay = r.getAttribute('data-q') || '';
        r.style.display = (!q || hay.includes(q)) ? '' : 'none';
      });
    });
  })();
</script>

<?= $this->endSection() ?>