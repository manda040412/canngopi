<?php
header('Content-Type: application/json');

// Include file koneksi database
include('../connection.php');

// Ambil data dari POST
$penjualan_per_hari = $_POST['penjualan_per_hari'] ?? '';
$menu_terjual = $_POST['menu_terjual'] ?? '';
$promo_terpakai = $_POST['promo_terpakai'] ?? '';
$UserID = $_POST['UserID'] ?? '';
$Tanggal = $_POST['tanggal'] ?? '';
$promo_terpakai = $promo_terpakai ? $promo_terpakai : '0';

// Validasi input
if (empty($penjualan_per_hari) || empty($menu_terjual) || empty($UserID) || empty($Tanggal)) {
    echo json_encode(['success' => false, 'error' => 'Data tidak lengkap']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $Tanggal)) {
    echo json_encode(['success' => false, 'error' => 'Format tanggal tidak valid']);
    exit;
}

// Pastikan hanya menutup order untuk 2025-02-02
$target_date = '2025-03-12';
if ($Tanggal !== $target_date) {
    echo json_encode(['success' => false, 'error' => 'Hanya data untuk 02/02/2025 yang bisa diperbarui']);
    exit;
}

// Query untuk mendapatkan OrderID pertama dan terakhir dari tabel order_list
$start_orderID_sql = "SELECT MIN(OrderID) as start_orderID FROM order_list WHERE DATE(created_at) = '$target_date'";
$start_orderID_result = $conn->query($start_orderID_sql);
$start_orderID = $start_orderID_result->fetch_assoc()['start_orderID'];

$last_orderID_sql = "SELECT MAX(OrderID) as last_orderID FROM order_list WHERE DATE(created_at) = '$target_date'";
$last_orderID_result = $conn->query($last_orderID_sql);
$last_orderID = $last_orderID_result->fetch_assoc()['last_orderID'];

// Query untuk mendapatkan waktu order pertama dan terakhir
$start_order_sql = "SELECT created_at as first_created_at FROM order_list WHERE OrderID = '$start_orderID'";
$start_order_result = $conn->query($start_order_sql);
$first_created_at = $start_order_result->fetch_assoc()['first_created_at'];

$last_order_sql = "SELECT created_at as last_created_at FROM order_list WHERE OrderID = '$last_orderID'";
$last_order_result = $conn->query($last_order_sql);
$last_created_at = $last_order_result->fetch_assoc()['last_created_at'];

// Query untuk menghitung total order
$total_order_sql = "SELECT COUNT(OrderID) as total_order FROM order_list WHERE DATE(created_at) = '$target_date'";
$total_order_result = $conn->query($total_order_sql);
$total_order = $total_order_result->fetch_assoc()['total_order'];

// Periksa apakah sudah ada data di penjualan untuk tanggal 2025-02-01
$check_sql = "SELECT * FROM penjualan WHERE DATE(created_at) = '$target_date'";
$check_result = $conn->query($check_sql);

if ($check_result->num_rows > 0) {
    // Jika data sudah ada, lakukan update
    $sql = "UPDATE penjualan 
            SET UserID = '$UserID', penjualan_per_hari = '$penjualan_per_hari', menu_terjual = '$menu_terjual', promo_terpakai = '$promo_terpakai', updated_at = NOW() 
            WHERE DATE(created_at) = '$target_date'";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'Data berhasil diperbarui']);

        // Ambil penjualanID
        $penjualanID_sql = "SELECT PenjualanID FROM penjualan WHERE DATE(created_at) = '$target_date'";
        $penjualanID_result = $conn->query($penjualanID_sql);
        $penjualanID = $penjualanID_result->fetch_assoc()['PenjualanID'];

        // Ambil orderID dari tabel order_list
        $orderID_sql = "SELECT OrderID FROM order_list WHERE DATE(created_at) = '$target_date'";
        $orderID_result = $conn->query($orderID_sql);
        $orderID_rows = $orderID_result->fetch_all(MYSQLI_ASSOC);

        // Masukkan data ke dalam tabel order_penjualan
        foreach ($orderID_rows as $orderID_row) {
            $orderID = $orderID_row['OrderID'];
            $sql = "INSERT INTO order_penjualan (OrderID, PenjualanID) VALUES ('$orderID', '$penjualanID')";
            $conn->query($sql);
        }

        // Update data pada tabel report
        $report_sql = "UPDATE report
                       SET UserID = '$UserID',
                           judul = 'Daily Sales Report pada tanggal $target_date',
                           deskripsi = 'Report untuk penjualan yang terjadi pada tanggal $target_date',
                           penjualan_per_hari = '$penjualan_per_hari',
                           total_order = '$total_order',
                           menu_terjual = '$menu_terjual',
                           promo_terpakai = '$promo_terpakai',
                           rekapan_penilaian = '4',
                           Start_date = '$first_created_at', End_date = '$last_created_at',
                           updated_at = NOW()
                       WHERE PenjualanID = '$penjualanID' AND DATE(created_at) = '$target_date'";
        $conn->query($report_sql);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
} else {
    // Jika data belum ada, lakukan insert
    $sql = "INSERT INTO penjualan (UserID, penjualan_per_hari, menu_terjual, promo_terpakai, Date, created_at, updated_at) 
            VALUES ('$UserID', '$penjualan_per_hari', '$menu_terjual', '$promo_terpakai', '$target_date', NOW(), NOW())";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'Data berhasil ditambahkan']);

        // Ambil penjualanID
        $penjualanID_sql = "SELECT PenjualanID FROM penjualan WHERE DATE(created_at) = '$target_date'";
        $penjualanID_result = $conn->query($penjualanID_sql);
        $penjualanID = $penjualanID_result->fetch_assoc()['PenjualanID'];

        // Ambil orderID dari tabel order_list
        $orderID_sql = "SELECT OrderID FROM order_list WHERE DATE(created_at) = '$target_date'";
        $orderID_result = $conn->query($orderID_sql);
        $orderID_rows = $orderID_result->fetch_all(MYSQLI_ASSOC);

        // Masukkan data ke dalam tabel order_penjualan
        foreach ($orderID_rows as $orderID_row) {
            $orderID = $orderID_row['OrderID'];
            $sql = "INSERT INTO order_penjualan (OrderID, PenjualanID) VALUES ('$orderID', '$penjualanID')";
            $conn->query($sql);
        }

        // Masukkan data ke dalam tabel report
        $report_sql = "INSERT INTO report (PenjualanID, UserID, judul, deskripsi, penjualan_per_hari, total_order, menu_terjual, promo_terpakai, rekapan_penilaian, Start_Date, End_Date, created_at, updated_at) 
                       VALUES ('$penjualanID', '$UserID', 'Daily Sales Report pada tanggal $target_date', 'Report untuk penjualan yang terjadi pada tanggal $target_date', '$penjualan_per_hari', '$total_order', '$menu_terjual', '$promo_terpakai', '4', '$first_created_at', '$last_created_at', NOW(), NOW())";
        $conn->query($report_sql);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}

// Menutup koneksi
$conn->close();
?>
