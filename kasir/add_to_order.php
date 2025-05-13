<?php
header('Content-Type: application/json');

// Memasukkan file connection.php
include('../connection.php');

session_start();

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['type']) || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

if (!isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$type = $data['type'];
$id = $data['id'];
$userID = $_SESSION['userID'];

$query = "SELECT OrderID, VoucherID, DiscountID, PromoID, BundlingID FROM order_list WHERE userID = ? AND status = 'processing' LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $orderId = $row['OrderID'];
    $voucherID = $row['VoucherID'];
    $DiscountID = $row['DiscountID'];
    $promoID = $row['PromoID'];
    $bundlingID = $row['BundlingID'];

    // Check the type selected
    if ($type === 'Promo') {
        // Ensure no double discounts
        if ($promoID !== null || $voucherID !== null || $DiscountID !== null || $bundlingID !== null) {
            echo json_encode(['success' => false, 'message' => 'Promo tidak bisa double.']);
            exit;
        }

        // Fetch the discount value (pengurangan_harga)
        $promoQuery = "SELECT pengurangan_harga FROM promo WHERE PromoID = ?";
        $promoStmt = $conn->prepare($promoQuery);
        $promoStmt->bind_param('i', $id);
        $promoStmt->execute();
        $promoResult = $promoStmt->get_result();
        $promoRow = $promoResult->fetch_assoc();
        $promoValue = $promoRow['pengurangan_harga'] ?? 0;

        // Update the PromoID and total_promo
        $query = "UPDATE order_list SET PromoID = ?, total_promo = ? WHERE OrderID = ? AND status = 'processing'";
        $updateValue = $promoValue;

    } elseif ($type === 'Bundling') {
        // Ensure no double discounts
        if ($bundlingID !== null || $promoID !== null || $DiscountID !== null || $voucherID !== null) {
            echo json_encode(['success' => false, 'message' => 'Bundling tidak bisa double']);
            exit;
        }

        // Fetch the bundling discount value (potongan)
        $bundleQuery = "SELECT potongan FROM bundling WHERE BundlingID = ?";
        $bundleStmt = $conn->prepare($bundleQuery);
        $bundleStmt->bind_param('i', $id);
        $bundleStmt->execute();
        $bundleResult = $bundleStmt->get_result();
        $bundleRow = $bundleResult->fetch_assoc();
        $bundleValue = $bundleRow['potongan'] ?? 0;

        // Update the BundlingID and total_promo
        $query = "UPDATE order_list SET BundlingID = ?, total_promo = ? WHERE OrderID = ? AND status = 'processing'";
        $updateValue = $bundleValue;

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid item type']);
        exit;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param('iii', $id, $updateValue, $orderId);
        $stmt->execute();

        // Proceed with adding menus to order_items as in the original code
        if ($type === 'Promo') {
            $menuQuery = "SELECT m.MenuID FROM menu m
                          JOIN menu_promo mp ON mp.MenuID = m.MenuID
                          WHERE mp.PromoID = ?";
        } elseif ($type === 'Bundling') {
            $menuQuery = "SELECT m.MenuID FROM menu m
                          JOIN menu_bundling mb ON mb.MenuID = m.MenuID
                          WHERE mb.BundlingID = ?";
        }

        $menuStmt = $conn->prepare($menuQuery);
        $menuStmt->bind_param('i', $id);
        $menuStmt->execute();
        $menuResult = $menuStmt->get_result();

        while ($menuRow = $menuResult->fetch_assoc()) {
            $menuID = $menuRow['MenuID'];

            // Check if the menu already exists in order_items
            $checkQuery = "SELECT OrderItemID FROM order_items WHERE OrderID = ? AND MenuID = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param('ii', $orderId, $menuID);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                $updateQuery = "UPDATE order_items SET Quantity = Quantity + 1 WHERE OrderID = ? AND MenuID = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param('ii', $orderId, $menuID);
                $updateStmt->execute();
            } else {
                $insertQuery = "INSERT INTO order_items (OrderID, MenuID, Quantity) VALUES (?, ?, 1)";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param('ii', $orderId, $menuID);
                $insertStmt->execute();
            }

            // Update sub_total
            $subTotalQuery = "UPDATE order_items oi
                              JOIN menu m ON oi.MenuID = m.MenuID
                              SET oi.sub_total = oi.Quantity * m.harga
                              WHERE oi.OrderID = ? AND oi.MenuID = ?";
            $subTotalStmt = $conn->prepare($subTotalQuery);
            $subTotalStmt->bind_param('ii', $orderId, $menuID);
            $subTotalStmt->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Item added successfully to the order and total promo updated']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }
}

$stmt->close();
$conn->close();
?>
