<?php
session_start();
// Include the connection.php file to connect to the database
include('../connection.php');

if (!isset($_SESSION['UserID']) || empty($_SESSION['UserID'])) {
    // Redirect to index.php if no session is found
    header("Location: index.php");
    exit();
}

// Initialize variables for filtering
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;
$reports = [];

// Calculate the date 5 days ago
$five_days_ago = date('Y-m-d', strtotime('-5 days'));

// If both dates are provided, filter by date range
if ($start_date && $end_date) {
    $end_date = date('Y-m-d', strtotime($end_date . ' +1 day'));
    
    // Prepare and execute SQL query for filtered results with ReportID
    $sql = "SELECT ReportID, judul, deskripsi, Start_Date, End_Date 
            FROM report 
            WHERE Start_Date >= ? AND End_Date < ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date); // Bind the date parameters
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Get reports from the last 5 days when no date filter is provided
    $sql = "SELECT ReportID, judul, deskripsi, Start_Date, End_Date 
            FROM report 
            WHERE Start_Date >= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $five_days_ago); // Bind the date parameter for 5 days ago
    $stmt->execute();
    $result = $stmt->get_result();
}

// Fetch all reports
if ($result->num_rows > 0) {
    $reports = $result->fetch_all(MYSQLI_ASSOC);
}

// Close the database connection
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Page</title>

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
            padding-top: 20px;
            padding-left: 45px;
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

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h2 {
            margin: 0;
            color: black;
            font-size: 26px;
        }

        .header button {
            background-color: #AA1919;
            border: 3px solid #AA1919;
            color: white;
            padding: 15px 20px;
            border-radius: 15px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
            margin-top: 20px;
        }

        .header button:last-child {
            background-color: white;
            color: #AA1919;
            font-weight: bold;
        }

        .date-picker-container {
            display: flex;
            column-gap: 50px;
            margin-top: 10px;
        }

        .start-date {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            font-size: 16px;
            background-color: #f9f9f9;
            color: #555;
            outline: none;
            font-family: 'Poppins', sans-serif;
        }

        .start-date:hover,
        .start-date:focus {
            border-color: #AA1919;
        }

        .end-date {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            font-size: 16px;
            background-color: #f9f9f9;
            color: #555;
            outline: none;
            font-family: 'Poppins', sans-serif;
        }

        .end-date:hover,
        .end-date:focus {
            border-color: #AA1919;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-size: 18px;
            color: #000;
            font-weight: 500;
        }

        .reports {
            list-style: none;
            padding: 0;
            flex-grow: 1;
        }

        .reports li {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .reports li h3 {
            margin: 0;
            font-size: 18px;
        }

        .reports li p {
            margin: 5px 0 0;
            color: #777;
        }

        .more-options {
            cursor: pointer;
            padding: 5px;
            font-size: 24px;
            color: #999;
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
            <h2>Report</h2>
            <div>
                <button id="sales-summary-button">Sales Summary</button>
                <button id="report-item-sales-button">Report Item Sales</button>
            </div>
        </div>

        <form method="post" id="filter-form">
            <div class="date-picker-container">
                <div>
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="start-date" value="<?php echo $start_date; ?>" onchange="document.getElementById('filter-form').submit();">
                </div>
                <div>
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="end-date" value="<?php echo $end_date; ?>" onchange="document.getElementById('filter-form').submit();">
                </div>
            </div>
        </form>

        <ul class="reports">
        <?php if (!empty($reports)) : ?>
            <?php foreach ($reports as $report) : ?>
                <li data-report-id="<?php echo htmlspecialchars($report['ReportID']); ?>">
                    <div>
                        <h3><?php echo htmlspecialchars($report['judul']); ?></h3>
                        <p><?php echo htmlspecialchars($report['deskripsi']); ?></p>
                        <small>
                            Start Date: 
                            <?php 
                            echo date('l, F j, Y', strtotime($report['Start_Date'])); // Show day of the week, month, day, and year
                            ?>
                        </small><br>
                        <small>
                            End Date: 
                            <?php 
                            echo date('l, F j, Y', strtotime($report['End_Date'])); // Show day of the week, month, day, and year
                            ?>
                        </small>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else : ?>
            <li>No reports found</li>
        <?php endif; ?>
    </ul>


    </div>  
    <script>

        document.getElementById('report-item-sales-button').addEventListener('click', function() {
            window.location.href = 'report_item_sales.php';
        });
        
        document.getElementById('sales-summary-button').addEventListener('click', function() {
            window.location.href = 'sales_summary.php';
        });

        document.querySelectorAll('.reports li').forEach(item => {
        item.addEventListener('click', () => {
            // Get the ReportID from the clicked list item's data attribute
            const reportId = item.getAttribute('data-report-id');
            // Redirect to the report details page with the ReportID as a URL parameter
            window.location.href = `lihat_report.php?ReportID=${reportId}`;
        });
    });

    </script>
</body>

</html>
