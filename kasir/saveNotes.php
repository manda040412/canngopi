<?php
// Include connection.php to use the database connection
include('../connection.php'); // This will use the connection from connection.php

// Ambil data notes
$notes = isset($_POST['notes']) ? $_POST['notes'] : null;
$orderID = isset($_POST['OrderID']) ? intval($_POST['OrderID']) : null;

// Validasi input
if (empty($notes) || empty($orderID)) {
    // die("Notes atau OrderID tidak boleh kosong.");
}

// Query untuk Notes
$sql = "UPDATE order_list SET notes = ? WHERE OrderID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $notes, $orderID);

if ($stmt->execute()) {
    // echo "Notes berhasil disimpan.";
    header("Location: betaRiwayat.php");
} else {
    // echo "Gagal menyimpan notes: " . $stmt->error;
}

$stmt->close();

// Tutup koneksi
$conn->close();
?>
