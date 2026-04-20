<?php
$nodes = [
  "jane" => ["label" => "Jane Eyre", "x" => 500, "y" => 350, "main" => true],

  "gateshead" => ["label" => "Gateshead", "x" => 250, "y" => 150],
  "moor" => ["label" => "Moor House", "x" => 750, "y" => 150],
  "thornfield" => ["label" => "Thornfield Hall", "x" => 750, "y" => 550],
  "lowood" => ["label" => "Lowood", "x" => 250, "y" => 550],
];

$relations = [
  ["from" => "jane", "to" => "gateshead", "type" => "odio"],
  ["from" => "jane", "to" => "moor", "type" => "afecto"],
  ["from" => "jane", "to" => "thornfield", "type" => "amor"],
  ["from" => "jane", "to" => "lowood", "type" => "amistad"],
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mapa de relaciones</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<div id="mapa">
  <svg id="lines"></svg>

  <?php foreach ($nodes as $id => $n): ?>
    <div
      class="node <?= $n['main'] ?? false ? 'main' : '' ?>"
      id="<?= $id ?>"
      style="left: <?= $n['x'] ?>px; top: <?= $n['y'] ?>px;">
      <?= $n['label'] ?>
    </div>
  <?php endforeach; ?>
</div>

<script>
  const relations = <?= json_encode($relations) ?>;
</script>
<script src="script.js"></script>

</body>
</html>
``