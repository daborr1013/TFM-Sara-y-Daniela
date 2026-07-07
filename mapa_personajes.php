<?php
require_once 'database.php';

// Fetch characters from database
$query = "SELECT * FROM characters WHERE work_id = 1 AND id IN (2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19)";
$result = $conn->query($query);

$characters = [];
while ($row = $result->fetch_assoc()) {
  $characters[$row['id']] = $row;
}

// Define nodes with positions based on narrative location in Jane Eyre
$nodes = [
  "jane" => ["label" => "Jane Eyre", "x" => 600, "y" => 350, "main" => true, "db_id" => 4],
  
  // Gateshead (beginning - left side)
  "reed_sra" => ["label" => "Sra. Reed", "x" => 80, "y" => 80, "db_id" => 7],
  "eliza" => ["label" => "Eliza Reed", "x" => 80, "y" => 200, "db_id" => 2],
  "georgiana" => ["label" => "Georgiana Reed", "x" => 80, "y" => 280, "db_id" => 3],
  "john" => ["label" => "John Reed", "x" => 80, "y" => 360, "db_id" => 5],
  "bessie" => ["label" => "Bessie", "x" => 150, "y" => 450],
  "lloyd" => ["label" => "Señor Lloyd", "x" => 200, "y" => 200, "db_id" => 6],
  
  // Lowood (school - left-center, below Gateshead)
  "brocklehu" => ["label" => "Señor Brocklehurst", "x" => 100, "y" => 580, "db_id" => 8],
  "helen" => ["label" => "Helen Burns", "x" => 220, "y" => 620, "db_id" => 9],
  "senorita" => ["label" => "Señorita Temple", "x" => 280, "y" => 560, "db_id" => 10],
  
  // Thornfield (main setting - right side)
  "rochester" => ["label" => "Edward Rochester", "x" => 850, "y" => 280, "db_id" => 16],
  "adele" => ["label" => "Adèle Varens", "x" => 950, "y" => 200, "db_id" => 14],
  "fairfax" => ["label" => "Señora Fairfax", "x" => 900, "y" => 380, "db_id" => 17],
  "grace" => ["label" => "Grace Poole", "x" => 1000, "y" => 350, "db_id" => 18],
  "bertha" => ["label" => "Bertha Mason", "x" => 1050, "y" => 280, "db_id" => 15],
  "ingram" => ["label" => "Blanche Ingram", "x" => 950, "y" => 480, "db_id" => 19],
  
  // Moor House (later setting - top right)
  "john_rivers" => ["label" => "St. John Rivers", "x" => 850, "y" => 80, "db_id" => 12],
  "diana" => ["label" => "Diana Rivers", "x" => 920, "y" => 120, "db_id" => 11],
  "mary" => ["label" => "Mary Rivers", "x" => 990, "y" => 80, "db_id" => 13],
];

$charImages = [
  2 => 'eliza.png',
  3 => 'georgina.png',
  4 => 'jane.png',
  5 => 'johnReed.png',
  6 => 'lloyd.png',
  7 => 'senoraReed.png',
  8 => 'brocklehurst.png',
  9 => 'helen.png',
  10 => 'temple.png',
  11 => 'diana.png',
  12 => 'johnRivers.png',
  13 => 'mary.png',
  14 => 'adele.png',
  15 => 'bertha.png',
  16 => 'rochester.png',
  17 => 'fairfaix.png',
  18 => 'gracePoole.png',
  19 => 'ingram.png'
];

$relations = [
  // Jane - Gateshead area
  ["from" => "jane", "to" => "reed_sra", "type" => "odio"],
  ["from" => "jane", "to" => "eliza", "type" => "relations"],
  ["from" => "jane", "to" => "georgiana", "type" => "relations"],
  ["from" => "jane", "to" => "john", "type" => "odio"],
  ["from" => "jane", "to" => "bessie", "type" => "afecto"],
  ["from" => "jane", "to" => "lloyd", "type" => "afecto"],
  
  // Jane - Lowood
  ["from" => "jane", "to" => "brocklehu", "type" => "odio"],
  ["from" => "jane", "to" => "helen", "type" => "afecto"],
  ["from" => "jane", "to" => "senorita", "type" => "afecto"],
  
  // Jane - Moor House
  ["from" => "jane", "to" => "diana", "type" => "afecto"],
  ["from" => "jane", "to" => "mary", "type" => "afecto"],
  ["from" => "jane", "to" => "john_rivers", "type" => "relations"],
  
  // Jane - Thornfield
  ["from" => "jane", "to" => "rochester", "type" => "amor"],
  ["from" => "jane", "to" => "adele", "type" => "afecto"],
  ["from" => "jane", "to" => "grace", "type" => "relations"],
  ["from" => "jane", "to" => "fairfax", "type" => "afecto"],
  
  // Other relationships
  ["from" => "rochester", "to" => "bertha", "type" => "odio"],
  ["from" => "rochester", "to" => "adele", "type" => "afecto"],
  ["from" => "rochester", "to" => "ingram", "type" => "relations"],
  ["from" => "adele", "to" => "ingram", "type" => "odio"],
  ["from" => "helen", "to" => "senorita", "type" => "afecto"],
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mapa de relaciones</title>
  <link rel="stylesheet" href="css/css_mapa.css">
</head>
<body>

<h1>Mapa de relaciones</h1>

<div id="mapa">
  <svg id="lines"></svg>
  
  <!-- Place labels -->
  <div class="place-label" style="left: <?= (70/1200)*100 ?>%; top: <?= (30/700)*100 ?>%;">Gateshead</div>
  <div class="place-label" style="left: <?= (120/1200)*100 ?>%; top: <?= (530/700)*100 ?>%;">Lowood</div>
  <div class="place-label" style="left: <?= (850/1200)*100 ?>%; top: <?= (40/700)*100 ?>%;">Moor House</div>
  <div class="place-label" style="left: <?= (900/1200)*100 ?>%; top: <?= (160/700)*100 ?>%;">Thornfield</div>

  <?php foreach ($nodes as $id => $n): ?>
    <?php 
      $imgSrc = '';
      if(isset($n['db_id']) && isset($charImages[$n['db_id']])) {
          $imgSrc = 'media/images/' . $charImages[$n['db_id']];
      } else if (file_exists('media/images/' . $id . '.png')) {
          $imgSrc = 'media/images/' . $id . '.png';
      }
    ?>
    <div
      class="node <?= $n['main'] ?? false ? 'main' : '' ?>"
      id="<?= $id ?>"
      style="left: <?= ($n['x'] / 1200) * 100 ?>%; top: <?= ($n['y'] / 700) * 100 ?>%;"
      <?php if(isset($n['db_id']) && isset($characters[$n['db_id']])): ?>
        onclick="showCharacter(<?= $n['db_id'] ?>)"
        title="Haz clic para más información"
      <?php endif; ?>
      >
      <?php if ($imgSrc): ?>
        <div class="node-image" style="background-image: url('<?= $imgSrc ?>');"></div>
      <?php else: ?>
        <div class="node-image placeholder"></div>
      <?php endif; ?>
      <span class="node-label"><?= $n['label'] ?></span>
    </div>
  <?php endforeach; ?>

  <div class="legend">
    <div class="legend-item">
      <div class="legend-color color-odio"></div>
      <span>Odio, abuso, opresión</span>
    </div>
    <div class="legend-item">
      <div class="legend-color color-afecto"></div>
      <span>Afecto, amistad, estabilidad</span>
    </div>
    <div class="legend-item">
      <div class="legend-color color-relations"></div>
      <span>Relaciones frías, intelectuales</span>
    </div>
    <div class="legend-item">
      <div class="legend-color color-amor"></div>
      <span>Amor</span>
    </div>
  </div>
</div>

<!-- Modal para mostrar información del personaje -->
<div id="characterModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <div id="characterInfo"></div>
  </div>
</div>

<script>
  const characters = <?php echo json_encode($characters); ?>;
  
  // Map character IDs to image filenames
  const characterImages = {
    2: 'eliza.png',
    3: 'georgina.png',
    4: 'jane.png',
    5: 'johnReed.png',
    6: 'lloyd.png',
    7: 'senoraReed.png',
    8: 'brocklehurst.png',
    9: 'helen.png',
    10: 'temple.png',
    11: 'diana.png',
    12: 'johnRivers.png',
    13: 'mary.png',
    14: 'adele.png',
    15: 'bertha.png',
    16: 'rochester.png',
    17: 'fairfaix.png',
    18: 'gracePoole.png',
    19: 'ingram.png'
  };
  
  function showCharacter(characterId) {
    const character = characters[characterId];
    if (character) {
      const imageSrc = characterImages[characterId] ? `media/images/${characterImages[characterId]}` : '';
      const imageHtml = imageSrc ? `<img src="${imageSrc}" alt="${character.nombre}" class="character-image">` : '';
      
      document.getElementById('characterInfo').innerHTML = `
        <div class="character-modal-content">
          ${imageHtml}
          <div class="character-text">
            <h2>${character.nombre}</h2>
            <p>${character.descripcion}</p>
          </div>
        </div>
      `;
      document.getElementById('characterModal').style.display = 'block';
    }
  }
  
  function closeModal() {
    document.getElementById('characterModal').style.display = 'none';
  }
  
  window.onclick = function(event) {
    const modal = document.getElementById('characterModal');
    if (event.target == modal) {
      modal.style.display = 'none';
    }
  }
  
  const relations = <?= json_encode($relations) ?>;
</script>
<script src="js/js_mapa.js"></script>

</body>
</html>