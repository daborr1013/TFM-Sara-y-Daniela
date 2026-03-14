<?php
include '../database.php';

$id = $_GET['id'];

$query = "SELECT * FROM blocks WHERE id = $id";
$result = $conn->query($query);

if (!$result) {
    die("Query error: " . $conn->error);
}

$capitulo = $result->fetch_assoc();

if (!$capitulo) {
    die("No chapter found with id: " . $id);
}
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