<?php
header("Content-Type: application/json");
include('../connection.php');

// Get input data
$penjualan_per_hari = $_POST['penjualan_per_hari'] ?? '';
$menu_terjual = $_POST['menu_terjual'] ?? '';
$promo_terpakai = $_POST['promo_terpakai'] ?? '0';
$UserID = isset($_POST['UserID']) ? (int)$_POST['UserID'] : 0;
$tanggal = $_POST['tanggal'] ?? '';

// Check if UserID exists in the user table
$check_user_sql = "SELECT UserID FROM user WHERE UserID = ?";
$stmt_user = $conn->prepare($check_user_sql);
$stmt_user->bind_param("i", $UserID);
$stmt_user->execute();
$user_result = $stmt_user->get_result();

if ($user_result->num_rows === 0) {
    // UserID doesn't exist in the user table
    echo json_encode(['success' => false, 'error' => "UserID $UserID does not exist in the user table"]);
    $conn->close();
    exit;
}
$stmt_user->close();

// Gunakan prepared statement untuk keamanan
$check_sql = "SELECT PenjualanID FROM penjualan WHERE DATE(created_at) = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("s", $tanggal);
$stmt->execute();
$check_result = $stmt->get_result();
$penjualanID_row = $check_result->fetch_assoc();
$penjualanID = $penjualanID_row['PenjualanID'] ?? null;
$stmt->close();

// Ambil informasi order
$order_sql = "SELECT MIN(OrderID) as start_orderID, MAX(OrderID) as last_orderID, COUNT(OrderID) as total_order FROM order_list WHERE DATE(created_at) = ?";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("s", $tanggal);
$stmt->execute();
$order_result = $stmt->get_result()->fetch_assoc();
$start_orderID = $order_result['start_orderID'];
$last_orderID = $order_result['last_orderID'];
$total_order = $order_result['total_order'];
$stmt->close();

if ($start_orderID && $last_orderID) {
    $timestamp_sql = "SELECT created_at FROM order_list WHERE OrderID IN (?, ?) ORDER BY created_at ASC";
    $stmt = $conn->prepare($timestamp_sql);
    $stmt->bind_param("ii", $start_orderID, $last_orderID);
    $stmt->execute();
    $timestamp_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $first_created_at = $timestamp_result[0]['created_at'] ?? null;
    $last_created_at = end($timestamp_result)['created_at'] ?? null;
    $stmt->close();
} else {
    $first_created_at = $last_created_at = null;
}

// Jika data sudah ada, lakukan update
if ($penjualanID) {
    $sql = "UPDATE penjualan 
            SET penjualan_per_hari = ?, menu_terjual = ?, promo_terpakai = ?, updated_at = NOW() 
            WHERE PenjualanID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isii", $penjualan_per_hari, $menu_terjual, $promo_terpakai, $penjualanID);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Data berhasil diperbarui', 'debug' => ['penjualanID' => $penjualanID]]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
} else {
    // Insert data baru
    $sql = "INSERT INTO penjualan (UserID, penjualan_per_hari, menu_terjual, promo_terpakai, Date, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisis", $UserID, $penjualan_per_hari, $menu_terjual, $promo_terpakai, $tanggal);
    $success = $stmt->execute();
    $penjualanID = $stmt->insert_id;
    $stmt->close();

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Data berhasil ditambahkan', 'debug' => ['penjualanID' => $penjualanID]]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}
// Masukkan data ke dalam order_penjualan
$orderID_sql = "SELECT OrderID FROM order_list WHERE DATE(created_at) = ?";
$stmt = $conn->prepare($orderID_sql);
$stmt->bind_param("s", $tanggal);
$stmt->execute();
$orderID_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($orderID_result as $orderID_row) {
    $orderID = $orderID_row['OrderID'];
    $sql = "INSERT IGNORE INTO order_penjualan (OrderID, PenjualanID) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $orderID, $penjualanID);
    $stmt->execute();
    $stmt->close();
}

// First check if a report already exists for this PenjualanID
$check_report_sql = "SELECT ReportID FROM report WHERE PenjualanID = ?";
$stmt = $conn->prepare($check_report_sql);
$stmt->bind_param("i", $penjualanID);
$stmt->execute();
$report_result = $stmt->get_result();
$stmt->close();

$judul = "Daily Sales Report pada tanggal $tanggal";
$deskripsi = "Report untuk penjualan yang terjadi pada tanggal $tanggal";

if ($report_result->num_rows > 0) {
    // Update existing report
    $report_sql = "UPDATE report SET 
                  UserID = ?,
                  judul = ?, 
                  deskripsi = ?, 
                  penjualan_per_hari = ?, 
                  total_order = ?, 
                  menu_terjual = ?, 
                  promo_terpakai = ?, 
                  rekapan_penilaian = 4, 
                  Start_Date = ?, 
                  End_Date = ?, 
                  updated_at = NOW()
                  WHERE PenjualanID = ?";
    $stmt = $conn->prepare($report_sql);
    $stmt->bind_param("issiiiissi", $UserID, $judul, $deskripsi, $penjualan_per_hari, $total_order, $menu_terjual, $promo_terpakai, $first_created_at, $last_created_at, $penjualanID);
} else {
    // Insert new report
    $report_sql = "INSERT INTO report (PenjualanID, UserID, judul, deskripsi, penjualan_per_hari, total_order, menu_terjual, promo_terpakai, rekapan_penilaian, Start_Date, End_Date, created_at, updated_at)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 4, ?, ?, NOW(), NOW())";
    $stmt = $conn->prepare($report_sql);
    $stmt->bind_param("iissiiiiss", $penjualanID, $UserID, $judul, $deskripsi, $penjualan_per_hari, $total_order, $menu_terjual, $promo_terpakai, $first_created_at, $last_created_at);
}

$stmt->execute();
$stmt->close();


// Tutup koneksi
$conn->close();
?>
