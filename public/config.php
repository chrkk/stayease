<?php
$host     = "localhost";
$dbname   = "stayeasedb";
$username = "root";
$password = "";  // blank by default in XAMPP

$con = new mysqli($host, $username, $password, $dbname);

if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}
?>