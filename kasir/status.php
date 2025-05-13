<?php
// Include the connection file
include('../connection.php'); // This will use the connection from connection.php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orderID'])) {
    $orderID = (int) $_POST['orderID'];
        
    // Reset pesananmakanan to 0 when the page loads
    $sqlReset = "UPDATE status SET Status = 1 WHERE StatusID = 4";
    $conn->query($sqlReset);
    
    // Query to update status to 'pending'
    $query = "UPDATE order_list SET status = 'pending' WHERE OrderID = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $orderID); // Bind OrderID parameter
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "Order status updated to pending";
        } else {
            echo "No rows updated";
        }

        $stmt->close();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>
