<?php
session_start();
// Include the connection.php file to connect to the database
include('../connection.php');

if (!isset($_SESSION['UserID']) || empty($_SESSION['UserID'])) {
    // Redirect to index.php if no session is found
    header("Location: index.php");
    exit();
}

// Query to get categories from Kategori table
$kategoriSql = "SELECT KategoriID, kategori FROM kategori";
$kategoriResult = $conn->query($kategoriSql);

// Capture filter inputs from POST request
$startDate = isset($_POST['start-date']) ? $_POST['start-date'] : '';
$endDate = isset($_POST['end-date']) ? $_POST['end-date'] : '';
$sort = isset($_POST['sort']) ? $_POST['sort'] : 'highest';
$category = isset($_POST['category']) ? $_POST['category'] : '';

// Sorting logic
$orderBy = $sort == 'highest' ? 'DESC' : 'ASC';

$sql = "SELECT m.nama_menu, m.image, k.kategori, 
        SUM(oi.total_quantity) AS total_quantity
        FROM menu m
        JOIN kategori k ON m.KategoriID = k.KategoriID
        JOIN (
            -- Step 1: Pastikan setiap OrderID hanya dihitung satu kali sebelum join
            SELECT oi.MenuID, op.PenjualanID, SUM(oi.Quantity) AS total_quantity
            FROM order_items oi
            JOIN order_list ol ON oi.OrderID = ol.OrderID
            JOIN (
                -- Step 2: Ambil hanya OrderID unik dari order_penjualan agar tidak ada duplikasi
                SELECT DISTINCT OrderID, PenjualanID 
                FROM order_penjualan
            ) op ON ol.OrderID = op.OrderID
            GROUP BY oi.MenuID, op.PenjualanID
        ) oi ON m.MenuID = oi.MenuID
        JOIN penjualan p ON oi.PenjualanID = p.PenjualanID
        WHERE 1=1";

// Apply date filters
if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND p.created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
}

// Apply category filter if selected
if (!empty($category)) {
    $sql .= " AND k.KategoriID = '$category'";
}

// Add sorting condition
$sql .= " GROUP BY m.MenuID ORDER BY total_quantity $orderBy";



// Execute the query
$result = $conn->query($sql);




// Close the connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Item Sales</title>

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
            justify-content: space-between;
        }
        
        .filter-bar div {
            display: flex;
            flex-direction: column;
            position: relative;
            width: 50%;
            max-width: 150px;
        }

        .filter-bar select,
        .filter-bar input[type="date"]{
                display: flex;
                width: auto;
                padding: 10px 10px 10px 10px;
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
            text-align: left;
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
</head>

<body>
<div class="sidebar">
        <img src="image/logo_canngopi.png" alt="Logo">
        <a href="monitoring.php">
            <i class='bx bx-stats'></i> Monitoring
        </a>
        <a href="report.php" class="active">
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
            <h2><i class='bx bx-arrow-back' onclick="goBack()"></i> Report Item Sales</h2>
        </div>

        <h4>Lihat berapa banyak item yang terjual</h4>

        <div class="filter-bar">
            <form action="report_item_sales.php" method="POST" id="filter-form">
                <div>
                    <h3>Start Date</h3>
                    <input type="date" id="start-date" name="start-date" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                <div>
                    <h3>End Date</h3>
                    <input type="date" id="end-date" name="end-date" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
                <div>
                    <h3>Sort</h3>
                    <select id="sort" name="sort">
                        <option value="highest" <?php echo $sort == 'highest' ? 'selected' : ''; ?>>Highest - lowest</option>
                        <option value="lowest" <?php echo $sort == 'lowest' ? 'selected' : ''; ?>>Lowest - highest</option>
                    </select>
                </div>
                <div>
                    <h3>Category</h3>
                    <select id="category" name="category">
                        <option value="">All</option>
                        <?php
                        if ($kategoriResult->num_rows > 0) {
                            // Loop through each category and create an option element
                            while ($row = $kategoriResult->fetch_assoc()) {
                                $selected = $row['KategoriID'] == $category ? 'selected' : '';
                                echo "<option value='" . $row['KategoriID'] . "' $selected>" . $row['kategori'] . "</option>";
                            }
                        } else {
                            echo "<option value=''>No categories found</option>";
                        }
                        ?>
                    </select>
                </div>
            </form>
        </div>

        <table class="reports-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Quantity Sold</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    // Output each row of data
                    while ($row = $result->fetch_assoc()) {
                        // Make sure 'image' and 'total_quantity' exist in the result set
                        if (!empty($row['image'])) {
                        // Mengambil base URL
                        $baseURL = "https://cobaadmin.canngopi.com";
                        
                        // Menghapus '/admin' jika ada dalam path
                        $imagePath = str_replace('/admin', '', $row['image']);
                        $total_quantity = !empty($row['total_quantity']) ? $row['total_quantity'] : 0; // Handle undefined total_sold
                        echo "<tr>";
                        echo "<td><img src='" . htmlspecialchars($baseURL . $imagePath) . "' alt='" . htmlspecialchars($row['nama_menu']) . "' width='50' height='50'></td>";
                        } else {
                        echo "<td><img src='https://via.placeholder.com/150' alt='Tidak ada gambar'></td>";
                        }
                        echo "<td>" . $row['nama_menu'] . "</td>";
                        echo "<td>" . $row['kategori'] . "</td>";
                        echo "<td>" . $total_quantity . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No results found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        function goBack() {
            window.location.href = "report.php";
        }
        // Automatically submit the form on input change
        document.getElementById('filter-form').addEventListener('change', function() {
            this.submit();
        });
    </script>

</body>

</html>
