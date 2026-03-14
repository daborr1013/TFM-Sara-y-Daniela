<?php
include 'database.php';
?>

<?php

$query = "SELECT * FROM summaries";
$result = $conn->query($query);

?>

<?php
include 'database.php';

$query = "SELECT * FROM summaries";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Resúmenes</title>
</head>

<body>

<h1>Resúmenes de Jane Eyre</h1>

<?php

while ($row = $result->fetch_assoc()) {

    echo "<h2>" . $row['chapter'] . "</h2>";
    echo "<p>" . $row['contenido'] . "</p>";

}

?>

</body>
</html>