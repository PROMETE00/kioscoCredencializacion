<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Estado del turno</title>
  <style>
    body{ background:#0b1220; color:#e8eefc; margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu; }
    .wrap{ max-width:520px; margin:0 auto; padding:18px 14px 34px; }
    .card{ background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); border-radius:16px; padding:16px; box-shadow: 0 10px 30px rgba(0,0,0,.25); }
    .big{ font-size:28px; font-weight:900; margin:0 0 10px; }
    .row{ margin:10px 0; }
    .k{ font-size:12px; color:rgba(232,238,252,.7); }
    .v{ font-size:16px; font-weight:700; }
    .btn{ display:block; margin-top:12px; width:100%; text-align:center; padding:12px 14px; border-radius:12px; background:#3b82f6; color:#fff; text-decoration:none; font-weight:800; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <?php if (!empty($notFound)): ?>
        <div class="big">Turno no encontrado</div>
        <div style="color:rgba(232,238,252,.75)">El enlace/QR no es válido o el turno ya no está activo.</div>
        <a class="btn" href="<?= base_url('turno') ?>">Generar turno</a>
      <?php else: ?>
        <div class="big"><?= esc($turno['folio']) ?></div>

        <div class="row">
          <div class="k">Estatus</div>
          <div class="v"><?= esc($turno['estatus'] ?? '-') ?></div>
        </div>

        <div class="row">
          <div class="k">Etapa</div>
          <div class="v"><?= esc($turno['etapa'] ?? '-') ?></div>
        </div>

        <div class="row">
          <div class="k">Creado</div>
          <div class="v"><?= esc($turno['creado_at'] ?? '-') ?></div>
        </div>

        <div class="row">
          <div class="k">Llamado</div>
          <div class="v"><?= esc($turno['llamado_at'] ?? 'Aún no') ?></div>
        </div>

        <div class="row">
          <div class="k">Expira</div>
          <div class="v"><?= esc($turno['fecha_expira'] ?? '-') ?></div>
        </div>

        <a class="btn" href="<?= base_url('turno') ?>">Volver</a>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>