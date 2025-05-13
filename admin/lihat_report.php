<?php
session_start();
// Include the database connection
include('../connection.php');

if (!isset($_SESSION['UserID']) || empty($_SESSION['UserID'])) {
    // Redirect to index.php if no session is found
    header("Location: index.php");
    exit();
}

// Get the ReportID from URL
$reportID = isset($_GET['ReportID']) ? $_GET['ReportID'] : null;

if ($reportID) {
    // Fetch report details
    date_default_timezone_set('UTC');
    
    $sql = "SELECT * FROM report WHERE ReportID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reportID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $report = $result->fetch_assoc();
        $tanggal_target = date('Y-m-d', strtotime($report['created_at']));
    } else {
        echo "No report found with the given ID.";
        exit();
    }
    
    if ($tanggal_target) {
        // Query to get the latest UserID from order_list for the target date
        $sql = "SELECT UserID FROM order_list WHERE DATE(created_at) = '$tanggal_target' LIMIT 1";
        $result = mysqli_query($conn, $sql);
    
        if ($row = mysqli_fetch_assoc($result)) {
            $userID = $row['UserID'];
        }
    }
        
    // Fetch order data related to the report from Order_list based on PenjualanID
    $sql_transactions = "
    SELECT 
        ol.OrderID, 
        u.nama AS Nama_Kasir, 
        COALESCE(d.nama_discount, b.nama_menu, pr.nama_menu, v.Voucher, 'Tidak ada' ) AS Jenis_Discount,
        CASE
            WHEN v.Persentase > 0 THEN CONCAT(v.Persentase, '%')
            WHEN v.Potongan > 0 THEN CONCAT('Rp ', v.Potongan)
            ELSE CONCAT (d.persentase, '%')
        END AS Persentase_Discount,
        oi_agg.Total_Sub_Total AS Sub_Total, 
        ol.total_promo AS Discount_IDR, 
        ol.total_harga AS Total_Bayar_IDR,
        p.PenjualanID
    FROM 
        order_list ol
    JOIN 
        (
            -- Subquery to calculate Sub_Total per OrderID
            SELECT 
                OrderID, 
                SUM(sub_total) AS Total_Sub_Total
            FROM 
                order_items
            GROUP BY 
                OrderID
        ) AS oi_agg ON ol.OrderID = oi_agg.OrderID
    JOIN 
        order_penjualan op ON ol.OrderID = op.OrderID
    JOIN
        penjualan p ON op.PenjualanID = p.PenjualanID
    JOIN 
        user u ON ol.UserID = u.UserID
    JOIN
        report r ON p.PenjualanID = r.PenjualanID
    LEFT JOIN
        discount d ON ol.DiscountID = d.DiscountID
    LEFT JOIN
        bundling b ON ol.BundlingID = b.BundlingID
    LEFT JOIN
        promo pr ON ol.PromoID = pr.PromoID
    LEFT JOIN
        voucher v ON ol.VoucherID = v.VoucherID
    WHERE 
        r.ReportID = ?  -- Replace with actual filter if dynamic
        AND
        ol.status = 'completed'
    GROUP BY 
        ol.OrderID, u.nama, d.nama_discount, b.nama_menu, pr.nama_menu, v.Voucher, d.persentase, v.Persentase, v.Potongan,
        ol.total_promo, ol.total_harga, p.PenjualanID, oi_agg.Total_Sub_Total;
    ";

    $stmt_transactions = $conn->prepare($sql_transactions);
    $stmt_transactions->bind_param("i", $reportID); // This should match the type of PenjualanID, adjust if necessary
    $stmt_transactions->execute();
    $transactions_result = $stmt_transactions->get_result();

} else {
    echo "No ReportID provided.";
    exit();
}

// Fetch daily sales summary
    $sql_sales = "SELECT SUM(Total_harga) as total_harga FROM order_list WHERE status = 'completed' AND DATE(created_at) = ?";
    $stmt_sales = $conn->prepare($sql_sales);
    $stmt_sales->bind_param("s", $tanggal_target);
    $stmt_sales->execute();
    $sales_result = $stmt_sales->get_result();
    $penjualan_per_hari = ($sales_result->num_rows > 0) ? $sales_result->fetch_assoc()['total_harga'] : 0;

    // Fetch menu sold count
    $sql_menu = "SELECT SUM(Quantity) as menu_terjual FROM order_list WHERE status = 'completed' AND DATE(created_at) = ?";
    $stmt_menu = $conn->prepare($sql_menu);
    $stmt_menu->bind_param("s", $tanggal_target);
    $stmt_menu->execute();
    $menu_result = $stmt_menu->get_result();
    $menu_terjual = ($menu_result->num_rows > 0) ? $menu_result->fetch_assoc()['menu_terjual'] : 0;

    // Fetch used promotions count
    $sql_promo = "SELECT SUM(total_promo) as promo_terpakai FROM order_list WHERE status = 'completed' AND DATE(created_at) = ?";
    $stmt_promo = $conn->prepare($sql_promo);
    $stmt_promo->bind_param("s", $tanggal_target);
    $stmt_promo->execute();
    $promo_result = $stmt_promo->get_result();
    $promo_terpakai = ($promo_result->num_rows > 0) ? $promo_result->fetch_assoc()['promo_terpakai'] : 0;
    
// Ambil data khusus "StaffMeal"
$sql_menu_staff = "
    SELECT 
        m.nama_menu AS Nama_Menu,
        m.Image AS Image,
        u.nama AS Nama_Kasir,
        SUM(oi.Quantity) AS Quantity
    FROM order_items oi
    JOIN order_list ol ON oi.OrderID = ol.OrderID
    JOIN user u ON ol.UserID = u.UserID
    JOIN menu m ON oi.MenuID = m.MenuID
    JOIN kategori k ON m.KategoriID = k.KategoriID
    JOIN (SELECT DISTINCT OrderID, PenjualanID FROM order_penjualan) op ON ol.OrderID = op.OrderID
    JOIN (SELECT DISTINCT PenjualanID, ReportID FROM report) r ON op.PenjualanID = r.PenjualanID
    WHERE k.kategori = 'StaffMeal'
    AND r.ReportID = ?
    AND DATE(ol.created_at) = (SELECT DATE(created_at) FROM report WHERE ReportID = r.ReportID LIMIT 1)
    AND ol.status = 'completed'
    GROUP BY m.nama_menu, m.Image, u.nama
    ORDER BY Quantity DESC;

";

$stmt = $conn->prepare($sql_menu_staff);
$stmt->bind_param("i", $reportID);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);


$sql_staffmeal = "
    SELECT 
    (SELECT SUM(oi.Quantity)
     FROM order_items oi
     JOIN order_list ol ON oi.OrderID = ol.OrderID
     JOIN menu m ON oi.MenuID = m.MenuID
     JOIN kategori k ON m.KategoriID = k.KategoriID
     WHERE k.kategori = 'StaffMeal'
     AND DATE(ol.created_at) = DATE(r.created_at)
     AND ol.status = 'completed'
    ) AS total_staffmeal
FROM report r
WHERE r.ReportID = ?;
";

$stmt = $conn->prepare($sql_staffmeal);
$stmt->bind_param("i", $reportID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$totalStaffMeal = $row['total_staffmeal'] ?? 0;

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Report</title>
    <!-- box icons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: Poppins, sans-serif;
            background: linear-gradient(to bottom right, #D90101, #990000);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            box-sizing: border-box;
        }

        .report-container {
            padding:30px;          
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            position: fixed; 
            top: 30px; 
            left: 30px; 
            right: 30px; 
            bottom: 30px; 
            overflow-y: auto; 
            z-index: 900;
            box-sizing: border-box;
        }

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .header i {
            font-size: 28px;
            margin-right: 15px;
        }

        h3 {
            font-size: 24px;
            margin: 0;
        }

        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .form-group label {
            width: 150px;
            margin-right: 15px;
            font-size: 18px;
        }

        .form-group input[type="text"], 
        .form-group select {
            flex: 1;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: Poppins, sans-serif;
            margin-right: 500px;
        }

        .line {
            border: none; 
            height: 2px; 
            background-color: #d5d5d5;
            margin-top: 10px;
            width: 100%;
            margin-bottom: 20px;        
        }

        .vertical-line {
            width: 1px;
            height: 100%;
            min-height: 100px;
            background-color: #d5d5d5;
            margin: 0 20px;
        }

        .report-content{
            display: flex;
            flex-direction: row;
            justify-content: left;
            height: 400px;
            box-sizing: border-box;
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

        

        .button-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 25px;
            margin-bottom: 25px;
        }

        .table-container {
            max-height: 400px; /* Set a max height for the container around the table */
            width: 100%;
            border-collapse: collapse;
            overflow-y: auto;
            
        }

        .tabel-transaction-report {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .tabel-transaction-report th, 
        .tabel-transaction-report td {
            font-size: small;
            padding: 10px;
            text-align: left;
            box-sizing: border-box;
            padding-left: 50px;
        }

        .tabel-transaction-report thead {
            position: sticky;
            top: 0; /* Sticks to the top of the table-wrapper */
            background-color: #f8f8f8; /* Background color for the header */
            z-index: 1; /* Ensures the header stays on top */ 
            display: table-header-group;
           
        }

        .tabel-transaction-report tbody {
            display: table-row-group;
            height: auto; 
            width: 100%; 
        }

        .tabel-transaction-report tr {
            border-bottom: 1px solid #ddd;
            height: 50px;
            width: 100%;
            text-align: left;
        }

        .tabel-transaction-report tr:hover {
            background-color: #f1f1f1;
        }

        

        .download-button {
            padding: 10px 20px;
            background-color: #990000;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 60px;
        }

        .tabel-transaction-staff {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .tabel-transaction-staff th,
        .tabel-transaction-staff td {
            font-size: small;
            padding: 10px;
            text-align: left;
            box-sizing: border-box;
            padding-left: 50px;
        }

        .tabel-transaction-staff thead {
            position: sticky;
            top: 0;
            /* Sticks to the top of the table-wrapper */
            background-color: #f8f8f8;
            /* Background color for the header */
            z-index: 1;
            /* Ensures the header stays on top */
            display: table-header-group;

        }

        .tabel-transaction-staff tbody {
            display: table-row-group;
            height: auto;
            width: 100%;
        }

        .tabel-transaction-staff tr {
            border-bottom: 1px solid #ddd;
            height: 50px;
            width: 100%;
            text-align: left;
        }

        .tabel-transaction-staff tr:hover {
            background-color: #f1f1f1;
        }
        
        .hidden {
            display: 
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3); 
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

            .modal-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 2;
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
        
        .staff-button {
            padding: 8px 8px;
            background-color: #d90101;
            color: #fff;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            margin: 5px;
            display: flex;
            width: 50px;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .staff-button:hover {
            background-color: #6C0000;
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="header">
            <i class='bx bx-left-arrow-alt' style="cursor: pointer;" onclick="goBack()"></i>
            <h3>Detail Report</h3>
        </div>

        <form id="report-detail" action="#">
            <!-- Fill the form fields dynamically with PHP -->
            <div class="form-group">
                <label for="judul">Judul Laporan</label>
                <input type="text" id="judul" name="judul" value="<?= htmlspecialchars($report['judul']); ?>" readonly>
            </div>

            <div class="form-group">
                <label for="tanggal">Tanggal</label>
                <input type="text" id="tanggal" name="tanggal" value="<?= htmlspecialchars($report['Start_Date']); ?>" readonly>
            </div>

            <hr class="line">

            <div class="report-content">
                <div class="overall-report-container">
                    <div class="total-transaction-done">
                        <h3>Total Transaction Done</h3>
                        <h1><?= htmlspecialchars($report['total_order']); ?></h1>
                    </div>

                    <div class="total-item-sold">
                        <h3>Total Item Sold</h3>
                        <h1><?= htmlspecialchars($report['menu_terjual']); ?></h1>
                    </div>

                    <div class="total-revenue">
                        <h3>Total Revenue</h3>
                        <h1>Rp <?= htmlspecialchars(number_format($report['penjualan_per_hari'], 0, ',', '.')); ?></h1>
                    </div>

                    <div class="total-discount-given">
                        <h3>Total Discount Given</h3>
                        <h1>Rp <?= htmlspecialchars(number_format($report['promo_terpakai'], 0, ',', '.')); ?></h1>
                    </div>
                    <div class="total-discount">
                    <div class="arrowButton">
                        <h3>Staff Meal</h3>
                        <a class="circle-button" id="staffMeal">
                            <div class="arrow"></div>
                        </a>
                    </div>
                    <h1><?= $totalStaffMeal; ?></h1>
                </div>
                </div>

                <div class="vertical-line"></div>

                <div class="table-container">
                <table class="tabel-transaction-report">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Nama Kasir</th>
                            <th>Sub-Total</th>
                            <th>Discount</th>
                            <th>Jenis Discount</th>
                            <th>Total Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($transactions_result->num_rows > 0): ?>
                            <?php while($transaction = $transactions_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($transaction['OrderID']); ?></td>
                                    <td><?= htmlspecialchars($transaction['Nama_Kasir']); ?></td>
                                    <td>Rp <?= number_format($transaction['Sub_Total'], 0, ',', '.'); ?></td>
                                    <td>Rp <?= number_format($transaction['Discount_IDR'], 0, ',', '.'); ?></td>
                                    <td>
                                        <?= htmlspecialchars($transaction['Jenis_Discount']); ?>
                                        <?= htmlspecialchars($transaction['Persentase_Discount']); ?>
                                    </td>
                                    <td>Rp <?= number_format($transaction['Total_Bayar_IDR'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No transactions found for this report.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <div class="button-container">
                <a href="downloadreport.php?ReportID=<?= htmlspecialchars($report['ReportID']); ?>" class="download-button">Download</a>
            </div>
            
            <div id="popupStaff" class="modal hidden">
                <div class="modal-content">
                    <table class="tabel-transaction-staff">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Nama Menu</th>
                                <th>Nama Kasir</th>
                                <th>Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($rows)): ?>
    <?php foreach ($rows as $staffMeal): ?>
        <tr>
            <td>
                <?php 
                // Pastikan 'image' memiliki nilai
                if (!empty($staffMeal['image'])) { 
                    // Base URL domain Anda
                    $baseURL = "https://cobaadmin.canngopi.com"; 

                    // Path gambar langsung dari database tanpa menghapus '/admin'
                    $imagePath = $staffMeal['image'];

                    // Sanitasi URL agar aman
                    $fullImagePath = htmlspecialchars($baseURL . '/' . ltrim($imagePath, '/'));
                ?>
                    <img src="<?= $fullImagePath; ?>" alt="<?= htmlspecialchars($staffMeal['Nama_Menu']); ?>" width="100">
                <?php } else { ?>
                    <img src="https://via.placeholder.com/100" alt="Tidak ada gambar" width="100">
                <?php } ?>
            </td>
            <td><?= htmlspecialchars($staffMeal['Nama_Menu']); ?></td>
            <td><?= htmlspecialchars($staffMeal['Nama_Kasir']); ?></td>
            <td><?= htmlspecialchars($staffMeal['Quantity']); ?></td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="4">Tidak ada data tersedia</td>
    </tr>
<?php endif; ?>
                        </tbody>
                    </table>
                    <button1 class="staff-button" id="closeStaff">Close</button1>
                </div>
            </div>
            
        </form>
    </div>

    <script>
        function goBack() {
        window.location.href = 'report.php'; // Redirect to report.php
    }
    
       document.addEventListener("DOMContentLoaded", function() {
    const backButton = document.querySelector(".bx-left-arrow-alt");
    if (backButton) {
        backButton.addEventListener("click", goBack);
    }
});


        // popup staff meal
        document.addEventListener("DOMContentLoaded", function() {
    const staffMeal = document.getElementById("staffMeal");
    const popupStaff = document.getElementById("popupStaff");
    const closeStaff = document.getElementById("closeStaff");

    if (staffMeal && popupStaff && closeStaff) {
        staffMeal.addEventListener("click", function() {
            popupStaff.style.display = "flex"; // Tampilkan modal
        });

        closeStaff.addEventListener("click", function() {
            popupStaff.style.display = "none"; // Sembunyikan modal
        });

        // Menutup popup jika mengklik di luar modal-content
        popupStaff.addEventListener("click", function(event) {
            if (event.target === popupStaff) {
                popupStaff.style.display = "none";
            }
        });
    }
});
        
        document.querySelector('form').addEventListener('submit', function (e) {
            e.preventDefault();  // Prevent form submission for debugging
            console.log('Form is being submitted');
            this.submit();  // Allow form submission after log
        });
        
        function closeOrderAutomatically() {
    // Debug what values are being pulled from PHP
    console.log("Raw values from PHP:", {
        penjualan_per_hari: <?php echo json_encode($penjualan_per_hari ?? ''); ?>,
        menu_terjual: <?php echo json_encode($menu_terjual ?? ''); ?>,
        promo_terpakai: <?php echo json_encode($promo_terpakai ?? ''); ?>,
        UserID: <?php echo json_encode($userID ?? ''); ?>,
        tanggal: <?php echo json_encode($tanggal_target ?? ''); ?>
    });
    
    // Get data from PHP
    const penjualan_per_hari = <?php echo json_encode($penjualan_per_hari ?? ''); ?>;
    const menu_terjual = <?php echo json_encode($menu_terjual ?? ''); ?>;
    const promo_terpakai = <?php echo json_encode($promo_terpakai ?? ''); ?>;
    const UserID = <?php echo json_encode($userID ?? ''); ?>;
    const tanggal = <?php echo json_encode($tanggal_target ?? ''); ?>;
    
    // Check if UserID is valid
    if (!UserID) {
        console.error("UserID is missing or invalid");
        return;
    }
    // Validate other required fields
    if (!penjualan_per_hari || !menu_terjual || !tanggal) {
        console.error("Required fields are missing");
        return;
    }
    
    // Check if we've just refreshed to prevent loops
    const justRefreshed = sessionStorage.getItem('justRefreshed');
    if (justRefreshed === 'true') {
        console.log('Page was just refreshed, skipping AJAX call');
        sessionStorage.removeItem('justRefreshed');
        return;
    }
    
    // Create AJAX request
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "CheckReport.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            // Log the raw response
            console.log("Raw response:", xhr.responseText);
            
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        console.log("Order closed successfully: " + response.message);
                        
                        // Set flag to prevent refresh loop
                        sessionStorage.setItem('justRefreshed', 'true');
                        
                        // Refresh the page
                        location.reload();
                    } else {
                        console.error("Error from server: " + response.error);
                    }
                } catch (e) {
                    console.error("Invalid JSON response:", e);
                }
            } else {
                console.error("HTTP error:", xhr.status);
            }
        }
    };
    
    // Create the payload
    const payload = 
        'penjualan_per_hari=' + encodeURIComponent(penjualan_per_hari) +
        '&menu_terjual=' + encodeURIComponent(menu_terjual) +
        '&promo_terpakai=' + encodeURIComponent(promo_terpakai) +
        '&UserID=' + encodeURIComponent(UserID) +
        '&tanggal=' + encodeURIComponent(tanggal);
    
    console.log("Sending payload:", payload);
    
    // Send the request
    xhr.send(payload);
}

// Run the function when the page loads
closeOrderAutomatically();
    </script>
</body>
</html>
