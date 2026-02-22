<?php
  $title = $title ?? 'Cola (FOTO)';
  $queue = $queue ?? [];
?>

<aside class="d-side d-queue">
  <div class="d-side__title" style="display:flex; justify-content:space-between; align-items:center;">
    <span><?= esc($title) ?></span>
    <span class="d-badge" id="qCount"><?= count($queue) ?></span>
  </div>

  <div class="d-tipbox" style="margin-top: 0;">
    <input id="qSearch" type="text" placeholder="Buscar por nombre o control..."
           class="d-search">
  </div>

  <div id="queueList" class="d-queueList"></div>
</aside>