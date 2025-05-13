<?php
header('Content-Type: application/json');

// Include the connection.php to reuse the database connection
include('../connection.php');

$orderID = $_POST['orderID'] ?? null;
$discountID = $_POST['discountID'] ?? null;

// Validasi apakah DiscountID, BundlingID, PromoID, atau VoucherID sudah diisi
$checkOrderQuery = "SELECT DiscountID, BundlingID, PromoID, VoucherID FROM order_list WHERE OrderID = ?";
$checkOrderStmt = $conn->prepare($checkOrderQuery);
$checkOrderStmt->bind_param("i", $orderID);
$checkOrderStmt->execute();
$orderResult = $checkOrderStmt->get_result();
$orderData = $orderResult->fetch_assoc();

if (!is_null($orderData['DiscountID']) || !is_null($orderData['BundlingID']) || !is_null($orderData['PromoID']) || !is_null($orderData['VoucherID'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Diskon tidak dapat digandakan.',
        'redirect' => 'betaKasir.php' // Include the redirect URL
    ]);
    exit;
}

// Retrieve discount details
$discountQuery = "SELECT Persentase, masa_berlaku FROM discount WHERE DiscountID = ?";
$discountStmt = $conn->prepare($discountQuery);
$discountStmt->bind_param("i", $discountID);
$discountStmt->execute();
$discountResult = $discountStmt->get_result();
$discount = $discountResult->fetch_assoc();

if (!$discount) {
    echo json_encode(['success' => false, 'error' => 'Diskon tidak ditemukan.']);
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

// Validasi masa berlaku diskon
$currentDate = new DateTime();
$masaBerlaku = new DateTime($discount['masa_berlaku']);
if ($currentDate > $masaBerlaku) {
    echo json_encode(['success' => false, 'error' => 'Diskon sudah kedaluwarsa.']);
    exit;
}

if (!$orderData || !is_null($orderData['VoucherID']) || !is_null($orderData['BundlingID']) || !is_null($orderData['PromoID'])) {
    echo json_encode(['success' => false, 'error' => 'Discount tidak double.']);
    exit;
}

// Menghitung diskon
$discountAmount = 0;
if ($discount['Persentase'] > 0) {
    // Diskon berdasarkan persentase
    $discountAmount = ($discount['Persentase'] / 100) * $subtotal;
}

// Menghitung total harga setelah diskon
$totalAmount = $subtotal - $discountAmount;

// Memperbarui tabel `Order_list`
$updateQuery = "UPDATE order_list SET Total_promo = ?, Total_harga = ?, DiscountID = ? WHERE OrderID = ?";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("ddii", $discountAmount, $totalAmount, $discountID, $orderID);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true, 'totalAmount' => $totalAmount]);
} else {
    echo json_encode(['success' => false, 'error' => $updateStmt->error]);
}

$updateStmt->close();
$stmtSubtotal->close();
$conn->close();
?>
