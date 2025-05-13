<?php
require_once '../connection.php';

// Ambil data terbaru dari tabel Report
$sql = "SELECT penjualan_per_hari, promo_terpakai FROM report ORDER BY ReportID DESC LIMIT 1";
$result = $conn->query($sql);

// Inisialisasi nilai default jika data tidak ditemukan
$gross_sales = 0;
$discount = 0;
$net_sales = 0;
$total_collected = 0;

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $gross_sales = $row['penjualan_per_hari'];
    $discount = $row['promo_terpakai'];
    $net_sales = $gross_sales - $discount;
    $total_collected = $net_sales; // Sesuaikan jika ada perhitungan tambahan
}

// Ambil tanggal dari form jika ada, jika tidak gunakan hari ini
if (isset($_GET['tanggal']) && !empty($_GET['tanggal'])) {
    $tanggalRange = explode(' - ', $_GET['tanggal']);
    if (count($tanggalRange) == 2) {
        $startDate = date('Y-m-d', strtotime(trim($tanggalRange[0])));
        $endDate = date('Y-m-d', strtotime(trim($tanggalRange[1])));
    }
} else {
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d');
}

// Query database berdasarkan rentang tanggal
$stmt = $conn->prepare("SELECT SUM(penjualan_per_hari), SUM(promo_terpakai) FROM report WHERE DATE(Start_Date) BETWEEN ? AND ?");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$stmt->bind_result($gross_sales, $discount);
$stmt->fetch();
$stmt->close();

// Jika nilai NULL, ubah menjadi 0
$gross_sales = $gross_sales ?? 0;
$discount = $discount ?? 0;
$net_sales = $gross_sales - $discount;
$total_collected = $net_sales;

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Summary</title>
    <!-- box icons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <!-- LitePicker -->
    <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>

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

        .summary-container {
            padding: 30px;
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
            /* flex: 1; */
            padding: 12px;
            font-size: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: Poppins, sans-serif;
            margin-right: 650px;
        }

        .line {
            border: none;
            background-color: #d5d5d5;
            margin-top: 10px;
            width: 100%;
            margin-bottom: 10px;
        }

        .sales-summary {
            width: 100%;
        }

        .row {
            display: flex;
            justify-content: space-between;
        }

        .item {
            padding: 5px 10px;
        }

        .data {
            text-align: right;
            flex-grow: 1;
            padding: 5px 10px;
        }

        .button-container {
            display: flex;
            justify-content: flex-end;
            /* margin-top: 25px;
            margin-bottom: 25px; */
        }

        .download-button {
            padding: 10px 20px;
            background-color: #990000;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            /* margin-top: 60px; */
        }
    </style>
</head>

<body>
    <div class="summary-container">
        <div class="header">
            <i class='bx bx-left-arrow-alt' onclick="goBack()"></i>
            <h3>Sales Summary</h3>
        </div>

        <form action="sales_summary.php" method="get">
            <div class="form-group">
                <label for="tanggal">Tanggal</label>
                <input type="text" id="tanggalRange" name="tanggal"
                    placeholder="hari ini / Input Tanggal" autocomplete="off"
                    value="<?= isset($_GET['tanggal']) ? $_GET['tanggal'] : '' ?>" readonly>

                <!-- Button Download -->
                <div class="button-container">
                    <a href="downloadSales.php?tanggal=<?= urlencode($_GET['tanggal'] ?? '') ?>" class="download-button">Download</a>
                </div>
            </div>
        </form>



        <!-- Sales Summary -->
        <div class="sales-summary">
            <hr class="line" style="height: 30px;">
            <div class="row">
                <!-- Gross Sales -->
                <div class="item">Gross Sales</div>
                <div class="data">Rp. <?= number_format($gross_sales, 0, ',', '.') ?></div>
            </div>

            <hr class="line" style="height: 1px; opacity: 0.5;">

            <div class="row">
                <!-- Discount -->
                <div class="item">Discount</div>
                <div class="data">(Rp. <?= number_format($discount, 0, ',', '.') ?>)</div>
            </div>

            <hr class="line" style="height: 1px; opacity: 0.5;">

            <div class="row">
                <!-- Refunds -->
                <div class="item">Refunds</div>
                <div class="data">Rp. 0</div>
            </div>

            <hr class="line" style="height: 3px; opacity: 0.5;">

            <div class="row" style="font-weight: bold;">
                <!-- Net Sales -->
                <div class="item">Net Sales</div>
                <div class="data">Rp. <?= number_format($net_sales, 0, ',', '.') ?></div>
            </div>

            <hr class="line" style="height: 1px; opacity: 0.5;">

            <div class="row">
                <!-- Gratuity -->
                <div class="item">Gratuity</div>
                <div class="data">Rp. 0</div>
            </div>

            <hr class="line" style="height: 1px; opacity: 0.5;">

            <div class="row">
                <!-- Tax -->
                <div class="item">Tax</div>
                <div class="data">Rp. 0</div>
            </div>

            <hr class="line" style="height: 1px; opacity: 0.5;">

            <div class="row">
                <!-- Rounding -->
                <div class="item">Rounding</div>
                <div class="data">Rp. 0</div>
            </div>

            <hr class="line" style="height: 3px; opacity: 0.5;">

            <div class="row" style="font-weight: bold;">
                <!-- Total Collected -->
                <div class="item">Total Collected</div>
                <div class="data">Rp. <?= number_format($total_collected, 0, ',', '.') ?></div>
            </div>

            <hr class="line" style="height: 3px; opacity: 0.5;">
        </div>
    </div>

    <script>
        function goBack() {
            window.location.href = "report.php"; // Back to your reports page
        }

        let picker = new Litepicker({
            element: document.getElementById('tanggalRange'),
            format: 'YYYY/MM/DD',
            singleMode: false,
            setup: (picker) => {
                picker.on('selected', () => {
                    document.querySelector('form').submit();
                });
            }
        });
    </script>

</body>

</html>