<?php
include 'database.php';
?>

<?php

$query = "SELECT * FROM blocks";
$result = $conn->query($query);

?>

<?php
include 'database.php';

$query = "SELECT * FROM blocks";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Capítulos</title>
    <link rel="stylesheet" href="css/css_caps.css">
    <script src="js/js_caps.js"></script>
</head>

<body>

<h1>Capítulos de Jane Eyre</h1>

<?php

while ($row = $result->fetch_assoc()) {

    echo "<h2>" . $row['titulo'] . "</h2>";
    echo "<p>" . $row['texto_curado'] . "</p>";

}

?>

</body>
</html>