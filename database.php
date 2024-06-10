<?php
$servername = "10.33.35.38";
$username = "fanded";
$password = "user";
$dbname = "intero_fajar";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
