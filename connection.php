<?php
$servername = "127.0.0.1";
$username = "u242583366_canngopii";
$password = "Cobapos8";
$dbname = "u242583366_coba_poss";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Define the $host variable if needed elsewhere
$host = $servername;
?>
