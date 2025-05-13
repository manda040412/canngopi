<?php
session_start();
// Include the connection.php to use the $conn object
include('../connection.php');

date_default_timezone_set('Asia/Jakarta'); // Set to your desired time zone


if (!isset($_SESSION['userID']) || empty($_SESSION['userID'])) {
    // Redirect to index.php if not logged in
    header("Location: index.php");
    exit(); // Ensure no further code is executed
}


 // Reset pesananmakanan to 0 when the page loads
 $homereset = "UPDATE status SET Status = 0 WHERE StatusID = 6";
 $conn->query($homereset);

 // Reset pesananmakanan to 0 when the page loads
 $sqlReset = "UPDATE status SET Status = 1 WHERE StatusID = 1";
 $conn->query($sqlReset);

// Reset pesananmakanan to 0 when the page loads
 $sqlReset = "UPDATE status SET Status = 1 WHERE StatusID = 7";
 $conn->query($sqlReset);


// Pastikan user sudah login dan memiliki userID yang valid
if (!isset($_SESSION['userID']) || empty($_SESSION['userID'])) {
    die("User belum login atau ID pengguna tidak valid.");
}

$userID = $_SESSION['userID'];

// Query to retrieve category data
$sql = "SELECT * FROM kategori";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $categories = array();
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
} else {
    $categories = array();
}

// Query to retrieve menu data
$sql = "
    SELECT m.MenuID, m.nama_menu, m.image, m.harga, k.kategori
    FROM menu m
    JOIN kategori k ON m.KategoriID = k.KategoriID
";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $menu_data = array();
    while ($row = $result->fetch_assoc()) {
        $menu_data[] = $row;
    }
} else {
    $menu_data = array();
}

// Fetch Promo data
$promoQuery = "SELECT * FROM promo WHERE masa_berlaku >= CURDATE() AND deleted_at IS NULL";
$promoResult = $conn->query($promoQuery);

if ($promoResult->num_rows > 0) {
    $promo_data = array();
    while ($row = $promoResult->fetch_assoc()) {
        $promo_data[] = $row;
    }
} else {
    $promo_data = array();
}

// Fetch Bundling data
$bundlingQuery = "SELECT * FROM bundling WHERE masa_berlaku >= CURDATE() AND deleted_at IS NULL";
$bundlingResult = $conn->query($bundlingQuery);

if ($bundlingResult->num_rows > 0) {
    $bundling_data = array();
    while ($row = $bundlingResult->fetch_assoc()) {
        $bundling_data[] = $row;
    }
} else {
    $bundling_data = array();
}

// Query untuk mengambil data diskon
$sql = "SELECT DiscountID, nama_discount, persentase, masa_berlaku FROM discount WHERE masa_berlaku >= CURDATE() AND deleted_at IS NULL";
$result = $conn->query($sql);

// Query untuk mengambil data diskon
$sql = "SELECT VoucherID, Voucher, Persentase, Potongan, jumlah, Deskripsi, Syarat, masa_berlaku, created_at, updated_at, deleted_at FROM voucher WHERE Masa_Berlaku >= CURDATE() AND deleted_at IS NULL";
$resultV = $conn->query($sql);

// Periksa apakah ada order dengan status "processing" untuk user ini
$sql = "SELECT OrderID FROM order_list WHERE userID = ? AND status = 'processing'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Jika ada pesanan processing, ambil OrderID-nya
    $stmt->bind_result($orderID);
    $stmt->fetch();
    $_SESSION['OrderID'] = $orderID;
} else {
    // Jika tidak ada, buat pesanan baru dengan status "processing"
    $sql = "INSERT INTO order_list (userID, status) VALUES (?, 'processing')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userID);

    if ($stmt->execute()) {
        // Simpan OrderID yang baru dibuat dalam sesi
        $_SESSION['OrderID'] = $conn->insert_id;
    } else {
        echo "Gagal membuat pesanan: " . $stmt->error;
    }

    $stmt->close();
}

// Query untuk mengambil data pesanan
$sql = "
    SELECT 
        ol.OrderID, ol.Total_harga, ol.Total_promo, ol.status,
        oi.OrderItemID, oi.MenuID, oi.Quantity, oi.sub_total, oi.Notes, m.nama_menu
    FROM order_list AS ol
    JOIN order_items AS oi ON ol.OrderID = oi.OrderID
    JOIN menu AS m ON oi.MenuID = m.MenuID
    WHERE ol.OrderID = ? AND ol.status = 'processing'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orderID);
$stmt->execute();
$orderResult = $stmt->get_result();

// Inisialisasi variabel subtotal, discount, dan flag
$subtotal = 0;
$totalDiscount = 0;
$totalAmount = 0;
$persentase = 0; 
$jenisDiskon = null;

// Ambil data terbaru dari tabel order_list berdasarkan OrderID
$sqlOrder = "SELECT Total_promo, Total_harga, DiscountID, VoucherID, PromoID, BundlingID FROM order_list WHERE OrderID = ?";
$stmtOrder = $conn->prepare($sqlOrder);
$stmtOrder->bind_param("i", $orderID);
$stmtOrder->execute();
$resultOrder = $stmtOrder->get_result();
$orderRow = $resultOrder->fetch_assoc();

if ($orderRow) {
    $totalDiscount = $orderRow['Total_promo'] ?? 0;
    $totalAmount = $orderRow['Total_harga'] ?? 0;
    $DiscountID = $orderRow['DiscountID'] ?? null;
    $voucherID = $orderRow['VoucherID'] ?? null;
    $PromoID = $orderRow['PromoID'] ?? null;
    $BundlingID = $orderRow['BundlingID'] ?? null;

    // Prioritaskan cek diskon, promo, bundling, atau voucher
    if (!empty($voucherID)) {
        // Ambil detail voucher
        $sqlVoucher = "SELECT Persentase FROM voucher WHERE VoucherID = ?";
        $stmtVoucher = $conn->prepare($sqlVoucher);
        $stmtVoucher->bind_param("i", $voucherID);
        $stmtVoucher->execute();
        $resultVoucher = $stmtVoucher->get_result();
        $voucherRow = $resultVoucher->fetch_assoc();

        if ($voucherRow) {
            $persentase = $voucherRow['Persentase'];
            $jenisDiskon = "Voucher";
        }
    } elseif (!empty($DiscountID)) {
        // Ambil detail diskon
        $sqlDiscount = "SELECT persentase FROM discount WHERE DiscountID = ?";
        $stmtDiscount = $conn->prepare($sqlDiscount);
        $stmtDiscount->bind_param("i", $DiscountID);
        $stmtDiscount->execute();
        $resultDiscount = $stmtDiscount->get_result();
        $discountRow = $resultDiscount->fetch_assoc();

        if ($discountRow) {
            $persentase = $discountRow['persentase'];
            $jenisDiskon = "Discount";
        }
    } elseif (!empty($PromoID)) {
        // Ambil detail promo
        $sqlPromo = "SELECT pengurangan_harga FROM promo WHERE PromoID = ?";
        $stmtPromo = $conn->prepare($sqlPromo);
        $stmtPromo->bind_param("i", $PromoID);
        $stmtPromo->execute();
        $resultPromo = $stmtPromo->get_result();
        $promoRow = $resultPromo->fetch_assoc();
    
        if ($promoRow) {
            $fixedDiscount = $promoRow['pengurangan_harga']; // Fixed amount discount
            $jenisDiskon = "Promo";
        }
    } elseif (!empty($BundlingID)) {
        // Ambil detail bundling
        $sqlBundling = "SELECT potongan FROM bundling WHERE BundlingID = ?";
        $stmtBundling = $conn->prepare($sqlBundling);
        $stmtBundling->bind_param("i", $BundlingID);
        $stmtBundling->execute();
        $resultBundling = $stmtBundling->get_result();
        $bundlingRow = $resultBundling->fetch_assoc();
    
        if ($bundlingRow) {
            $fixedDiscount = $bundlingRow['potongan']; // Fixed amount discount
            $jenisDiskon = "Bundling";
        }
    }
    
}

// Jika subtotal dihitung ulang
$subtotal = $totalAmount + $totalDiscount;

// Tutup koneksi
$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Order Menu</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <!-- <script src="kasirFunction.js"></script> -->

    <script>
        function addToOrder(type, id) {
            console.log(`Sending Type: ${type}, ID: ${id}`);
            fetch('add_to_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        type,
                        id
                    }),
                })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json(); // Parse JSON response
                })
                .then((data) => {
                    console.log('Response from PHP:', data); // Log the response for debugging
                    if (data.success) {
                        // alert('Item berhasil ditambahkan ke pesanan.');
                        window.location.href = 'betaKasir.php';
                    } else {
                        alert(`Gagal menambahkan item ke pesanan. Error: ${data.message}`);
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menambahkan item.');
                });
        }
    </script>

    <style>
        .btn-pesan {
            color: white;
            background-color: #C47676;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin: auto;
            font-size: 12px;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .btn-pesan:hover {
            background-color: #7D0000;
            color: white;
        }

        .edit-icon {
            color: white;
            background-color: #C47676;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 4px;
            margin-left: 10px;
            /* font-size: 12px; */
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
            height: 27px;
        }

        .edit-icon:hover {
            background-color: #7D0000;
            color: white;
        }

        .trash-icon {
            color: white;
            background-color: #C47676;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 4px;
            margin-left: 10px;
            /* font-size: 12px; */
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
            height: 20px;
        }

        .trash-icon:hover {
            background-color: #7D0000;
            color: white;
        }

        .popup-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 90%;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            animation: fadeIn 0.3s ease-in-out;
            flex-direction: column;
        }
        .sidebar a.active {
            font-weight: bold;
            color: #8C1D1D;
        }
        
        // tambahan
    
    .sidebar a.active {
    font-weight: bold;
    color: #8C1D1D; /* Warna teks aktif */
    background-color: #FFF0F0; /* Latar belakang aktif */
    border-left: 4px solid #8C1D1D; /* Indikator aktif */
}

        .content {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px; /* Jarak antar elemen */
    width: 100%; /* Pastikan kontainer mengambil seluruh lebar */
    box-sizing: border-box; /* Termasuk padding dalam ukuran total */
}
        //sampai sini
        
        .menu-item[data-category="Promo"] {
            border: 2px solid #FFD700;
            padding: 10px;
            margin: 10px;
            background-color: #FFFBEA;
        }

        .menu-item[data-category="Bundling"] {
            border: 2px solid #FF4500;
            padding: 10px;
            margin: 10px;
            background-color: #FFE6E6;
        }
        
        .menu-item {
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    text-align: center;
    padding: 10px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.menu-item img {
    width: 100%;
    height: 150px; /* Atur tinggi gambar */
    object-fit: cover;
    border-radius: 8px 8px 0 0;
}

.menu-item h3 {
    margin: 0;
    font-size: 18px; /* Smaller font size */
    color: #282828;
    line-height: 1.2em; /* Maintain line height */
    padding-top: 8px; /* Adjust padding */
    padding-bottom: 8px; /* Adjust padding */
}

.menu-item p {
    margin: 0;
    font-size: 16px; /* Smaller font size */
    color: #282828;
    padding-bottom: 10px; /* Adjust padding */
    /* font-weight: bold; */
}

.menu-item:hover {
    transform: translateY(-5px); /* Slight hover effect */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Enhance shadow on hover */
}
/* List item pesanan */
.order-items {
    max-height: 100px; /* Batas tinggi dengan fitur scroll */
    overflow-y: auto;
    padding: 0;
    margin: 0;
    list-style: none;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
}

.menu-container {
    max-height: 100px; /* Atur tinggi maksimal setiap item menu agar hanya setengahnya terlihat */
    overflow: hidden; /* Menyembunyikan bagian yang melebihi batas tinggi */
    margin-bottom: 10px; /* Memberikan jarak antar menu */
}

.order-summary-container {
    width: 330px;
    padding: 30px 20px 30px 20px;
    border-radius: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-items: center;
    gap: 20px;
}
.headerReceipt{
    display: flex;
    flex-direction: column;
    width: 100%;
}
.capsulation {
    display: flex;
    height: 100vh;
}
.order-summary-box {
    display: flex;
    flex-direction: column;
    flex-grow: 1; 
    background-color: white;
    border-radius: 15px;
    padding: 20px;
    width: 330px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    box-sizing: border-box;
    justify-content: space-between;
}

.order-summary-box-notes {
    height: 50%;
    background-color: white;
    border-radius: 15px;
    padding: 20px;
    width: 380px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    box-sizing: border-box;
    overflow-y: auto;
    overflow-x: hidden;
    margin-top: 30px;
    margin-left: 5px;
}

.order-summary-box-notes::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.receiptFooter {
    margin-top: 15px; /* Memberikan jarak antara bagian footer dan elemen di atasnya */
}

.divider-list {
    margin-bottom: 10px; /* Menambahkan jarak bawah antara divider dan order totals */
    border-top: 1px solid #ddd; /* Menambahkan garis pemisah */
}

.order-totals {
    padding-top: -55px; /* Menambahkan padding atas dalam order totals */
    padding-bottom: -55px; /* Menambahkan padding bawah dalam order totals */
    gap: -5px; /* Menambahkan jarak antar baris dalam order totals */
}

.order-totals p {
    margin: 2px 0; /* Memberikan jarak antar paragraf dalam order totals */
}

.order-totals .total {
    font-weight: bold; /* Membuat teks total lebih tebal untuk menonjolkan total */
}

.order-totals .subtotal, .order-totals .discount {
    font-weight: normal; /* Teks subtotal dan discount tidak terlalu tebal */
}

.discount-container {
    width: 90%;
    background-color: white;
    padding: 10px;
    border-radius: 15px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.discount-container h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #999;
}

.confirm-button {
    width: 50%;
    background-color: #7D0000;
    color: white;
    border: none;
    border-radius: 10px;
    padding: 10px;
    font-size: 16px;
    cursor: pointer;
    margin-top: 5px;
    font-weight: 600;
}

.confirm-button:hover {
    background-color: #B30000;
}
//sampai sini

        .voucher-buttons {
            display: flex;
            flex-direction: column;
            /* overflow-x: auto; */
            gap: 10px;
            scrollbar-width: none;
        }

        .voucher-buttons::-webkit-scrollbar {
            display: none;
        }

        .voucher-btn {
            color: white;
            background-color: #C47676;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            font-size: 12px;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .voucher-btn:hover {
            background-color: #7D0000;
            color: white;
        }

        .discount-buttons {
            display: flex;
            flex-direction: column;
            /* overflow-x: auto; */
            gap: 10px;
            scrollbar-width: none;
        }

        .discount-btn {
            color: white;
            background-color: #C47676;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            font-size: 12px;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .discount-btn:hover {
            background-color: #7D0000;
            color: white;
        }

        .discount-container {
            width: 90%;
            background-color: white;
            padding: 15px;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: grid;
        }

        .discount-container h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #999;
        }

        .discount-container button {
            color: white;
            background-color: #C47676;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin: 5px;
            font-size: 12px;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .discount-container button:hover {
            background-color: #7D0000;
            color: white;
        }
        
        .MenuSidebar-footer {
    margin-top: auto;
    margin-bottom: 80px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}
    </style>
</head>


<body>
    <div class="MenuSidebar">
        <a href="hapusOrder.php" class="MenuSidebar-item">
            <img src="images/shopping-bag-regular-240.png" alt="Icon 1" class="icon">
            <span class="label"> Home Screen</span>
        </a>
        <a href="betaRiwayat.php" class="MenuSidebar-item">
            <img src="images/history-regular-240.png" alt="Icon 2" class="icon">
            <span class="label">Riwayat pesanan</span>
        </a>
        <div class="MenuSidebar-footer">
            <img src="logo/logo.png" alt="Sign Out" class="icon">
            <span class="label">Sign Out</span>
        </div>
    </div>
    <div class="containerKatalog">
        <div class="sticky-section">
            <h1>Pesan makanan</h1>
            <p>
                <?php
                // Cek apakah session sudah dimulai
                if (session_status() === PHP_SESSION_NONE) {
                    session_start(); // Mulai session jika belum dimulai
                }

                // Cek apakah userName dan kategori_user sudah tersimpan di session
                if (isset($_SESSION['userName']) && isset($_SESSION['kategori_user'])) {
                    // Tampilkan nama dan kategori_user dari session
                    echo $_SESSION['kategori_user'] .  ": " .  $_SESSION['userName'];
                } else {
                    echo "User not logged in.";
                }
                ?>
            </p>
        </div>
        <div class="divider"></div>
        <div class="bagiDua">
            <div class="sidebar">
                <ul>
                    <h3>Menu Kategori</h3>
                    <li><a href="#" data-item="All" class="active">All</a></li>
                    <?php foreach ($categories as $category): ?>
                        <li>
                            <a href="#" data-item="<?php echo htmlspecialchars($category['kategori']); ?>">
                                <?php echo htmlspecialchars($category['kategori']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <h3>Promo/Bundling</h3>
                    <li><a href="#" data-item="Promo">Promo</a></li>
                    <li><a href="#" data-item="Bundling">Bundling</a></li>
                </ul>
            </div>

            <!-- Container Menu -->
            <div class="container-menu">
                <div class="headline-menu">
                    <h1>Menu makanan</h1>
                </div>
                <!-- Content -->
                <div class="content">
                    <?php foreach ($menu_data as $menu_item) { ?>
                        <div class="menu-item" data-category="<?php echo htmlspecialchars($menu_item['kategori']); ?>" value="<?php echo htmlspecialchars($menu_item['MenuID']); ?>">
                            <?php if (!empty($menu_item['image'])) { 
                            // Menyusun base URL dan mengonversi path gambar di database
                            $baseURL = "https://cobaadmin.canngopi.com"; // Base URL domain Anda
                            $imagePath = str_replace('/admin', '', $menu_item['image']); // Menghapus '/admin' agar URL bisa mengaksesnya
                            
                            // Menampilkan gambar
                            ?>
                            <img src="<?php echo htmlspecialchars($baseURL . $imagePath); ?>" alt="<?php echo htmlspecialchars($menu_item['nama_menu']); ?>">
                            <?php } else { ?>
                            <img alt="Tidak ada gambar"> 
                            <?php } ?>
                            <h3><?php echo htmlspecialchars($menu_item['nama_menu']); ?></h3>
                            <p>Rp <?php echo number_format($menu_item['harga'], 0, ',', '.'); ?></p>
                            <button class="btn-pesan" onclick="window.location.href='pesanan.php?MenuID=<?php echo htmlspecialchars($menu_item['MenuID']); ?>'">Pesan</button>
                        </div>
                    <?php } ?>
                    <!-- Promo Section -->
                    <?php foreach ($promo_data as $promo_item) { ?>
                    <div class="menu-item" data-category="Promo">
                        <h3><?php echo htmlspecialchars($promo_item['nama_menu']); ?></h3>
                        <p>Potongan harga: Rp <?php echo number_format($promo_item['pengurangan_harga'], 0, ',', '.'); ?></p>
                        <p><?php echo htmlspecialchars($promo_item['deskripsi']); ?></p>
                        <small>Berlaku sampai: <?php echo htmlspecialchars($promo_item['masa_berlaku']); ?></small>
                        <button class="btn-pesan" onclick="addToOrder('Promo', '<?php echo $promo_item['PromoID']; ?>')">Tambahkan ke Pesanan</button>
                    </div>

                    <?php } ?>

                    <!-- Bundling Section -->
                    <?php foreach ($bundling_data as $bundling_item) { ?>
                        <div class="menu-item" data-category="Bundling">
                            <h3><?php echo htmlspecialchars($bundling_item['nama_menu']); ?></h3>
                            <p>Harga Normal: Rp <?php echo number_format($bundling_item['harga'], 0, ',', '.'); ?></p>
                            <p>Harga Bundle: Rp <?php echo number_format($bundling_item['harga_baru'], 0, ',', '.'); ?></p>
                            <p><?php echo htmlspecialchars($bundling_item['deskripsi']); ?></p>
                            <small>Berlaku sampai: <?php echo htmlspecialchars($bundling_item['masa_berlaku']); ?></small>
                            <button class="btn-pesan" onclick="addToOrder('Bundling', '<?php echo $bundling_item['BundlingID']; ?>')">Tambahkan ke Pesanan</button>
                        </div>

                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    </div>

    <div class="capsulation">
        <div class="order-summary-container">
            <div class="order-summary-box">
                <div class="headerReceipt">
                    <h2 class="order-id">Order ID <span class="order-number">#<?php echo $_SESSION['OrderID']; ?></span></h2>
                    <ul class="order-items" id="receipt-list">
                        <?php if ($orderResult->num_rows > 0) : ?>
                            <?php
                            $subtotal = 0;
                            $hasItems = false;

                            while ($orderRow = $orderResult->fetch_assoc()) : {
                                    $orderItemID = $orderRow['OrderItemID'];  // Mendapatkan OrderItemID
                                }
                                if (!empty($orderRow['MenuID'])) :
                                    $subtotal += $orderRow['sub_total'];
                                    $hasItems = true;
                            ?>
                                    <div class="menu-container" style="margin-bottom: 10px;" value="<?php echo htmlspecialchars($menu_item['MenuID']); ?>">
                                        <div class="menu-item-list">
                                            <div class="menu-description">
                                                <h3><?php echo $orderRow['nama_menu']; ?></h3>
                                                <p><?php echo $orderRow['Notes']; ?></p>
                                            </div>
                                            <div class="menu-quantity">
                                                <p><?php echo $orderRow['Quantity']; ?>x</p>
                                            </div>
                                            <div class="menu-price">
                                                <p>Rp <?php echo number_format($orderRow['sub_total'], 0, ',', '.'); ?></p>
                                            </div>
                                        </div>
                                        <div class="edit-icon" onclick="window.location.href='pesanan.php?MenuID=<?php echo htmlspecialchars($orderRow['MenuID']); ?>'">
                                            <i class='bx bx-edit'></i>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endwhile; ?>

                        <?php else : ?>
                            <p style="font-size:small; font-style: normal; ">Belum ada item dalam pesanan ini.</p>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="receiptFooter">
                    <div class="divider-list"></div>
                    <div class="order-totals">
                        <p>Sub - Total <span class="subtotal">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span></p>
                        <p>
                            <?php
                            echo $jenisDiskon ? $jenisDiskon : "Discount";
                            ?>
                            (<?php echo $persentase; ?>%)
                            <span class="discount">- Rp <?php echo number_format($totalDiscount, 0, ',', '.'); ?></span>
                        </p>
                        <p class="total">Total <span class="total-amount">Rp <?php echo number_format($totalAmount, 0, ',', '.'); ?></span></p>
                    </div>
                </div>
            </div>

            <div class="discount-container">
                <h3 style="display: flex; justify-content: space-between; align-items:center;">Discount dan Voucher
                    <div class="trash-icon" onclick="removeDiscount(OrderID)">
                        <i class='bx bx-trash'></i>
                    </div>
                </h3>
                <button id="openDiscount">Discount</button>
                <button id="openVoucher">Voucher</button>
            </div>

            <!-- Discount -->
            <div id="popupDiscount" class="modal hidden">
                <div class="modal-content">
                    <h2 style="display: flex; justify-content: space-between; align-items:center;">Discount
                        <i class="bx bx-x" id="closeDiscount"></i>
                    </h2>
                    <div class="discount-buttons">
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<button class="discount-btn" 
                                    data-discountid="' . $row["DiscountID"] . '" 
                                    data-persentase="' . $row["persentase"] . '"
                                    data-masa="' . $row["masa_berlaku"] . '">
                                ' . $row["nama_discount"] . ' (' . $row["persentase"] . '%)</button>';
                            }
                        } else {
                            echo "Tidak ada diskon tersedia";
                        }
                        ?>
                    </div>
                </div>
            </div>


            <!-- Voucher -->
            <div id="popupVoucher" class="modal hidden">
                <div class="modal-content">
                    <h2 style="display: flex; justify-content: space-between;">Voucher
                        <i class="bx bx-x" id="closeVoucher"></i>
                    </h2>
                    <div class="voucher-buttons">
                        <?php
                        if ($resultV->num_rows > 0) {
                            while ($row = $resultV->fetch_assoc()) {
                                if ($row["jumlah"] > 0) {
                                    // Tentukan nilai untuk ditampilkan
                                    $nilaiDiskon = $row["Persentase"] > 0 ? $row["Persentase"] . '%' : 'Rp ' . number_format($row["Potongan"], 0, ',', '.');
                                    // $persentase = !is_null($row["Persentase"]) ? $row["Persentase"] : 0; // Gunakan 0 jika NULL
                                    $potongan = !is_null($row["Potongan"]) ? $row["Potongan"] : 0; // Gunakan 0 jika NULL

                                    echo '<button class="voucher-btn" 
                                    data-voucherid="' . $row["VoucherID"] . '" 
                                    data-persentase="' . $persentase . '"
                                    data-potongan="' . $potongan . '" 
                                    data-deskripsi="' . htmlspecialchars($row["Deskripsi"], ENT_QUOTES) . '" 
                                    data-syarat="' . htmlspecialchars($row["Syarat"], ENT_QUOTES) . '" 
                                    data-masa="' . $row["masa_berlaku"] . '"
                                    data-jumlah="' . $row["jumlah"] . '">
                            ' . $row["Voucher"] . ' ' . '(' . $nilaiDiskon . ')' . '</button>';
                                }
                            }
                        } else {
                            echo "Tidak ada voucher tersedia";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Tombol Konfirmasi -->
            <button class="confirm-button" id="confirm-button">Confirm</button>
        </div>
    </div>
    
    <!-- Tombol pemilihan pesanan -->
    <div id="order-popup" class="popup">
        <div class="popup-content">
            <h2>Pilih Tipe Penjualan</h2>
            <div class="order-option-container">
                <button class="order-option" data-option="DineIn" id="Dine-in" onclick="saveOrderOption('DineIn', OrderID)">
                    <img src="images/dine-in.png" alt="Dine-in" />
                </button>
                <button class="order-option" data-option="TakeAway" id="Take-Away" onclick="saveOrderOption('TakeAway', OrderID)">
                    <img src="images/takeaway.png" alt="Take Away" />
                </button>
            </div>
            <button id="close-popup">Close</button>
        </div>
    </div>
    <!-- signout -->
    <div id="popupSignout" class="modal hidden">
        <div class="modal-content">
            <p>Are you sure you want to sign out?</p>
            <button id="konfirmSignout">Yes</button>
            <button id="cancelSignout">No</button>
        </div>
    </div>
    <script>

    document.addEventListener("DOMContentLoaded", function () {
        const categoryLinks = document.querySelectorAll(".sidebar a");
        const menuItems = document.querySelectorAll(".menu-item");

        categoryLinks.forEach(link => {
            link.addEventListener("click", function (e) {
                e.preventDefault(); // Prevent default link behavior

                const category = this.getAttribute("data-item");

                menuItems.forEach(item => {
                    if (category === "All" || item.getAttribute("data-category") === category) {
                        item.style.display = "block"; // Show matching items
                    } else {
                        item.style.display = "none"; // Hide non-matching items
                    }
                });

                // Update active category styling
                categoryLinks.forEach(link => link.classList.remove("active"));
                this.classList.add("active");
            });
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
        const categoryLinks = document.querySelectorAll(".sidebar a");
        const menuItems = document.querySelectorAll(".menu-item");

        categoryLinks.forEach(link => {
            link.addEventListener("click", function (e) {
                e.preventDefault();

                const category = this.getAttribute("data-item");

                menuItems.forEach(item => {
                    if (category === "All" || item.getAttribute("data-category") === category) {
                        item.style.display = "block";
                    } else {
                        item.style.display = "none";
                    }
                });

                categoryLinks.forEach(link => link.classList.remove("active"));
                this.classList.add("active");
            });
        });
    });

    // popup Discount & Voucher
    const Discount = document.getElementById('popupDiscount');
        const Voucher = document.getElementById('popupVoucher');
        const closeDiscount = document.getElementById('closeDiscount');
        const closeVoucher = document.getElementById('closeVoucher');

        openDiscount.addEventListener('click', function() {
            Discount.classList.remove('hidden');
        });

        openVoucher.addEventListener('click', function() {
            Voucher.classList.remove('hidden');
        });

        closeDiscount.addEventListener('click', function() {
            Discount.classList.add('hidden');
        });

        closeVoucher.addEventListener('click', function() {
            Voucher.classList.add('hidden');
        });

        // AJAX untuk discount btn
        document.querySelectorAll('.discount-btn').forEach(button => {
            button.addEventListener('click', function () {
                const discountID = this.getAttribute('data-discountid');
                const persentase = parseFloat(this.getAttribute('data-persentase')) || 0;
                const orderID = <?php echo $orderID; ?>; // Ensure this is set to the current OrderID

                console.log("Order ID: ", orderID);
                console.log("Discount ID: ", discountID);
                console.log("Percentage Discount: ", persentase);

                const xhr = new XMLHttpRequest();
                xhr.open("POST", "discount.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            console.log(response); // Log the response for debugging

                            if (response.success) {
                                location.reload(); // Automatically reload the page to show the updated data
                            } else {
                                if (response.redirect) {
                                    window.location.href = response.redirect; // Redirect if 'redirect' is set
                                }
                            }
                        } catch (e) {
                            console.error("Error parsing JSON: ", e);
                        }
                    }
                };
                xhr.send("orderID=" + orderID + "&discountID=" + discountID + "&persentase=" + persentase);
            });
        });


        // Ajax untuk voucher btn
        document.querySelectorAll('.voucher-btn').forEach(button => {
            button.addEventListener('click', function() {
                const voucherID = this.getAttribute('data-voucherid');
                const persentase = parseFloat(this.getAttribute('data-persentase')) || 0;
                const potongan = parseFloat(this.getAttribute('data-potongan')) || 0;
                const orderID = <?php echo $orderID; ?>; // Ensure this is set to the current OrderID

                // Check if necessary variables are set correctly
                console.log("Order ID: ", orderID);
                console.log("Voucher ID: ", voucherID);
                console.log("Percentage Discount: ", persentase);
                console.log("Fixed Discount: ", potongan);

                const xhr = new XMLHttpRequest();
                xhr.open("POST", "voucher.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        // Ensure the response is valid JSON
                        try {
                            const response = JSON.parse(xhr.responseText);
                            console.log(response); // Log the response
                            if (response.success) {
                                // alert("Diskon diterapkan. Total harga setelah diskon: " + response.totalAmount);
                                location.reload(); // Reload page to show updated order details
                            } else {
                                alert("Error: " + response.error);
                            }
                        } catch (e) {
                            console.error("Error parsing JSON: ", e);
                        }
                    }
                };
                xhr.send("orderID=" + orderID + "&voucherID=" + voucherID + "&persentase=" + persentase + "&potongan=" + potongan);
            });
        });

    // AJAX unutuk Update Total
    function updateTotal(orderID) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "update_total.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    document.querySelector('.total-amount').innerText = 'Rp ' + response.totalAmount.toLocaleString('id-ID');
                    console.log("Total berhasil diperbarui di database.");
                } else {
                    console.error("Error:", response.error);
                }
            }
        };
        xhr.send("orderID=" + orderID);
    }

    // Panggil fungsi ini setelah perhitungan selesai
    document.addEventListener("DOMContentLoaded", function() {
        const orderID = <?php echo $orderID; ?>; // Ganti dengan cara Anda mendapatkan orderID
        updateTotal(orderID);
    });

    // AJAX untuk menyimpan Tipe Penjualan
    const OrderID = <?php echo $orderID; ?>;

    document.getElementById('confirm-button').addEventListener('click', function() {
        openPopup();
    });

    function openPopup() {
        document.getElementById('order-popup').style.display = 'flex';
    }

    document.getElementById('close-popup').addEventListener('click', function() {
        document.getElementById('order-popup').style.display = 'none';
    });

    function saveOrderOption(TipePenjualan, OrderID) {
        document.getElementById('order-popup').style.display = 'none';

        const xhr = new XMLHttpRequest();
        xhr.open("POST", "tipePenjualan.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                // window.location.href = 'dinein.php';

                if (TipePenjualan === 'DineIn') {
                    window.location.href = "dinein.php";
                }
                if (TipePenjualan === 'TakeAway') {
                    window.location.href = "takeaway.php";
                }
            }
        };
        xhr.send("TipePenjualan=" + encodeURIComponent(TipePenjualan) + "&OrderID=" + encodeURIComponent(OrderID));
    }

    // AJAX untuk hapus discount
    function removeDiscount(OrderID) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'hapusPromo.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        // Kirim OrderID untuk proses penghapusan
        xhr.send('OrderID=' + OrderID);

        // Proses ketika respon dari server diterima
        xhr.onload = function() {
            if (xhr.status === 200) {
                location.reload(); // Reload halaman untuk melihat perubahan
            } else {
                alert('Terjadi kesalahan saat menghapus diskon.');
            }
        };
    }
        
    // AJAX untuk promo/bundling btn
    document.querySelectorAll('.promo-btn, .bundling-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id'); // ID Promo atau Bundling
            const type = this.getAttribute('data-type'); // 'Promo' atau 'Bundling'
            const orderID = <?php echo $orderID; ?>; // Pastikan ini mendefinisikan OrderID aktif

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "update_promo_bundling.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        location.reload(); // Reload untuk memperbarui tampilan
                    } else {
                        alert("Error: " + response.error);
                    }
                }
            };
            xhr.send("orderID=" + orderID + "&id=" + id + "&type=" + type);
        });
    });

    </script>
    <script src="kasirFunction.js"></script>
</body>

</html>