<?php
include 'database.php';

echo "=== CHARACTERS TABLE ===\n";
$result = $conn->query('SELECT COUNT(*) as count FROM characters WHERE work_id = 1');
$row = $result->fetch_assoc();
echo "Total characters: " . $row['count'] . "\n";

echo "\n=== SAMPLE CHARACTERS ===\n";
$result = $conn->query('SELECT nombre, rol, descripcion FROM characters WHERE work_id = 1 LIMIT 3');
while($row = $result->fetch_assoc()) {
    echo "- " . $row['nombre'] . " (" . $row['rol'] . ")\n";
    echo "  Description: " . substr(strip_tags($row['descripcion']), 0, 100) . "...\n\n";
}

echo "\n=== SUMMARIES TABLE ===\n";
$result = $conn->query('SELECT COUNT(*) as count FROM summaries');
$row = $result->fetch_assoc();
echo "Total summaries: " . $row['count'] . "\n";

echo "\n=== SAMPLE SUMMARIES ===\n";
$result = $conn->query('SELECT chapter, LENGTH(contenido) as length FROM summaries LIMIT 3');
while($row = $result->fetch_assoc()) {
    echo "Chapter " . $row['chapter'] . ": " . $row['length'] . " bytes\n";
}

echo "\n=== BLOCKS TABLE ===\n";
$result = $conn->query('SELECT COUNT(*) as count FROM blocks WHERE work_id = 1');
$row = $result->fetch_assoc();
echo "Total blocks: " . $row['count'] . "\n";

$conn->close();
?>
