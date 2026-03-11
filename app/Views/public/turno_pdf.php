<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Comprobante de turno</title>
  <style>
    body { font-family: Arial, sans-serif; color: #172033; margin: 24px; font-size: 13px; }
    .header { border-bottom: 2px solid #2f6df6; padding-bottom: 12px; margin-bottom: 18px; }
    .header table { width: 100%; border-collapse: collapse; }
    .brand-title { font-size: 20px; font-weight: bold; margin: 0 0 4px; }
    .brand-subtitle { color: #4b5563; margin: 0; }
    .section { margin-top: 18px; }
    .section h2 { font-size: 15px; margin: 0 0 10px; color: #0f172a; }
    .card { border: 1px solid #d7dfeb; border-radius: 10px; padding: 14px; background: #f8fbff; }
    .grid { width: 100%; border-collapse: collapse; }
    .grid td { width: 50%; vertical-align: top; padding: 6px 10px 6px 0; }
    .label { display: block; color: #64748b; font-size: 11px; text-transform: uppercase; margin-bottom: 4px; }
    .value { font-size: 13px; font-weight: bold; color: #172033; }
    .folio-box { margin: 14px 0 0; padding: 16px; text-align: center; border-radius: 12px; background: #eef4ff; border: 1px solid #cfe0ff; }
    .folio-box .label { margin-bottom: 6px; }
    .folio-value { font-size: 26px; font-weight: bold; color: #2347a8; letter-spacing: 2px; }
    .qr-wrap { text-align: center; margin-top: 18px; }
    .qr-wrap img { width: 180px; height: 180px; }
    .note { margin-top: 18px; padding: 12px 14px; border: 1px dashed #cbd5e1; background: #fcfdff; color: #475569; border-radius: 10px; line-height: 1.5; }
    .footer { margin-top: 20px; font-size: 11px; color: #64748b; text-align: center; }
  </style>
</head>
<body>
  <div class="header">
    <table>
      <tr>
        <td>
          <div class="brand-title">Instituto Tecnológico de Oaxaca</div>
          <p class="brand-subtitle">Comprobante de turno para credencialización</p>
        </td>
        <td style="text-align:right;">
          <?php if (!empty($logoImage)): ?>
            <img src="<?= esc($logoImage) ?>" alt="Logo" style="width:80px; height:auto;">
          <?php endif; ?>
        </td>
      </tr>
    </table>
  </div>

  <div class="folio-box">
    <span class="label">Folio de atención</span>
    <div class="folio-value"><?= esc($turno['folio'] ?? 'N/A') ?></div>
  </div>

  <div class="section">
    <h2>Datos del alumno</h2>
    <div class="card">
      <table class="grid">
        <tr>
          <td><span class="label">Nombre completo</span><span class="value"><?= esc($turno['nombre_completo'] ?? 'N/A') ?></span></td>
          <td><span class="label">No. de control / ficha</span><span class="value"><?= esc($turno['identificador'] ?? 'N/A') ?></span></td>
        </tr>
        <tr>
          <td><span class="label">Carrera</span><span class="value"><?= esc($turno['carrera'] ?? 'N/A') ?></span></td>
          <td><span class="label">Campus</span><span class="value"><?= esc($turno['campus'] ?? 'N/A') ?></span></td>
        </tr>
      </table>
    </div>
  </div>

  <div class="section">
    <h2>Datos del turno</h2>
    <div class="card">
      <table class="grid">
        <tr>
          <td><span class="label">Fecha y hora de generación</span><span class="value"><?= esc($turno['fecha_generacion_texto'] ?? 'N/A') ?></span></td>
          <td><span class="label">Estatus actual</span><span class="value"><?= esc($turno['estatus'] ?? 'N/A') ?></span></td>
        </tr>
        <tr>
          <td><span class="label">Etapa actual</span><span class="value"><?= esc($turno['etapa'] ?? 'N/A') ?></span></td>
          <td><span class="label">Expira</span><span class="value"><?= esc($turno['fecha_expira_texto'] ?? 'N/A') ?></span></td>
        </tr>
      </table>
    </div>
  </div>

  <div class="section">
    <h2>Seguimiento</h2>
    <div class="card">
      <p style="margin-top:0;">
        Puedes consultar el avance de tu trámite usando el código QR o el folio mostrado en este comprobante.
      </p>

      <div class="qr-wrap">
        <?php if (!empty($qrImageSrc)): ?>
          <img src="<?= esc($qrImageSrc) ?>" alt="Código QR de seguimiento">
        <?php else: ?>
          <div style="font-weight:bold; color:#b42318;">No fue posible incrustar el QR en este momento.</div>
        <?php endif; ?>
      </div>

      <div class="note">
        Enlace de seguimiento:<br>
        <?= esc($turno['seguimiento_url'] ?? 'N/A') ?>
      </div>
    </div>
  </div>

  <div class="footer">
    Instituto Tecnológico de Oaxaca · Sistema de turnos para credencialización
  </div>
</body>
</html>
