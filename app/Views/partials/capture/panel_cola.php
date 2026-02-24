<?php
  $title = $title ?? 'Cola (FOTO)';
  $queue = $queue ?? [];
?>

<aside class="d-panel d-queue">
  <div class="d-card-head" >
    <div class="d-card-title"><?= esc($title) ?></div>
    <span class="d-badge" id="qCount"><?= count($queue) ?></span>
  </div>


    <input id="qSearch" type="text" placeholder="Buscar por nombre o control..."
           class="d-search">


  <div id="queueList" class="d-queueList">
    
  </div>
</aside>