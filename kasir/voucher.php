<?php
header('Content-Type: application/json');

// Include the connection file
include('../connection.php'); // This will use the connection from connection.php

// Get data from POST request
$orderID = $_POST['orderID'];
$voucherID = $_POST['voucherID'];

// Retrieve voucher details
$voucherQuery = "SELECT Persentase, Potongan, jumlah, masa_berlaku FROM voucher WHERE VoucherID = ?";
$voucherStmt = $conn->prepare($voucherQuery);
$voucherStmt->bind_param("i", $voucherID);
$voucherStmt->execute();
$voucherResult = $voucherStmt->get_result();
$voucher = $voucherResult->fetch_assoc();

if (!$voucher) {
    echo json_encode(['success' => false, 'error' => 'Voucher tidak ditemukan.']);
    exit;
}

// Ambil data subtotal dari `Order_items`
$sqlSubtotal = "SELECT SUM(sub_total) AS subtotal FROM order_items WHERE OrderID = ?";
$stmtSubtotal = $conn->prepare($sqlSubtotal);
$stmtSubtotal->bind_param("i", $orderID);
$stmtSubtotal->execute();
$resultSubtotal = $stmtSubtotal->get_result();
$rowSubtotal = $resultSubtotal->fetch_assoc();
$subtotal = $rowSubtotal['subtotal'];

if (!$subtotal) {
    echo json_encode(['success' => false, 'error' => 'Subtotal tidak ditemukan.']);
    exit;
}

// Validasi masa berlaku voucher
$currentDate = new DateTime();
$masaBerlaku = new DateTime($voucher['masa_berlaku']);
if ($currentDate > $masaBerlaku) {
    echo json_encode(['success' => false, 'error' => 'Voucher sudah kedaluwarsa.']);
    exit;
}

// Validasi jumlah voucher
if ($voucher['jumlah'] <= 0) {
    echo json_encode(['success' => false, 'error' => 'Voucher sudah habis.']);
    exit;
}

// Validasi apakah VoucherID, BundlingID, atau PromoID sudah diisi
$checkOrderQuery = "SELECT DiscountID, BundlingID, PromoID, VoucherID FROM order_list WHERE OrderID = ?";
$checkOrderStmt = $conn->prepare($checkOrderQuery);
$checkOrderStmt->bind_param("i", $orderID);
$checkOrderStmt->execute();
$orderResult = $checkOrderStmt->get_result();
$orderData = $orderResult->fetch_assoc();

if (!$orderData || !is_null($orderData['DiscountID']) || !is_null($orderData['BundlingID']) || !is_null($orderData['PromoID'])) {
    echo json_encode(['success' => false, 'error' => 'Voucher tidak double.']);
    exit;
}

// Menghitung diskon
$discountAmount = 0;
if ($voucher['Persentase'] > 0) {
    // Diskon berdasarkan persentase
    $discountAmount = ($voucher['Persentase'] / 100) * $subtotal;
} else if ($voucher['Potongan'] > 0) {
    // Diskon berdasarkan potongan tetap
    $discountAmount = $voucher['Potongan'];
}

// Menghitung total harga setelah diskon
$totalAmount = $subtotal - $discountAmount;

// Memperbarui tabel `Order_list`
$updateQuery = "UPDATE order_list SET Total_promo = ?, Total_harga = ?, VoucherID = ? WHERE OrderID = ?";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("ddii", $discountAmount, $totalAmount, $voucherID, $orderID);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true, 'totalAmount' => $totalAmount]);
} else {
    echo json_encode(['success' => false, 'error' => $updateStmt->error]);
}

$updateStmt->close();
$stmtSubtotal->close();

// Close the connection
$conn->close();
?>
