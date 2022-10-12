<?php
$servername = $env["MYSQL_ADDRESS"];
$username = $env["MYSQL_USER"];
$password = $env["MYSQL_PASSWORD"];
$dbname = $env["MYSQL_DATABASE"];

$conn = new mysqli($servername, $username, $password, $dbname);
?>
