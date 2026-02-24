<?php
  $title = $title ?? 'Cola (FIRMA)';
  $queue = $queue ?? [];
?>
<aside class="d-panel d-queue">
    <div class="d-card-head">
      <div class="d-card-title"><?= esc($title) ?></div>
      <span class="d-badge" id="colaCount"><?= count($queue) ?></span>
    </div>

    <input class="d-input" id="colaSearch" placeholder="Buscar por nombre o Numero de control" />

    <div class="d-queue" id="colaList">
      <!-- items por JS -->
    </div>

</aside>