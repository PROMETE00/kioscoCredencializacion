<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tu turno</title>

  <style>
    body{ background:#0b1220; color:#e8eefc; margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu; }
    .wrap{ max-width:520px; margin:0 auto; padding:18px 14px 34px; }
    .card{ background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); border-radius:16px; padding:16px; box-shadow: 0 10px 30px rgba(0,0,0,.25); }
    .folio{ font-size:34px; font-weight:900; letter-spacing:1px; margin:6px 0 10px; }
    .muted{ color:rgba(232,238,252,.72); font-size:13px; line-height:1.35; }
    .qrbox{ display:flex; justify-content:center; margin:14px 0; }
    .pill{ display:inline-block; padding:6px 10px; border-radius:999px; background:rgba(59,130,246,.18); border:1px solid rgba(59,130,246,.35); font-size:12px; }
    a{ color:#93c5fd; word-break:break-all; }
    .btn{ display:block; margin-top:12px; width:100%; text-align:center; padding:12px 14px; border-radius:12px; background:#3b82f6; color:#fff; text-decoration:none; font-weight:800; }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="pill">Turno generado</div>
      <div class="folio"><?= esc($folio) ?></div>

      <div class="muted">
        <b><?= esc($nombre) ?></b><br>
        Expira: <?= esc($expira) ?>
      </div>

      <div class="qrbox">
        <div id="qr"></div>
      </div>

      <div class="muted">
        Enlace de seguimiento:<br>
        <a href="<?= esc($url) ?>"><?= esc($url) ?></a>
      </div>

      <a class="btn" href="<?= esc($url) ?>">Ver estado del turno</a>
      <a class="btn" style="background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.14);" href="<?= base_url('turno') ?>">Generar otro</a>
    </div>
  </div>

  <script>
    new QRCode(document.getElementById("qr"), {
      text: <?= json_encode($url) ?>,
      width: 220,
      height: 220,
      correctLevel: QRCode.CorrectLevel.M
    });
  </script>
</body>
</html>