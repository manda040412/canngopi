<?php
// Include the connection file
include('../connection.php'); // This will use the connection from connection.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipePenjualan = $_POST['TipePenjualan'];
    $orderID = $_POST['OrderID'];

    // Daftar opsi yang valid sesuai ENUM di database
    $validOptions = ['DineIn', 'TakeAway', null];

    // Validasi nilai
    if (in_array($tipePenjualan, $validOptions, true)) {
        // Update kolom TipePenjualan
        $sql = "UPDATE order_list SET TipePenjualan = ? WHERE OrderID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $tipePenjualan, $orderID);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "Tipe Penjualan berhasil diupdate: " . $tipePenjualan . ", OrderID: " . $orderID;
            } else {
                echo "Query dijalankan, tetapi tidak ada baris yang diupdate.";
            }
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error: Nilai tipe penjualan tidak valid.";
    }

}
?>
