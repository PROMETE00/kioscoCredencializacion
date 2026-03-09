<?= $this->extend('layouts/discere') ?>


<?= $this->section('head') ?>
  <link rel="stylesheet" href="<?= base_url('assets/css/admin-user-create.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<section class="d-card au-create">

  <div class="au-create__head">
    <div>
      <div class="au-create__title">Crear usuario</div>
    </div>

    <div class="au-create__headActions">
      <a class="d-btn au-btn--ghost" href="<?= site_url('admin/usuarios') ?>">Volver</a>
    </div>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="au-alert au-alert--error">
      <?= esc(session()->getFlashdata('error')) ?>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= site_url('admin/usuarios') ?>" class="au-form">
    <?= csrf_field() ?>

    <div class="au-grid">

      <label class="au-field">
        <span class="au-label">Usuario</span>
        <input class="au-input" name="usuario" value="<?= esc(old('usuario')) ?>" autocomplete="username" placeholder="Ej. firma, foto, admin...">
      </label>

      <label class="au-field">
        <span class="au-label">Nombre</span>
        <input class="au-input" name="nombre" value="<?= esc(old('nombre')) ?>" placeholder="Nombre completo o estación">
      </label>

      <label class="au-field">
        <span class="au-label">Email (opcional)</span>
        <input class="au-input" name="email" value="<?= esc(old('email')) ?>" autocomplete="email" placeholder="correo@dominio.com">
      </label>

      <label class="au-field">
        <span class="au-label">Rol</span>
        <select class="au-input" name="rol_id">
          <option value="">Selecciona…</option>
          <?php foreach (($roles ?? []) as $r): ?>
            <option value="<?= esc($r['id']) ?>"><?= esc($r['nombre'].' ('.$r['codigo'].')') ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label class="au-field">
        <span class="au-label">Contraseña</span>
        <input class="au-input" type="password" name="password" autocomplete="new-password" placeholder="Mínimo 6 caracteres">
      </label>

      <label class="au-field">
        <span class="au-label">Confirmar contraseña</span>
        <input class="au-input" type="password" name="password2" autocomplete="new-password" placeholder="Repite la contraseña">
      </label>

    </div>

    <div class="au-actions au-actions--split">
      <a class="d-btn au-btn--ghost" href="<?= site_url('admin/usuarios') ?>">Cancelar</a>
      <button class="d-btn d-btn--primary au-btn--primary" type="submit">Crear usuario</button>
    </div>
  </form>

</section>

<?= $this->endSection() ?>