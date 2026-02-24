<?= $this->extend('layouts/discere') ?>
<?php $this->setVar('activeMenu', 'dashboard'); ?>

<?= $this->section('content') ?>

<section class="d-card">
  <div class="d-card__header">
    <div class="d-card__title">Crear usuario</div>
    <a class="d-btn" href="<?= site_url('admin/usuarios') ?>">Volver</a>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="d-status" style="border-style:solid; border-color: rgba(239,68,68,.25); background: rgba(239,68,68,.08);">
      <?= esc(session()->getFlashdata('error')) ?>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= site_url('admin/usuarios') ?>" style="margin-top:12px;">
    <?= csrf_field() ?>

    <div class="d-meta__row">
      <span class="d-meta__label">Usuario</span>
      <input name="usuario" value="<?= esc(old('usuario')) ?>" style="width:60%; padding:10px; border:1px solid var(--border); border-radius:10px;">
    </div>

    <div class="d-meta__row" style="margin-top:10px;">
      <span class="d-meta__label">Nombre</span>
      <input name="nombre" value="<?= esc(old('nombre')) ?>" style="width:60%; padding:10px; border:1px solid var(--border); border-radius:10px;">
    </div>

    <div class="d-meta__row" style="margin-top:10px;">
      <span class="d-meta__label">Email</span>
      <input name="email" value="<?= esc(old('email')) ?>" style="width:60%; padding:10px; border:1px solid var(--border); border-radius:10px;">
    </div>

    <div class="d-meta__row" style="margin-top:10px;">
      <span class="d-meta__label">Rol</span>
      <select name="rol_id" style="width:60%; padding:10px; border:1px solid var(--border); border-radius:10px;">
        <option value="">Selecciona…</option>
        <?php foreach (($roles ?? []) as $r): ?>
          <option value="<?= esc($r['id']) ?>"><?= esc($r['nombre'].' ('.$r['codigo'].')') ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="d-meta__row" style="margin-top:10px;">
      <span class="d-meta__label">Contraseña</span>
      <input type="password" name="password" style="width:60%; padding:10px; border:1px solid var(--border); border-radius:10px;">
    </div>

    <div class="d-meta__row" style="margin-top:10px;">
      <span class="d-meta__label">Confirmar</span>
      <input type="password" name="password2" style="width:60%; padding:10px; border:1px solid var(--border); border-radius:10px;">
    </div>

    <div style="margin-top:14px; display:flex; gap:10px;">
      <button class="d-btn d-btn--primary" type="submit">Crear</button>
      <a class="d-btn" href="<?= site_url('admin/usuarios') ?>">Cancelar</a>
    </div>
  </form>
</section>

<?= $this->endSection() ?>