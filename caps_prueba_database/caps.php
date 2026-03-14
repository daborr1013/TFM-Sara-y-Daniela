<?php
include '../database.php';

if (!isset($conn) || $conn->connect_error) {
    die("Database connection error: " . ($conn ? $conn->connect_error : "Connection not established"));
}

$query = "SELECT id, titulo FROM blocks";
$result = $conn->query($query);

if (!$result) {
    die("Query error: " . $conn->error);
}
?>


<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../css/css_eyre.css">
</head>
<body>
<h1>Jane Eyre</h1>

<h2>Capítulos</h2>

<?php

while ($row = $result->fetch_assoc()) {

    echo "<a href='cap.php?id=".$row['id']."'>";
    echo $row['titulo'];
    echo "</a><br>";

}

?>
</body>
</html>