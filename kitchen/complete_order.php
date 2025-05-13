<?php
session_start();

// Include the connection file
include('../connection.php');

// Check if orderID is provided
if (isset($_POST['orderID'])) {
    $orderID = $_POST['orderID'];

    // Update the order status to 'completed'
    $update_query = "UPDATE order_list SET status = 'completed' WHERE OrderID = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $orderID);

    if ($stmt->execute()) {
        // Query to count the remaining orders that are not completed
        $count_sql = "SELECT COUNT(DISTINCT OrderID) as total_orders FROM order_list WHERE status != 'completed'";
        $count_result = $conn->query($count_sql);

        $total_orders = 0;
        if ($count_result && $count_result->num_rows > 0) {
            $row = $count_result->fetch_assoc();
            $total_orders = $row['total_orders'];
        }

        // Send the updated order count as a response
        echo json_encode(['total_orders' => $total_orders]);
    } else {
        echo json_encode(['error' => 'Failed to update order status.']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'OrderID not provided.']);
}

$conn->close();
?>
