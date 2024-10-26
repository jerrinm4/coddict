<?php
$conn = new mysqli('localhost', 'root', '', 'coddict24');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
?>