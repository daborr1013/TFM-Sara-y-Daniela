<?php
require_once 'database.php';

// Fetch characters from database
$query = "SELECT * FROM characters WHERE work_id = 1 AND id IN (2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19)";
$result = $conn->query($query);

$characters = [];
while ($row = $result->fetch_assoc()) {
  $characters[$row['id']] = $row;
}

// Define nodes with positions
$nodes = [
  "jane" => ["label" => "Jane Eyre", "x" => 500, "y" => 350, "main" => true, "db_id" => 4],
  
  // Gateshead family and connections
  "eliza" => ["label" => "Eliza Reed", "x" => 100, "y" => 200, "db_id" => 2],
  "john" => ["label" => "John Reed", "x" => 100, "y" => 300, "db_id" => 5],
  "georgiana" => ["label" => "Georgiana Reed", "x" => 100, "y" => 400, "db_id" => 3],
  "bessie" => ["label" => "Bessie", "x" => 150, "y" => 500],
  "reed_sra" => ["label" => "Sra. Reed", "x" => 200, "y" => 150, "db_id" => 7],
  
  // Lowood
  "brocklehu" => ["label" => "Señor Brocklehurst", "x" => 150, "y" => 620, "db_id" => 8],
  "helen" => ["label" => "Helen Burns", "x" => 250, "y" => 650, "db_id" => 9],
  "senorita" => ["label" => "Señorita Temple", "x" => 250, "y" => 550, "db_id" => 10],
  "lloyd" => ["label" => "Señor Lloyd", "x" => 350, "y" => 600, "db_id" => 6],
  
  // Moor House
  "diana" => ["label" => "Diana Rivers", "x" => 650, "y" => 100, "db_id" => 11],
  "mary" => ["label" => "Mary Rivers", "x" => 750, "y" => 50, "db_id" => 13],
  "john_rivers" => ["label" => "St. John Rivers", "x" => 850, "y" => 100, "db_id" => 12],
  
  // Thornfield
  "rochester" => ["label" => "Edward Rochester", "x" => 700, "y" => 380, "db_id" => 16],
  "adele" => ["label" => "Adèle Varens", "x" => 800, "y" => 350, "db_id" => 14],
  "grace" => ["label" => "Grace Poole", "x" => 900, "y" => 450, "db_id" => 18],
  "bertha" => ["label" => "Bertha Mason", "x" => 900, "y" => 350, "db_id" => 15],
  "ingram" => ["label" => "Blanche Ingram", "x" => 950, "y" => 500, "db_id" => 19],
  "fairfax" => ["label" => "Señora Fairfax", "x" => 850, "y" => 520, "db_id" => 17],
];

$relations = [
  // Jane - Gateshead area
  ["from" => "jane", "to" => "reed_sra", "type" => "odio"],
  ["from" => "jane", "to" => "eliza", "type" => "relations"],
  ["from" => "jane", "to" => "georgiana", "type" => "relations"],
  ["from" => "jane", "to" => "john", "type" => "odio"],
  ["from" => "jane", "to" => "bessie", "type" => "afecto"],
  
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

  <?php foreach ($nodes as $id => $n): ?>
    <div
      class="node <?= $n['main'] ?? false ? 'main' : '' ?>"
      id="<?= $id ?>"
      style="left: <?= $n['x'] ?>px; top: <?= $n['y'] ?>px;"
      <?php if(isset($n['db_id']) && isset($characters[$n['db_id']])): ?>
        onclick="showCharacter(<?= $n['db_id'] ?>)"
        title="Haz clic para más información"
        style="cursor: pointer; left: <?= $n['x'] ?>px; top: <?= $n['y'] ?>px;"
      <?php endif; ?>
      >
      <?= $n['label'] ?>
    </div>
  <?php endforeach; ?>
</div>

<!-- Modal para mostrar información del personaje -->
<div id="characterModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <div id="characterInfo"></div>
  </div>
</div>

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

<script>
  const characters = <?php echo json_encode($characters); ?>;
  
  function showCharacter(characterId) {
    const character = characters[characterId];
    if (character) {
      document.getElementById('characterInfo').innerHTML = `
        <h2>${character.titulo}</h2>
        <div>${character.texto_curado}</div>
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