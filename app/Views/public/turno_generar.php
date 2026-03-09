<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Generar Turno</title>

  <!-- Si ya tienes tu CSS del proyecto, cámbialo aquí -->
  <link rel="stylesheet" href="<?= base_url('assets/css/discere-theme.css') ?>">

  <style>
    body{ background:#0b1220; color:#e8eefc; margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, "Helvetica Neue", Arial; }
    .wrap{ max-width:520px; margin:0 auto; padding:18px 14px 34px; }
    .card{ background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); border-radius:16px; padding:16px; box-shadow: 0 10px 30px rgba(0,0,0,.25); }
    .h1{ font-size:20px; font-weight:800; margin:0 0 6px; letter-spacing:.2px; }
    .muted{ color:rgba(232,238,252,.72); font-size:13px; line-height:1.35; }
    .err{ background:rgba(255,80,80,.12); border:1px solid rgba(255,80,80,.28); padding:10px 12px; border-radius:12px; margin:12px 0; }
    label{ display:block; font-size:13px; margin:12px 0 6px; color:rgba(232,238,252,.85); }
    input{
      width:100%; box-sizing:border-box;
      border-radius:12px; border:1px solid rgba(255,255,255,.14);
      background:rgba(0,0,0,.25);
      color:#e8eefc; padding:12px 12px; font-size:16px;
      outline:none;
    }
    input:focus{ border-color: rgba(120,170,255,.8); }
    .btn{
      width:100%; margin-top:14px;
      border:0; border-radius:12px;
      padding:12px 14px; font-weight:800; font-size:16px;
      background: #3b82f6; color:white; cursor:pointer;
    }
    .mini{ margin-top:12px; font-size:12px; color:rgba(232,238,252,.65); }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1 class="h1">Generar turno</h1>
      <div class="muted">Escribe tu <b>No. de control</b> o <b>No. de ficha</b>. Se generará un QR para dar seguimiento desde tu celular.</div>

      <?php if (!empty($error)): ?>
        <div class="err"><?= esc($error) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= base_url('turno') ?>">
        <?= csrf_field() ?>

        <label for="identificador">No. de control / ficha</label>
        <input
          id="identificador"
          name="identificador"
          value="<?= esc(old('identificador') ?? '') ?>"
          placeholder="Ej. 20161234 o 12345"
          inputmode="numeric"
          autocomplete="off"
          required
        >

        <button class="btn" type="submit">Generar QR</button>

        <div class="mini">
          * Tu turno expira al final del día. Guarda el QR o el enlace que se mostrará.
        </div>
      </form>
    </div>
  </div>
  <div style="margin-top:14px; text-align:center;">
  <a href="<?= base_url('admin/login') ?>"
     style="display:inline-block; padding:10px 14px; border-radius:12px;
            background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.14);
            color:#e8eefc; text-decoration:none; font-weight:800;">
    Administración / Iniciar sesión
  </a>
</div>
</body>
</html>