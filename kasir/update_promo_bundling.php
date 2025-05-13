<?php
// Include the connection file
include('../connection.php'); // This will use the connection from connection.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderID = intval($_POST['orderID']);
    $id = intval($_POST['id']);
    $type = $_POST['type'];

    // Validate the input
    if (!$orderID || !$id || !$type) {
        echo json_encode(['success' => false, 'error' => 'Invalid input data']);
        exit();
    }

    // Query untuk memeriksa apakah promo/bundling sudah diterapkan
    if ($type === 'Promo') {
        $checkQuery = "SELECT PromoID FROM order_list WHERE OrderID = ? AND PromoID IS NOT NULL AND status = 'processing'";
    } elseif ($type === 'Bundling') {
        $checkQuery = "SELECT BundlingID FROM order_list WHERE OrderID = ? AND BundlingID IS NOT NULL AND status = 'processing'";
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid type']);
        exit();
    }

    $stmtCheck = $conn->prepare($checkQuery);
    $stmtCheck->bind_param('i', $orderID);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    // Jika PromoID atau BundlingID sudah ada
    if ($stmtCheck->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Promo/Bundling sudah diterapkan ke order ini']);
        $stmtCheck->close();
        $conn->close();
        exit();
    }
    $stmtCheck->close();

    // Lanjutkan dengan pembaruan jika promo/bundling belum ada
    if ($type === 'Promo') {
        $query = "UPDATE order_list SET PromoID = ? WHERE OrderID = ? AND status = 'processing'";
    } elseif ($type === 'Bundling') {
        $query = "UPDATE order_list SET BundlingID = ? WHERE OrderID = ? AND status = 'processing'";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $id, $orderID);

    if ($stmt->execute()) {
        // Promo/Bundling successfully updated, now update the total
        $updateTotalQuery = "
            UPDATE order_list
            SET total_harga = (
                SELECT SUM(oi.sub_total) - 
                    (CASE 
                        WHEN PromoID IS NOT NULL THEN (total_harga * (SELECT persentase FROM promo WHERE PromoID = ?)/100)
                        WHEN BundlingID IS NOT NULL THEN (SELECT discount FROM bundling WHERE BundlingID = ?)
                        ELSE 0 
                    END)
                FROM order_items oi
                WHERE oi.OrderID = order_list.OrderID
            )
            WHERE OrderID = ?";
        
        $stmtTotal = $conn->prepare($updateTotalQuery);
        $stmtTotal->bind_param('iii', $id, $id, $orderID);

        if ($stmtTotal->execute()) {
            echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update total']);
        }
        $stmtTotal->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update promo/bundling']);
    }
    $stmt->close();
}

// Close the connection
$conn->close();
?>
