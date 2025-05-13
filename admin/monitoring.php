<?php
session_start();
require_once('../connection.php');

try {
   // Default to the current month
    $currentDate = date('Y-m-d');
    $startDate = date('Y-m-01'); // First day of the month
    $endDate = date('Y-m-t 23:59:59'); // Last day of the month
    
    // Get user-selected filter type
    $filterType = $_GET['filter_type'] ?? 'monthly';
    $customStart = $_GET['start_date'] ?? null;
    $customEnd = $_GET['end_date'] ?? null;
    
    // Determine the date range based on filter type
    switch ($filterType) {
        case 'daily':
            $startDate = $currentDate;
            $endDate = date('Y-m-d 23:59:59', strtotime($currentDate)); 
            break;
        case 'weekly':
            $startDate = date('Y-m-d', strtotime('monday this week'));
            $endDate = date('Y-m-d 23:59:59', strtotime('sunday this week')); 
            break;
        case 'monthly':
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t 23:59:59'); 
            break;
        case 'quarterly':
            $currentMonth = date('m');
            $currentYear = date('Y');
            $quarterStartMonths = [1 => '01', 2 => '04', 3 => '07', 4 => '10'];
            $quarter = ceil($currentMonth / 3);
            $startDate = "$currentYear-{$quarterStartMonths[$quarter]}-01";
            $endDate = date('Y-m-t 23:59:59', strtotime("$startDate +2 months")); 
            break;
        case 'custom':
        if ($customStart && $customEnd) {
            $startDate = date('Y-m-d 00:00:00', strtotime($customStart));
            $endDate = date('Y-m-d 23:59:59', strtotime($customEnd));
        }
        break;

    }
    
    // First Query: Promo and Discount Data
   $query = "
       SELECT 
    (SELECT SUM(total_promo) FROM (
        SELECT ol.OrderID, MAX(ol.total_promo) AS total_promo
        FROM order_list ol
        JOIN promo pr ON ol.PromoID = pr.PromoID
        JOIN (SELECT DISTINCT OrderID, PenjualanID FROM order_penjualan) op ON ol.OrderID = op.OrderID
        JOIN penjualan p ON op.PenjualanID = p.PenjualanID
        WHERE ol.PromoID IS NOT NULL AND p.created_at BETWEEN ? AND ?
        GROUP BY ol.OrderID
    ) AS subquery) AS total_promo,

    (SELECT SUM(total_discount) FROM (
        SELECT ol.OrderID, MAX(ol.total_promo) AS total_discount
        FROM order_list ol
        JOIN discount d ON ol.DiscountID = d.DiscountID
        JOIN (SELECT DISTINCT OrderID, PenjualanID FROM order_penjualan) op ON ol.OrderID = op.OrderID
        JOIN penjualan p ON op.PenjualanID = p.PenjualanID
        WHERE ol.DiscountID IS NOT NULL AND p.created_at BETWEEN ? AND ?
        GROUP BY ol.OrderID
    ) AS subquery) AS total_discount,

    (SELECT SUM(total_voucher) FROM (
        SELECT ol.OrderID, MAX(ol.total_promo) AS total_voucher
        FROM order_list ol
        JOIN voucher v ON ol.VoucherID = v.VoucherID
        JOIN (SELECT DISTINCT OrderID, PenjualanID FROM order_penjualan) op ON ol.OrderID = op.OrderID
        JOIN penjualan p ON op.PenjualanID = p.PenjualanID
        WHERE ol.VoucherID IS NOT NULL AND p.created_at BETWEEN ? AND ?
        GROUP BY ol.OrderID
    ) AS subquery) AS total_voucher,

    (SELECT SUM(total_bundling) FROM (
        SELECT ol.OrderID, MAX(ol.total_promo) AS total_bundling
        FROM order_list ol
        JOIN bundling b ON ol.BundlingID = b.BundlingID
        JOIN (SELECT DISTINCT OrderID, PenjualanID FROM order_penjualan) op ON ol.OrderID = op.OrderID
        JOIN penjualan p ON op.PenjualanID = p.PenjualanID
        WHERE ol.BundlingID IS NOT NULL AND p.created_at BETWEEN ? AND ?
        GROUP BY ol.OrderID
    ) AS subquery) AS total_bundling;
";


    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssssss', 
        $startDate, $endDate,  // total_promo
        $startDate, $endDate,  // total_discount
        $startDate, $endDate,  // total_voucher
        $startDate, $endDate   // total_bundling
    );
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $total_promo = $data['total_promo'];
    $total_discount = $data['total_discount'];
    $total_voucher = $data['total_voucher'];
    $total_bundling = $data['total_bundling'];
    $stmt->close();
    
    // Query for sales distribution based on category
  $distributionQuery = "
    SELECT 
        k.kategori AS kategori_makanan, 
        SUM(sub.total_quantity) AS total_terjual 
    FROM (
        -- Menghitung total quantity per kategori dalam tiap order
        SELECT oi.OrderID, m.KategoriID, SUM(oi.Quantity) AS total_quantity
        FROM order_items oi
        JOIN menu m ON oi.MenuID = m.MenuID
        GROUP BY oi.OrderID, m.KategoriID  -- Kelompokkan per OrderID & Kategori
    ) AS sub
    JOIN (
        -- Menghapus duplikasi OrderID dalam order_penjualan
        SELECT DISTINCT OrderID, PenjualanID FROM order_penjualan
    ) op ON sub.OrderID = op.OrderID
    JOIN penjualan p ON op.PenjualanID = p.PenjualanID
    JOIN kategori k ON sub.KategoriID = k.KategoriID
    WHERE p.created_at BETWEEN ? AND ?
    GROUP BY k.kategori
";



    // Prepare and execute the sales distribution query
    $stmt = $conn->prepare($distributionQuery);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $distributionResult = $stmt->get_result();
    
    // Fetch the distribution data
    $distributionData = $distributionResult->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Prepare data for the chart
    $categories = array_column($distributionData, 'kategori_makanan');
    $totals = array_column($distributionData, 'total_terjual');
    
    // Second Query: Revenue and Sales Data
    $sql_query = "
        SELECT 
            (SELECT COUNT(DISTINCT op.OrderID) 
             FROM penjualan p
             JOIN order_penjualan op ON op.PenjualanID = p.PenjualanID
             WHERE p.created_at BETWEEN ? AND ?) AS total_transactions,  
    
       (SELECT COALESCE(SUM(sub.total_quantity), 0)
         FROM (
             -- Hitung total quantity per OrderID sebelum bergabung dengan order_penjualan
             SELECT oi.OrderID, SUM(oi.Quantity) AS total_quantity
             FROM order_items oi
             GROUP BY oi.OrderID
         ) AS sub
         JOIN (
             -- Pastikan setiap OrderID hanya muncul sekali di order_penjualan
             SELECT DISTINCT OrderID, PenjualanID FROM order_penjualan
         ) AS op ON sub.OrderID = op.OrderID
         JOIN penjualan p ON op.PenjualanID = p.PenjualanID
         WHERE p.created_at BETWEEN ? AND ?
        ) AS total_items_sold,



            (SELECT COALESCE(SUM(p.penjualan_per_hari), 0) 
             FROM penjualan p 
             WHERE p.created_at BETWEEN ? AND ?) AS netsales,
    
            (SELECT COALESCE(SUM(p.promo_terpakai), 0) 
             FROM penjualan p 
             WHERE p.created_at BETWEEN ? AND ?) AS total_discount,
    
            ((SELECT COALESCE(SUM(p.penjualan_per_hari), 0) 
              FROM penjualan p 
              WHERE p.created_at BETWEEN ? AND ?) 
             + 
             (SELECT COALESCE(SUM(p.promo_terpakai), 0) 
              FROM penjualan p 
              WHERE p.created_at BETWEEN ? AND ?)) AS total_revenue
    ";
    
    $stmt = $conn->prepare($sql_query);
    $stmt->bind_param('ssssssssssss', 
        $startDate, $endDate,  // total_transactions
        $startDate, $endDate,  // total_items_sold
        $startDate, $endDate,  // total_revenue
        $startDate, $endDate,  // total_discount
        $startDate, $endDate,  // netsales (total revenue)
        $startDate, $endDate   // netsales (total discount)
    );
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc() ?? [
        'total_transactions' => 0, 
        'total_items_sold' => 0, 
        'netsales' => 0, 
        'total_revenue' => 0, 
        'total_discount' => 0
    ];
    $stmt->close();
    
    // Check for revenue data
    error_log("Revenue Data: " . print_r($data, true));
    
    // Combine both sets of data (promo + revenue)
    $combinedData = array_merge($data, [
        'total_promo' => $total_promo,
        'total_discount' => $total_discount,
        'total_voucher' => $total_voucher,
        'total_bundling' => $total_bundling
    ]);

    // Function to format currency
    function format_currency($amount) {
        return number_format($amount, 0, ',', '.') . ' IDR';
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Page</title>

    <!-- box icons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <style>
        body {
            margin: 0;
            font-family: Poppins, sans-serif;
            background: linear-gradient(to right, #D90101, #990000);
            display: flex;
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

        .sidebar a i {
            margin-left: 30px;
            margin-right: 30px;
            font-size: 24px;
        }

        .sidebar .logout {
            margin-left: 200px;
            margin-top: auto;
            padding-bottom: 30px;
            color: #757575;
        }

        .content1, .content2 {
            flex-grow: 1;
            padding: 20px;
            background-color: white;
            border-radius: 15px;
            margin-top: 10px;
            margin-left: 30px;
            margin-right: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .content1 {
            margin-top: 10px;
            margin-bottom: 30px;
            height: 650px;
            display: flex; /* Added this */
            justify-content: space-between; /* This will space the sections evenly */
        }
        .content2 {
            margin-top: 0px;
            margin-bottom: 10px;
            height: 400px;
        }

        .section {
            margin-left: 270px; 
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header button {
            background-color: #6C0000;
            border: 2px solid #6C0000;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 16px;
        }

        .header h2 {
            margin-top: 20px;
            margin-left: 20px;
            color: black;
            font-size: 26px;
        }
        .header h3 {
            margin-top: 5px;
            margin-left: 5px;
            color: black;
            font-size: 16px;
        }
        .info1{
            display: flex;
            justify-content: space-between;
            align-items: center;
            float: left;
        }
        .info2{
            display: flex;
            justify-content: space-between;
            align-items: center;
            float: right;
        }
        .sectionkiri {
            width: 50%; /* Adjusted to make it responsive */
            margin-bottom: 30px;
        }

        .sectionkanan {
            padding-top: 40px;
            width: 45%; /* Adjusted to make it responsive */
            margin-left: 20px; /* Add some space between kiri and kanan */
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            text-align: center;
        }
        th {
            background-color: #C8C8C8;
        }
        tr:nth-child(even) {
            background-color: #F2F2F2;
        }
        tr:nth-child(odd) {
            background-color: #E4E4E4;
        }
        tr:first-child th:first-child {
            border-top-left-radius: 5px;
        }
        tr:first-child th:last-child {
            border-top-right-radius: 5px;
        }
        tr:last-child td:first-child {
            border-bottom-left-radius: 5px;
        }
        tr:last-child td:last-child {
            border-bottom-right-radius: 5px;
        }
        .History{
            float: right;
        }

        .overall-report-container{
            padding: 0;
            margin-right: 10px;
            min-width: 240px;
            max-height: 400px;
            box-sizing: border-box;
        }
        .overall-report-container h3{
            font-size: 20px;
        }

        .overall-report-container h1{
            margin-top: 5px;
            background: linear-gradient(180deg, #B71C1C 0%, #510C0C 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .info-section h1{
            font-size: 20px;
            margin-top: 5px;
            background: linear-gradient(180deg, #B71C1C 0%, #510C0C 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .info-section{
            display: flex;
            flex-wrap: wrap;
            flex-direction: column;
            justify-content: space-between;
            align-items: baseline;
            margin-left: 20px;
            max-height: 475px; /* Batasi tinggi untuk memicu overflow */
            column-gap: 20px; /* Jarak antara kolom */
        }
        .filter-container {
            display: flex;
            width: 100%;
            background: #fff;
            padding: 10px;
            border-radius: 10px;
        }
        .filter-container label, .filter-container select {
            font-weight: bold;
            margin-right: 10px;
            margin-bottom: 6px;
        }
        .filter-container input, .filter-container select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            margin-right: 10px;
        }
        .order-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 500px;
            margin: 0 auto;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .order-item:hover {
            transform: scale(1.02);
        }

        .column-Info {
            display: flex;
            flex-direction: column;
        }
        .durasi {
            display: flex;
            flex-direction: column;
        }

        .circle-button {
            width: 30px;
            height: 30px;
            margin-left : 10px;
            background-color: #E74C3C;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .circle-button:hover {
            background-color: #D43F2C;
        }

        .arrowButton {
            display: flex;
            align-items: center;
            justify-content: space-between;
        
        }

        .arrow {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-right: 3px solid white;
            border-top: 3px solid white;
            transform: rotate(45deg);
        }
    </style>
</head>

<body>
<div class="sidebar">
        <img src="image/logo_canngopi.png" alt="Logo">
        <a href="monitoring.php" class="active">
            <i class='bx bx-stats'></i> Monitoring
        </a>
        <a href="report.php">
            <i class='bx bxs-report'></i> Report
        </a>
        <a href="menu.php">
            <i class='bx bxs-food-menu'></i> Menu
        </a>
        <a href="promo.php">
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
    <div class="section">
        <div class="content1">
            <section class="sectionkiri">
                <div class="header">
                    <h2>Informasi Pesanan Realtime</h2>
                </div>
                <div class="filter-container">
                    <div class="column-Info">
                    <label for="filter-type">Pilih Filter:</label>
                    <select id="filter-type" name="filter_type" onchange="updateFilter()">
                        <option value="daily">Per Hari</option>
                        <option value="weekly">Per Minggu</option>
                        <option value="monthly">Per Bulan</option>
                        <option value="quarterly">Per 3 Bulan</option>
                        <option value="custom">Custom</option>
                    </select>

                    </div>
                    <div class="durasi">
                    <label for="start-date">Start Date:</label>
                    <input type="date" id="start-date" name="start_date" style="display:none;" onchange="updateFilter()">
                    </div>
                    <div class="durasi">
                    <label for="end-date">End date:</label>
                    <input type="date" id="end-date" name="end_date" style="display:none;" onchange="updateFilter()">
                    </div>
                </div>
                <div class="info-section">
                <div class="total-transaction-done">
                    <h3>Total Transaction Done</h3>
                    <h1><?= $data['total_transactions'] ?? 0; ?></h1>
                </div>
                <div class="total-item-sold">
                    <h3>Total Item Sold</h3>
                    <h1><?= $data['total_items_sold'] ?? 0; ?></h1>
                </div>
                <div class="total-revenue">
                    <h3>Total Revenue</h3>
                    <h1><?= format_currency($data['total_revenue'] ?? 0); ?></h1>
                </div>
                <div class="nett-sales">
                    <h3>Nett Sales</h3>
                    <h1><?= format_currency($data['netsales'] ?? 0); ?></h1>
                </div>
                <div class="total-discount">
                    <div class="arrowButton">
                        <h3>Total Discount</h3>
                        <a href="detaild.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&filter_type=<?php echo $filterType; ?>" class="circle-button">
                            <div class="arrow"></div>
                        </a>
                    </div>
                    <h1><?php echo format_currency($total_discount); ?></h1>
                </div>
                
                <div class="total-voucher">
                    <div class="arrowButton">
                        <h3>Total Voucher</h3>
                        <a href="detailv.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&filter_type=<?php echo $filterType; ?>" class="circle-button">
                            <div class="arrow"></div>
                        </a>
                    </div>
                    <h1><?php echo format_currency($total_voucher); ?></h1>
                </div>
                
                <div class="total-promo">
                    <div class="arrowButton">
                        <h3>Total Promo</h3>
                        <a href="detailp.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&filter_type=<?php echo $filterType; ?>" class="circle-button">
                            <div class="arrow"></div>
                        </a>
                    </div>
                    <h1><?php echo format_currency($total_promo); ?></h1>
                </div>
                
                <div class="total-bundling">
                    <div class="arrowButton">
                        <h3>Total Bundling</h3>
                        <a href="detailb.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&filter_type=<?php echo $filterType; ?>" class="circle-button">
                            <div class="arrow"></div>
                        </a>
                    </div>
                    <h1><?php echo format_currency($total_bundling); ?></h1>
                </div>

                    
                </div>
            </section>
            <section class="sectionkanan">
                <canvas id="salesPieChart" width="600" height="600"></canvas>
            </section>
        </div>
        <div class="content2">  
            <div class="header">
                <h2>Jadwal Pergantian Shift Kasir</h2>
            </div>
            <div class="table">
                <table>
                    <tr>
                        <th>Nama</th>
                        <th>Shift</th>
                        <th>Waktu</th>
                    </tr>

                    <?php

                    try {
                        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        
                        $stmt = $pdo->query("
                            SELECT 
                                k.nama, 
                                sh.Shift, 
                                CONCAT(sh.waktu_start, ' - ', sh.waktu_end) as waktu,
                                s.created_at
                            FROM 
                                historyshift hs
                            INNER JOIN
                                shiftkasir s ON hs.ShiftKasirID = s.ShiftKasirID
                            INNER JOIN 
                                user k ON s.UserID = k.UserID 
                            INNER JOIN 
                                shift sh ON s.ShiftID = sh.ShiftID
                            ORDER BY hs.created_at DESC
                            LIMIT 4
                        ");


                        // Display the rows in the table
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['Shift']) . "</td>";    
                            echo "<td>" . htmlspecialchars($row['waktu']) . "</td>";
                            echo "</tr>";
                        }

                    } catch (PDOException $e) {
                        echo "Error: " . $e->getMessage();
                    }
                    ?>


                </table>
            <div class="History">
                <div class="header">
                    <div>
                        <a href="history_pergantianshift.php">
                            <button>History</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
    <script>
        // Data from PHP
        const categories = <?php echo json_encode($categories); ?>;
        const totals = <?php echo json_encode($totals); ?>;

        // Render Pie Chart
        const ctx = document.getElementById('salesPieChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: categories,
                datasets: [{
                    data: totals,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',  // Soft Red
                        'rgba(54, 162, 235, 0.7)',  // Sky Blue
                        'rgba(75, 192, 192, 0.7)',  // Teal
                        'rgba(255, 206, 86, 0.7)',  // Sunny Yellow
                        'rgba(153, 102, 255, 0.7)', // Lavender Purple
                        'rgba(255, 159, 64, 0.7)',  // Warm Orange
                        'rgba(100, 181, 246, 0.7)', // Light Blue
                        'rgba(165, 214, 167, 0.7)', // Pastel Green
                        'rgba(240, 98, 146, 0.7)',  // Pink
                        'rgba(255, 238, 88, 0.7)',  // Bright Yellow
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',  
                        'rgba(54, 162, 235, 1)',  
                        'rgba(75, 192, 192, 1)',  
                        'rgba(255, 206, 86, 1)',  
                        'rgba(153, 102, 255, 1)', 
                        'rgba(255, 159, 64, 1)',  
                        'rgba(100, 181, 246, 1)', 
                        'rgba(165, 214, 167, 1)', 
                        'rgba(240, 98, 146, 1)',  
                        'rgba(255, 238, 88, 1)',
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Sales Distribution by Category'
                    }
                }
            }
        });
        
        document.addEventListener("DOMContentLoaded", function () {
        const urlParams = new URLSearchParams(window.location.search);
        const filterSelect = document.getElementById("filter-type");
        const startInput = document.getElementById("start-date");
        const endInput = document.getElementById("end-date");
    
        // Get values from URL parameters
        const filterType = urlParams.get("filter_type") || "monthly";
        const startDate = urlParams.get("start_date") || "";
        const endDate = urlParams.get("end_date") || "";
    
        // Set values based on URL parameters
        filterSelect.value = filterType;
        startInput.value = startDate;
        endInput.value = endDate;
    
        function toggleDateInputs() {
            const startLabel = document.querySelector("label[for='start-date']");
            const endLabel = document.querySelector("label[for='end-date']");
            
            if (filterSelect.value === "custom") {
                startInput.style.display = "inline-block";
                endInput.style.display = "inline-block";
                startLabel.style.display = "inline-block";
                endLabel.style.display = "inline-block";
            } else {
                startInput.style.display = "none";
                endInput.style.display = "none";
                startLabel.style.display = "none";
                endLabel.style.display = "none";
            }
        }

    
        function updateFilter() {
            const selectedFilter = filterSelect.value;
            let url = window.location.pathname + "?filter_type=" + selectedFilter;
    
            if (selectedFilter === "custom") {
                if (startInput.value && endInput.value) {
                    url += "&start_date=" + startInput.value + "&end_date=" + endInput.value;
                } else {
                    return; // Prevent navigation if dates are not selected
                }
            }
    
            window.location.href = url;
        }
    
        // Ensure correct UI state on page load
        toggleDateInputs();
    
        // Event listeners
        filterSelect.addEventListener("change", function () {
            toggleDateInputs();
            if (filterSelect.value !== "custom") {
                updateFilter();
            }
        });
    
        startInput.addEventListener("change", updateFilter);
        endInput.addEventListener("change", updateFilter);
    });
    </script>
</body>

</html>
