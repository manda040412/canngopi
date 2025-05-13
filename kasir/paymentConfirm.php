<?php
// Include connection.php to reuse the database connection
require_once '../connection.php';

session_start();

 // Reset pesananmakanan to 0 when the page loads
 $sqlReset = "UPDATE status SET Status = 1 WHERE StatusID = 3";
 $conn->query($sqlReset);

 // Reset pesananmakanan to 0 when the page loads
$statusreset = "UPDATE status SET Status = 0 WHERE StatusID = 2";
$conn->query($statusreset);

// Pastikan user sudah login dan memiliki userID yang valid
if (!isset($_SESSION['userID']) || empty($_SESSION['userID'])) {
    // Redirect to index.php if not logged in
    header("Location: index.php");
    exit(); // Ensure no further code is executed
}

$userID = $_SESSION['userID'];

// Periksa apakah ada order dengan status "processing" untuk user ini
$sql = "SELECT OrderID FROM order_list WHERE UserID = ? AND status = 'processing'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Jika ada pesanan processing, ambil OrderID-nya
    $stmt->bind_result($orderID);
    $stmt->fetch();
    $_SESSION['OrderID'] = $orderID;
}

// Ambil data terbaru dari tabel Order_list berdasarkan OrderID
$sqlOrder = "SELECT Total_harga FROM order_list WHERE OrderID = ?";
$stmtOrder = $conn->prepare($sqlOrder);
$stmtOrder->bind_param("i", $orderID);
$stmtOrder->execute();
$resultOrder = $stmtOrder->get_result();
$orderRow = $resultOrder->fetch_assoc();

$totalAmount = $orderRow['Total_harga'] ?? 0;

$sql = "SELECT TipePenjualan, Nomormeja FROM order_list WHERE OrderID = $orderID";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $orderData = $result->fetch_assoc();
    $tipePenjualan = $orderData['TipePenjualan'];
    $nomorMeja = $orderData['Nomormeja'];
} else {
    // Jika tidak ada data
    $tipePenjualan = "Unknown";  // Atau bisa dikosongkan
    $nomorMeja = "N/A"; // Atau bisa dikosongkan
}

$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
</head>

<style>
    /* styles.css */

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #e60000;
        font-family: Arial, sans-serif;
    }

    .order-container {
        display: flex;
        flex-direction: column;
        background-color: #fff;
        width: calc(100% - 40px);
        height: calc(100% - 40px);
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        margin: 0 10px;
    }



    .order-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        /* Takes full width */
        color: #8b0000;
        font-weight: bold;
        text-align: left;
    }

    .back-button {
        background: none;
        border: none;
        font-size: 20px;
        color: #8b0000;
        cursor: pointer;
    }

    .order-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .divider {
        width: 1px;
        height: 20px;
        /* Adjust height as needed */
        background-color: #8b0000;
        /* Match the color with the design */
    }

    .dividerHorizontal {
        width: 100%;
        height: 1px;
        background-color: #8b0000;
        /* Match the color with the design */
        margin-top: 20px;
    }


    .order-info span {
        font-size: 26px;
        color: #8b0000;
    }

    .order-info strong {
        font-weight: bold;
    }

    .order-content h1 {
        font-size: 60px;
        color: #8b0000;
        margin: 20px 0;
    }

    .confirmation-text {
        font-size: 18px;
        font-weight: bold;
        color: #8b0000;
    }

    .instruction-text {
        font-size: 14px;
        color: #8b0000;
        margin: 10px 0;
    }

    .order-actions {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 20px;
    }

    .header-text {
        display: flex;
        justify-content: center;
        gap: 15px;
    }

    .show-qr,
    .confirm {
        padding: 10px 20px;
        border: none;
        border-radius: 25px;
        cursor: pointer;
        font-weight: bold;
    }

    .show-qr {
        background-color: #d9a1a1;
        color: #8b0000;
    }

    .confirm {
        background-color: #8b0000;
        color: #fff;
    }

    .header-text span {
        font-size: 26px;
        color: #8b0000;
    }

    .order-content {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        margin-top: auto;
        /* Center vertically */
        margin-bottom: auto;
    }
</style>

<body>
    <div class="order-container">
        <div class="order-header">
            <div class="header-text">
                <button class="back-button" onclick="location.href='<?php echo ($_SESSION['tipe_penjualan'] == 'dinein') ? 'dinein.php' : 'takeaway.php'; ?>'">←</button>
                <span>Konfirmasi Pesanan</span>
            </div>
            <div class="order-info">
                <span><?php echo htmlspecialchars($tipePenjualan); ?></span>
                <div class="divider"></div>
                <span>Table <strong><?php echo htmlspecialchars($nomorMeja); ?></strong></span>
            </div>
        </div>
        <div class="dividerHorizontal"></div>
        <div class="order-content">
            <h1>Rp <?php echo number_format($totalAmount, 0, ',', '.'); ?></h1>
            <p class="confirmation-text">Konfirmasi Pembayaran</p>
            <p class="instruction-text">Pastikan pembayaran dari customer telah diterima dan valid</p>
            <div class="order-actions">
                <button class="confirm">Confirm</button>
            </div>
        </div>

    </div>

    <script>
        document.querySelector('.confirm').addEventListener('click', function() {
            // Kirim permintaan AJAX untuk memperbarui status
            const orderID = <?php echo $orderID; ?>; // Ganti dengan OrderID yang sesuai
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'status.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status == 200) {
                    window.location.href = 'betaAFK.php';
                }
            };
            xhr.send('orderID=' + orderID);
        });
    </script>
</body>

</html>