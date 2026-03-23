<?php
include '../database.php';

// Validate and sanitize input
if (!isset($_GET['id']) || empty($_GET['id'])) {
    error_log("Missing or empty chapter ID");
    die("Capítulo no encontrado.");
}

$id = intval($_GET['id']); // Convert to integer for safety

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM blocks WHERE id = ?");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("Error al buscar el capítulo. Por favor, intenta más tarde.");
}

$stmt->bind_param("i", $id);
if (!$stmt->execute()) {
    error_log("Execute failed: " . $stmt->error);
    die("Error al buscar el capítulo. Por favor, intenta más tarde.");
}

$result = $stmt->get_result();

if (!$result) {
    error_log("Query error: " . $conn->error);
    die("Error al buscar el capítulo. Por favor, intenta más tarde.");
}

$capitulo = $result->fetch_assoc();

if (!$capitulo) {
    error_log("Chapter not found with id: " . $id);
    die("Capítulo no encontrado.");
}
?>
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../css/css_caps.css">
</head>
<body>

<h1><?php echo $capitulo['titulo']; ?></h1>

<p>
<?php echo $capitulo['texto_curado']; ?>
</p>

</body>
</html>