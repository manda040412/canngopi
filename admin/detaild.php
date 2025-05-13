<?php
session_start();
require_once '../connection.php';

if (!isset($_SESSION['UserID']) || empty($_SESSION['UserID'])) {
    // Redirect to index.php if no session is found
    header("Location: index.php");
    exit();
}

// Get filter parameters from the URL or form submission
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;
$filterType = $_GET['filter_type'] ?? 'monthly';

// Now, define the order by variable based on the selected options
$orderBy = ''; // Default empty

// Get the sort options
$sortQtyOption = $_GET['sort_qty'] ?? 'highest'; // Added this for qty sorting
$sortOption = $_GET['sort_by'] ?? 'idr_highest'; // Default to 'idr_highest'

// Apply date filter if available
$query = "
    SELECT 
    d.nama_discount, 
    SUM(DISTINCT ol.total_promo) AS total_discount, 
    COUNT(DISTINCT ol.OrderID) AS total_qty_used
FROM discount d
JOIN order_list ol ON d.DiscountID = ol.DiscountID
JOIN order_penjualan op ON ol.OrderID = op.OrderID
JOIN penjualan p ON op.PenjualanID = p.PenjualanID
WHERE 1=1

";

// Apply the date filter if start and end date are available
if ($startDate && $endDate) {
    $query .= " AND p.date BETWEEN '$startDate' AND '$endDate'";
}

// Build the query based on the selected sort option
switch ($sortOption) {
    case 'idr_highest':
        $orderBy = "ORDER BY total_discount DESC";
        break;
    case 'idr_lowest':
        $orderBy = "ORDER BY total_discount ASC";
        break;
    case 'qty_highest':
        $orderBy = "ORDER BY total_qty_used DESC";
        break;
    case 'qty_lowest':
        $orderBy = "ORDER BY total_qty_used ASC";
        break;
    default:
        $orderBy = "ORDER BY total_discount DESC"; // Default sorting
}

// Apply sorting
$query .= " GROUP BY d.nama_discount $orderBy";

// Execute the query to fetch the data
$result = mysqli_query($conn, $query);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Penggunaan Discount</title>

    <!-- box icons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
</head>

<style>
        body {
            margin: 0;
            font-family: Poppins, sans-serif;
            background: linear-gradient(to right, #D90101, #990000);
            display: flex;
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
            padding:30px;          
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

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
            color: black;
            display: flex;
            align-items: center;
        }

        h3 {
            font-size: 12px;
            margin-bottom: 2px;
            color: #757575;
        }
        .header h2 i {
            margin-right: 10px;
        }

        h4 {
            margin: 1px 0 12px;
            color: black;
            font-weight: normal;
        }

        .header button {
            background-color: #AA1919;
            border: 2px solid #AA1919;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }

        .header button:last-child {
            background-color: white;
            color: #AA1919;
        }

        .filter-bar {
            display: column;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            gap: 20px;
        }
        
        .filter-bar div {
            flex-direction: column;
        }

        .filter-bar select,
        .filter-bar input[type="date"],
        .filter-bar input[type="input"] {
            display: flex;
            width: auto;
            min-width: 150px;
            max-width: 100%;
            white-space: nowrap;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
        }



        .reports-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reports-table th,
        .reports-table td {
            padding: 15px;
            text-align: center;
            font-weight: normal;
        }

        .reports-table th {
            background-color: #f8f8f8;
            color: #333;
            border-bottom: 1px solid #ddd;
            font-weight: normal;
        }

        .reports-table tr {
            border-bottom: 1px solid #ddd;
        }

        .reports-table tr:hover {
            background-color: #f1f1f1;
        }

        .reports-table img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
        }

        .sort {
            display: flex;
            align-items: center;
            align-items: flex-start;
        }

        .sort select {
            margin-left: 0px;
        }

        .category {
            display: flex;
            align-items: center;
            align-items: flex-start;
            margin-left: 20px;
        }

        .category select {
            margin-left: 0px;
        }   
        

    </style>
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

    <div class="content">
        
    <div class="header">
            <h2><i class='bx bx-left-arrow-alt' onclick="goBack()"></i>Detail Penggunaan Discount</h2>
    </div>
    <h4>Lihat berapa banyak discount yang terpakai</h4>
    <div class="filter-bar">
        <form action="detaild.php" method="GET" id="filter-form">
            <div>
                <h3>Sort By</h3>
                <select id="sort_by" name="sort_by" onchange="this.form.submit()">
                    <option value="idr_highest" <?php echo ($sortOption == 'idr_highest') ? 'selected' : ''; ?>>Sort by IDR: Highest - Lowest</option>
                    <option value="idr_lowest" <?php echo ($sortOption == 'idr_lowest') ? 'selected' : ''; ?>>Sort by IDR: Lowest - Highest</option>
                    <option value="qty_highest" <?php echo ($sortOption == 'qty_highest') ? 'selected' : ''; ?>>Sort by Quantity: Highest - Lowest</option>
                    <option value="qty_lowest" <?php echo ($sortOption == 'qty_lowest') ? 'selected' : ''; ?>>Sort by Quantity: Lowest - Highest</option>
                </select>
            </div>
        
            <!-- Hidden inputs for date and filter type -->
            <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" />
            <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" />
            <input type="hidden" name="filter_type" value="<?php echo htmlspecialchars($filterType); ?>" />
        </form>
    </div>



    <table class="reports-table">
        <thead>
            <tr>
                <th>Nama Discount</th>
                <th>Total Potongan IDR</th>
                <th>Total Qty Discount Terpakai</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Check if there are results
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>" . $row['nama_discount'] . "</td>";
                    echo "<td>" . number_format($row['total_discount'], 0, ',', '.') . "</td>";
                    echo "<td>" . number_format($row['total_qty_used'], 0, ',', '.') . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No data found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
    function goBack() {
            window.location.href = "monitoring.php"; // Back to your reports page
        }
        
        function adjustWidth() {
        const select = document.querySelector(".filter-bar select");
        if (!select) return; // Kalau select tidak ditemukan, keluar dari fungsi

        const tempOption = document.createElement("span");
        tempOption.style.visibility = "hidden";
        tempOption.style.position = "absolute";
        tempOption.style.whiteSpace = "nowrap";
        tempOption.textContent = select.options[select.selectedIndex].text;

        document.body.appendChild(tempOption);
        select.style.width = tempOption.offsetWidth + 20 + "px"; 
        document.body.removeChild(tempOption);
    }

    document.addEventListener("DOMContentLoaded", () => {
        const select = document.querySelector(".filter-bar select");
        if (select) {
            select.addEventListener("change", adjustWidth);
            adjustWidth(); // Set width pertama kali
        }
    });
</script>

</body>

</html>