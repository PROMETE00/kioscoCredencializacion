<?php
// Espera:
// $nombre, $estadoTexto, $estadoType ('ok'|'warn'|'error'), $miniUrl (opcional)
$estadoType = $estadoType ?? 'warn';
?>
<section class="d-cap-subheader">
  <div class="d-cap-subheader__mini">
    <?php if (!empty($miniUrl)): ?>
      <img class="d-cap-subheader__miniimg" src="<?= esc($miniUrl) ?>" alt="Mini preview">
    <?php else: ?>
      <div class="d-cap-subheader__miniph" aria-hidden="true"></div>
    <?php endif; ?>
  </div>

  <div class="d-cap-subheader__name"><?= esc($nombre ?? '—') ?></div>

  <div class="d-cap-subheader__status d-cap-subheader__status--<?= esc($estadoType) ?>">
    <?= esc($estadoTexto ?? 'EN CAPTURA') ?>
  </div>
</section>