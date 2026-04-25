<?php
require 'c:/xampp/htdocs/TFM-Sara-y-Daniela/database.php';
$res = $conn->query('SHOW TABLES');
while ($row = $res->fetch_array()) {
    echo $row[0] . PHP_EOL;
}
