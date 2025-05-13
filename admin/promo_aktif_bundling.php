<?php
// Start session
session_start();

// Include the database connection
include('../connection.php');

if (!isset($_SESSION['UserID']) || empty($_SESSION['UserID'])) {
    // Redirect to index.php if no session is found
    header("Location: index.php");
    exit();
}

// Fetch active bundling promos from the Bundling table
$query = "
    SELECT b.BundlingID, m.nama_menu, m.image, m.deskripsi, b.harga, b.harga_baru, b.masa_berlaku 
    FROM bundling b
    JOIN menu_bundling mb ON b.BundlingID = mb.BundlingID
    JOIN menu m ON mb.MenuID = m.MenuID
    WHERE b.deleted_at IS NULL AND b.masa_berlaku > NOW()
";
$result = $conn->query($query);

// Check if there are active bundlings
if ($result->num_rows > 0) {
    $active_bundlings = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $active_bundlings = []; // Initialize as an empty array if no bundling found
}

// Count the number of active bundlings
$bundling_count = count($active_bundlings);

// Close the database connection
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promo Bundling Aktif</title>

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
            padding-top: 15px;
            padding-left: 30px;
            padding-right: 30px;
            padding-bottom: 10px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            position: fixed; 
            box-sizing: border-box;
            top: 30px; 
            left: 320px; 
            right: 30px; 
            bottom: 30px; 
            overflow-y: auto; 
            z-index: 900;
        }

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            position: relative;
        }

        .header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #333;
            flex-grow: 1;
        }

        .header i {
            font-size: 24px;
            color: #333;
            cursor: pointer;
        }

        .header::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #ccc;
        }
     
        .promo-grid {
            display: grid;
            height: 1000px;
            margin-top: 20px;
            margin-bottom: 120px;
            margin-left: 10px;
            margin-right: 10px;
            margin-bottom: 50px;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
        }

        .promo-item {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s;
            height:350px ;
        }

        .promo-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .promo-item h3 {
            margin: 10px 0 5px;
            font-size: 18px;
            color: #333;
        }

        .promo-item p {
            margin: 0 0 5px;
            font-size: 14px;
            color: #777;
        }

        .promo-item .price {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .price-old {
            text-decoration: line-through;
            color: #999;
        }

        .footer {
            display: flex;
            margin-left: 48%;
            position: sticky;
            
            font-size: 14px;
            color: #777;
            
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
        <div class="header">
            <i class='bx bx-chevron-left' onclick="location.href='promo.php'" style="font-size: 54px;"></i>
            <h2>Promo Bundling Aktif</h2>
        </div>

        <div class="promo-grid">
            <?php foreach ($active_bundlings as $bundling): ?>
                <div class="promo-item" onclick="location.href='promo_detail_bundling.php?BundlingID=<?= htmlspecialchars($bundling['BundlingID']); ?>'">
                    <!-- Display the promo details dynamically -->
                    <img src="data:image/jpeg;base64,<?= base64_encode($bundling['image']); ?>" alt="<?= htmlspecialchars($bundling['nama_menu']); ?>" style="width: 100%; height: 200px;">
                    <h3><?= htmlspecialchars($bundling['nama_menu']); ?></h3>
                    <p><?= htmlspecialchars($bundling['deskripsi']); ?></p>
                    <p class="price">
                        <span class="price-old">Rp <?= number_format($bundling['harga'], 0, ',', '.'); ?></span><br>
                        Rp <?= number_format($bundling['harga_baru'], 0, ',', '.'); ?>
                    </p>
                    <p>Aktif hingga <?= date('d F Y', strtotime($bundling['masa_berlaku'])); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="footer">
            ~ <?= $bundling_count; ?> items ~
        </div>
    </div>
</body>

</html>

