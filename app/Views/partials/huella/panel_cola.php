<?php
  $title = $title ?? 'Cola (HUELLA)';
  $queue = $queue ?? [];
?>
<aside class="d-panel d-queue">

  <div class="d-card-head">
    <div class="d-card-title"><?= esc($title) ?></div>
    <span class="d-badge" id="qCount"><?= count($queue) ?></span>
  </div>

  <input id="qSearch" type="text" class="d-search" placeholder="Buscar por nombre o Numero de control">

  <div id="queueList" class="d-queueList">
    <!-- items por JS -->
  </div>

</aside>