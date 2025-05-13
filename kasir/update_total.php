<?php
// Include the connection file
include('../connection.php'); // This will use the connection from connection.php

// Ambil OrderID secara dinamis, misalnya dari sesi pengguna atau input form
$orderID = $_POST['orderID']; // Pastikan `orderID` dikirim dari AJAX atau input form

// Ambil data pesanan untuk menghitung subtotal dan total diskon
$sql = "
    SELECT ol.Total_harga, ol.Total_promo, oi.sub_total
    FROM order_list AS ol
    JOIN order_items AS oi ON ol.OrderID = oi.OrderID
    WHERE ol.OrderID = ? AND ol.status = 'processing'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orderID);
$stmt->execute();
$result = $stmt->get_result();

// Inisialisasi variabel
$subtotal = 0;
$totalDiscount = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $subtotal += $row['sub_total'];
        $totalDiscount = $row['Total_promo'];
    }

    // Hitung total akhir
    $totalAmount = $subtotal - $totalDiscount;

    // Perbarui Total_harga di database
    $updateQuery = "UPDATE order_list SET Total_harga = ? WHERE OrderID = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("di", $totalAmount, $orderID);

    if ($updateStmt->execute()) {
        echo json_encode(['success' => true, 'totalAmount' => $totalAmount]);
    } else {
        echo json_encode(['success' => false, 'error' => $updateStmt->error]);
    }

    $updateStmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Data pesanan tidak ditemukan.']);
}

$stmt->close();

// Close the connection
$conn->close();
?>
