<?php
session_start(); // Start the session

// Include connection.php to connect to the database if needed
include('../connection.php');

// Initialize the response
$response = [
    'pesanMakanan' => false
];

// Check if 'pesanMakanan' is set and true
if (isset($_SESSION['pesanMakanan']) && $_SESSION['pesanMakanan'] === true) {
    $response['pesanMakanan'] = true;

    // If you want to log this event or interact with the database
    // Here you can insert logs into the database if needed

    // Example of inserting a log entry for the order
    $stmt = $pdo->prepare("INSERT INTO order_logs (status, created_at) VALUES (:status, NOW())");
    $stmt->bindParam(':status', $status);
    $status = 'Pesan Makanan';
    $stmt->execute();
}

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
