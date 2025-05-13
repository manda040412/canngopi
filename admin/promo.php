<?php
session_start();
// Include the connection.php file to connect to the database
include('../connection.php');

if (!isset($_SESSION['UserID']) || empty($_SESSION['UserID'])) {
    // Redirect to index.php if no session is found
    header("Location: index.php");
    exit();
}

// Query to fetch 'Discount yang terus berjalan'
$discountQuery = "SELECT DiscountID, nama_discount, persentase FROM discount WHERE kategori = 'AlwaysOn'";
$discountResult = $conn->query($discountQuery);

$VoucherQuery = "
SELECT 
    VoucherID, 
    voucher, 
    CASE 
        WHEN IFNULL(persentase, 0) > 0 THEN CONCAT(IFNULL(persentase, 0), '%')
        WHEN IFNULL(potongan, 0) > 0 THEN CONCAT('Rp ', FORMAT(IFNULL(potongan, 0), 0))
        ELSE 'Tidak Ada Diskon'
    END AS diskon 
FROM voucher 
WHERE masa_berlaku > NOW()
AND deleted_at IS NULL";
$voucherResult = $conn->query($VoucherQuery);

// Query to fetch 'Discount khusus'
$specialDiscountQuery = "SELECT DiscountID, nama_discount, persentase FROM discount WHERE masa_berlaku > NOW() AND kategori = 'Seasonal'";
$specialDiscountResult = $conn->query($specialDiscountQuery);

// Query to fetch 'Promo Aktif'
$promoQuery = "SELECT 
                p.PromoID, 
                p.nama_menu, 
                MAX(m.image) AS image, -- Memilih satu gambar dari menu terkait
                p.masa_berlaku, 
                GROUP_CONCAT(m.nama_menu SEPARATOR ', ') AS daftar_menu -- Gabungkan nama menu terkait
            FROM promo p
            JOIN menu_promo mp ON p.PromoID = mp.PromoID
            JOIN menu m ON mp.MenuID = m.MenuID
            WHERE p.masa_berlaku > NOW() 
            AND p.deleted_at IS NULL 
            GROUP BY p.PromoID, p.nama_menu, p.masa_berlaku
            ORDER BY p.masa_berlaku ASC";
$promoResult = $conn->query($promoQuery);

$bundlingQuery = "SELECT 
                B.BundlingID, 
                B.nama_menu, 
                MAX(m.image) AS image, -- Pilih satu gambar dari menu terkait
                B.harga, 
                B.harga_baru, 
                B.masa_berlaku, 
                B.deskripsi,
                GROUP_CONCAT(m.nama_menu SEPARATOR ', ') AS daftar_menu
            FROM bundling B
            JOIN menu_bundling mb ON B.BundlingID = mb.BundlingID
            JOIN menu m ON mb.MenuID = m.MenuID
            WHERE B.masa_berlaku > NOW() 
            AND B.deleted_at IS NULL 
            GROUP BY B.BundlingID, B.nama_menu, B.harga, B.harga_baru, B.masa_berlaku, B.deskripsi
            ORDER BY B.masa_berlaku ASC";
$bundlingResult = $conn->query($bundlingQuery);

$historyPromoQuery = "SELECT 
                    p.PromoID,
                    p.nama_menu, 
                    MAX(m.image) AS image, -- Pilih satu gambar dari menu terkait
                    p.masa_berlaku,
                    GROUP_CONCAT(m.nama_menu SEPARATOR ', ') AS daftar_menu
                FROM promo p
                JOIN menu_promo mp ON p.PromoID = mp.PromoID
                JOIN menu m ON mp.MenuID = m.MenuID
                WHERE p.masa_berlaku <= NOW() 
                AND p.deleted_at IS NULL 
                GROUP BY p.PromoID, p.nama_menu, p.masa_berlaku
                ORDER BY p.masa_berlaku DESC";
$historyPromoResult = $conn->query($historyPromoQuery);

// Check for errors in promo queries
if (!$promoResult || !$bundlingResult) {
    die("Error in query: " . $conn->error);
}

// Close connection after the results are fetched
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promo Page</title>

    <!-- box icons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <style>
        body {
            margin: 0;
            font-family: Poppins, sans-serif;
            background: linear-gradient(to right, #D90101, #990000);
            display: flex;
            padding: 0;
            min-height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 250px;
            background-color: #f2f2f2;
            color: #757575;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 40px;
            padding-right: 10px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            position: fixed; 
            top: 30px; 
            left: 30px; 
            bottom: 30px;
            z-index: 1000;
        }

        .sidebar img {
            width: 100px;
            margin-bottom: 30px;
        }

        .sidebar a {
            text-decoration: none;
            color: #757575;
            padding: 15px 20px;
            width: 100%;
            display: flex;
            align-items: center;
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 10px;
            transition: background-color 0.3s, color 0.3s;
        }

        .sidebar a.active {
            background-color: #ffc4c4;
            padding-left: 1px;
            padding-right: 1px;
            color: #aa1919;
            border-radius: 0 50px 50px 0;
            font-weight: 600;
        }

        .sidebar a i {
            margin-left: 30px;
            margin-right: 30px;
            font-size: 24px;
        }
        .sidebar a:hover {
            background-color: #d0d0d0; 
            padding-left: 1px;
            padding-right: 1px;
            color: #313131; 
            border-radius: 0 50px 50px 0;
            font-weight: 600;
            transition: background-color 0.3s, color 0.3s;
            z-index: 950;
        }

        .sidebar .logout {
            margin-left: 200px;
            margin-top: auto;
            padding-bottom: 30px;
            color: #757575;
        }

        .content {
            padding-top: 5px;
            padding-left: 30px;
            padding-right: 30px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            position: fixed; 
            top: 30px; 
            left: 320px; 
            right: 30px; 
            bottom: 30px; 
            overflow-y: auto; 
            z-index: 900;
        }

        
        .content::-webkit-scrollbar {
           display: none;
        }


        
        .section-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            padding-top: -20px;
        }

        .promo-section {
            display: flex;
            align-items: center;
        }

        .promo-section h2 {
            font-size: 26px;
            font-weight: bold;
            color: #333;
            margin-right: 20px;
        }

        .promo-section p {
            font-size: 16px;
            color: #999;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .promo-section p i {
            margin-right: 8px;
        }

        .promo-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
            margin-top: 1px;
        }

        .promo-buttons button {
            background-color: #AA1919;
            border: none;
            color: white;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .promo-buttons button i {
            margin-left: 8px;
        }

        .promo-buttons button.secondary {
            background-color: white;
            color: #AA1919;
            border: 2px solid #AA1919;
        }

        .discount-section {
            margin-bottom: 20px;
            margin-top: 0px;
            padding-top: 0px;
        }

        .discount-section h3 {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .discount-list {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .discount-list a{
            text-decoration: none; 
            color: #990000; 
            display: block; 
        }

        .discount-badge {
            background-color: #ffc4c4;
            color: #AA1919;
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: bold;
        }

        .promo-list {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .promo-item {
            background-color: #fff;
            padding: 15px;
            border-radius: 15px;
            box-shadow: 0px 0px 8px 3px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .promo-list a {
            text-decoration: none; 
            color: inherit; 
            display: block; 
        }

        .promo-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 0px;
        }

        
 
        .promo-item h4 {
            font-size: 16px;
            color: #AA1919;
            margin-bottom: 5px;
        }

        .promo-item p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        .promo-item:hover{
            transform: translateY(-5px);

        }

        .more-button {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
            margin-bottom: 40px;
        }

        .more-button button {
            background-color: #AA1919;
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
        }

        .divider {
            border: none;
            border-top: 1px solid #cccccc;
            margin-top: 30px;
            margin-bottom: -10px;
        }

        .history-promo-list {
            display: flex;
            overflow-x: auto;
            gap: 15px;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .history-promo-item {
            min-width: 200px;
            background-color: #fff;
            padding: 15px;
            border-radius: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        

        .history-promo-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .history-promo-item h4 {
            font-size: 16px;
            color: #AA1919;
            margin-bottom: 5px;
        }

    </style>
</head>

<body>
<div class="sidebar">
        <img src="image/logo_canngopi.png" alt="Logo">
        <a href="monitoring.php">
            <i class='bx bx-stats'></i> Monitoring
        </a>
        <a href="report.php">
            <i class='bx bxs-report'></i> Report
        </a>
        <a href="menu.php">
            <i class='bx bxs-food-menu'></i> Menu
        </a>
        <a href="promo.php" class="active">
            <i class='bx bxs-purchase-tag'></i> Promo
        </a>
        <a href="events.php">
            <i class='bx bxs-calendar-event'></i> Events
        </a>
        <a href="access.php">                             
            <i class='bx bxs-key'></i> Access
        </a>
        <a href="index.php" class="logout">Logout</a>
    </div>

    <div class="content">
        <div class="promo-section">
            <h2>Discount</h2>
            <p><i class='bx bx-info-circle'></i>Discount memberikan potongan harga dari kategori tertentu ataupun keseluruhan harga</p>
        </div>

        <div class="promo-buttons">
            <button onclick="location.href='promo_tambah_discount.php'">Tambah Discount <i class='bx bx-plus'></i></button>
            <button class="secondary" onclick="location.href='promo_generate_voucher.php'">Generate Voucher <i class='bx bx-plus'></i></button>
        </div>

        <div class="discount-section">
            <h3>Discount yang terus berjalan</h3>
            <div class="discount-list">
                <?php
                if ($discountResult->num_rows > 0) {
                    while ($row = $discountResult->fetch_assoc()) {
                        echo '<a href="promo_detail_discount.php?DiscountID=' . $row['DiscountID'] . '" class="discount-badge">' . $row['nama_discount'] . ' ' . $row['persentase'] . '%</a>';
                    }
                } else {
                    echo "No ongoing discounts available.";
                }
                ?>
            </div>
        </div>

        <!-- Discount khusus -->
        <div class="discount-section">
            <h3>Discount khusus</h3>
            <div class="discount-list">
                <?php
                if ($specialDiscountResult->num_rows > 0) {
                    while ($row = $specialDiscountResult->fetch_assoc()) {
                        echo '<a href="promo_detail_discount.php?DiscountID=' . $row['DiscountID'] . '" class="discount-badge">' . $row['nama_discount'] . ' ' . $row['persentase'] . '%</a>';
                    }
                } else {
                    echo "No special discounts available.";
                }
                ?>
            </div>
        </div>
        
        <div class="discount-section">
            <h3>Voucher yang valid</h3>
            <div class="discount-list">
                <?php
                if ($voucherResult->num_rows > 0) {
                    while ($row = $voucherResult->fetch_assoc()) {
                        echo '<a href="editvoucher.php?VoucherID=' . $row['VoucherID'] . '" class="discount-badge">' . $row['voucher'] . ' ' . $row['diskon'] . '</a>';
                    }
                } else {
                    echo "No ongoing voucher available.";
                }                
                ?>
            </div>
            <hr class="divider">
        </div>

        <div class="discount-section">
            <div class="promo-section">
                <h2>Promo</h2>
                <p><i class='bx bx-info-circle'></i>Promo memberikan potongan harga spesial dan atau pada event tertentu</p>
            </div>
            <div class="promo-buttons">
                <button onclick="location.href='tambah_promo.php'">Tambah Promo <i class='bx bx-plus'></i></button>
                <button class="secondary" onclick="location.href='promo_buat_bundling.php'">Buat Bundling <i class='bx bx-plus'></i></button>
            </div>
        </div>

        <!-- Promo Aktif Section -->
        <div>
        <div class="section-title">Promo Aktif</div>
        <div class="promo-list">
            <?php
            if ($promoResult->num_rows > 0) {
                while($promo = $promoResult->fetch_assoc()) {
                    if (!empty($promo['image'])) {
                        // Mengambil base URL
                        $baseURL = "https://cobaadmin.canngopi.com";
                        
                        // Menghapus '/admin' jika ada dalam path
                        $imagePath = str_replace('/admin', '', $promo['image']);

                    // Format the date
                    $masaBerlaku = date("d F Y", strtotime($promo['masa_berlaku']));

                    // Display each promo item with a dynamic link
                    echo '<a href="promo_detail_aktif.php?PromoID=' . $promo['PromoID'] . '" class="promo-item">';
                    echo '<img src="' . htmlspecialchars($baseURL . $imagePath) . '" alt="' . htmlspecialchars($promo['nama_menu']) . '">';
                    } else {
                        // Placeholder jika tidak ada gambar
                        echo '<img src="https://via.placeholder.com/150" alt="Tidak ada gambar">';
                        }
                    echo '<h4>' . htmlspecialchars($promo['nama_menu']) . '</h4>';
                    echo '<p>Aktif hingga ' . $masaBerlaku . '</p>';
                    echo '</a>';
                }
            } else {
                echo "<p>No active promos available.</p>";
            }
            ?>
        </div>

            <div class="more-button">
                <button onclick="location.href='promo_aktif.php'">Lebih Banyak</button>
            </div>
        </div>

        <!-- Promo Bundling Aktif Section -->
        <div>
        <div class="section-title">Promo Bundling Aktif</div>
        <div class="promo-list">
            <?php
            if ($bundlingResult->num_rows > 0) {
                while($bundling = $bundlingResult->fetch_assoc()) {
                    if (!empty($bundling['image'])) {
                        // Mengambil base URL
                        $baseURL = "https://cobaadmin.canngopi.com";
                        
                        // Menghapus '/admin' jika ada dalam path
                        $imagePath = str_replace('/admin', '', $bundling['image']);

                    // Format the date
                    $masaBerlaku = date("d F Y", strtotime($bundling['masa_berlaku']));

                    // Display each bundling promo item with a dynamic link
                    echo '<a href="promo_detail_bundling.php?BundlingID=' . $bundling['BundlingID'] . '" class="promo-item">';
                    echo '<img src="' . htmlspecialchars($baseURL . $imagePath) . '" alt="' . htmlspecialchars($bundling['nama_menu']) . '">';
                    } else {
                        // Placeholder jika tidak ada gambar
                        echo '<img src="https://via.placeholder.com/150" alt="Tidak ada gambar">';
                        }
                    echo '<h4>' . htmlspecialchars($bundling['nama_menu']) . '</h4>';
                    echo '<p>' . htmlspecialchars($bundling['deskripsi']) . '</p>';
                    echo '<p class="price">Rp ' . number_format($bundling['harga'], 0, ',', '.') . '</p>';
                    echo '<p class="price">Rp ' . number_format($bundling['harga_baru'], 0, ',', '.') . '</p>';
                    echo '<p>Aktif hingga ' . $masaBerlaku . '</p>';
                    echo '</a>';
                }
            } else {
                echo "<p>No active bundling promos available.</p>";
            }
            ?>
        </div>
            <div class="more-button">
                <button onclick="location.href='promo_aktif_bundling.php'">Lebih Banyak</button>
            </div>
        </div>

    </div>        
</body>

</html>
