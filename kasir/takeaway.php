<?php
// Include the connection file
include('../connection.php'); // This will use the connection from connection.php

session_start();

// Assuming this is set when the user selects the sales type
$_SESSION['tipe_penjualan'] = 'takeaway'; // or 'takeaway'

// Reset pesananmakanan to 0 when the page loads
$sqlReset = "UPDATE status SET Status = 0 WHERE StatusID = 1";
$conn->query($sqlReset);

// Reset pesananmakanan to 0 when the page loads
$statusreset = "UPDATE status SET Status = 1 WHERE StatusID = 2";
$conn->query($statusreset);

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

// Query untuk mengambil data pesanan
$sql = "
    SELECT 
        ol.OrderID, ol.Total_harga, ol.Total_promo, ol.status,
        oi.MenuID, oi.Quantity, oi.sub_total, oi.Notes, m.nama_menu
    FROM order_list AS ol
    JOIN order_items AS oi ON ol.OrderID = oi.OrderID
    JOIN menu AS m ON oi.MenuID = m.MenuID
    WHERE ol.OrderID = ? AND ol.status = 'processing'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orderID);
$stmt->execute();
$result = $stmt->get_result();

// Inisialisasi variabel
$subtotal = 0;
$totalDiscount = 0;
$totalAmount = 0;
$persentase = 0;
$jenisDiskon = null;

// Ambil data terbaru dari tabel Order_list berdasarkan OrderID
$sqlOrder = "SELECT Total_promo, Total_harga, DiscountID, VoucherID FROM order_list WHERE OrderID = ?";
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

    // Prioritaskan cek VoucherID atau DiscountID
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
    }
}

// Menutup statement untuk menghindari kebocoran sumber daya
$stmtOrder->close();
if (isset($stmtVoucher)) {
    $stmtVoucher->close();
}
if (isset($stmtDiscount)) {
    $stmtDiscount->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($stmt->execute()) {
        // Redirect ke halaman pembayaran jika berhasil
        header("Location: paymentConfirm.php");
    } else {
        echo "Gagal menyimpan nomor meja: " . $conn->error;
    }

    $stmt->close();
}


?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Takeaway Details</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<style type="text/css">
    body {
        margin: 0;
        font-family: Poppins, sans-serif;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow-y: hidden;
        overflow-x: hidden;
        background: linear-gradient(to bottom,
                #FF0000 0%,
                #C30000 40%,
                #8A1518 100%);
    }

    .sticky-section-order {
        display: flex;
        flex-direction: row;
        align-items: center;
        position: sticky;
        top: 0;
        background-color: #fff;
        z-index: 10;
        padding-bottom: 0px;
    }

    .sticky-section-order h1 {
        padding-bottom: 0px;
    }

    .containerOrder {
        background-color: #fff;
        width: calc(100% - 60px);
        margin-left: 30px;
        margin-top: 30px;
        margin-bottom: 30px;
        margin-right: 30px;
        padding: 20px;
        border-radius: 20px;
        box-sizing: border-box;
        height: calc(100vh - 60px);
        display: flex;
        flex-direction: column;
    }

    .order-summary-box-order {
        display: flex;
        flex-direction: column;
        height: 100%;
        background-color: white;
        border-radius: 15px;
        padding: 30px;
        width: 50%;
        box-sizing: border-box;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-top: 30px;
        margin-left: 5px;
        position: relative;

    }

    .order-summary-box-order::-webkit-scrollbar-thumb {
        background-color: #b30000;
        /* Thumb color */
        border-radius: 8px;
        /* Rounded edges for thumb */
    }

    .scrollable-content {
        overflow-y: auto;
        flex-grow: 1;
        margin-bottom: 20px;
        /* Space for fixed count-price section */
    }

    .bagiDua {
        height: 100%;
        padding-bottom: 40px;
        margin-bottom: 10px;
        display: flex;
        gap: 10px;
        overflow-y: hidden;
        box-sizing: border-box;

    }

    .count-price {
        position: sticky;
        bottom: 0;
        background-color: #fff;
        padding-top: 10px;
    }

    .divider {
        height: 2px;
        background-color: #ddd;
        width: 100%;
    }

    .order-number {
        color: #A30000;
        font-weight: bold;
        font-size: 20px;
    }

    .order-id {
        font-size: 20px;
        color: #333;
        font-weight: bold;
        margin: 10px 10px 20px 0;
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
    }

    .order-items {
        font-family: Poppins;
        position: relative;
        font-weight: 800;
        font-size: 28px;
        padding: 0;
        margin-top: 20px;
        margin-bottom: -30px;

    }

    .order-items li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        font-size: 18px;
        color: #920000;
    }

    .menu-list-order {
        display: flex;
        justify-content: space-between;
        width: 100%;
        align-items: center;
        height: auto;
        border-top: 1px solid #eaeaea;
        padding-bottom: 5px;
        padding-top: 10px;
        margin-top: 5px;
    }

    .menu-list-order .item-price {
        color: #870000;

    }

    .menu-list-order>div {
        flex: 1;
        min-width: 0;
    }

    .menu-list-order h4 {
        margin: 0;
        font-weight: 500;
        color: #757575;
        display: inline-block;
    }

    .menu-list-order p {
        margin: 0;
        margin-top: 4px;
        font-size: 12px;
        font-weight: 400;
        color: #979797;
    }

    .order-items li:first-child .menu-list-order {
        border-top: none;
        /* Remove line for the first item */
        padding-top: 0;
        /* Optional: adjust padding for consistency */
    }

    .qty {
        position: relative;
        flex-shrink: 0;
        flex-grow: 1;
        margin-left: 10px;
        text-align: center;
        white-space: nowrap;
    }

    .divider-order {
        margin-top: 20px;
        margin-bottom: 20px;
        height: 2px;
        background-color: #ddd;
        width: 100%;
    }

    .order-items-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        background-color: white;
        position: sticky;
        top: 0;
        /* This will keep it fixed at the top of the scroll area */
        z-index: 5;
        border-bottom: 1px solid #ddd;
    }

    .order-items-header .flexMenu,
    .order-items-header .item-price {
        font-weight: bold;
        color: #870000;

    }

    .item-price {
        padding-right: 30px;
    }

    .container-kanan {
        display: flex;
        width: 50%;
        flex-direction: column;
        align-items: center;
        margin-top: 20px;
        overflow-y: auto;

    }

    .pembayaran {
        text-align: center;
        font-weight: 600;
        color: #6C0000;
    }

    .confirmation {
        text-align: center;
        font-weight: 300;
        color: #6C0000;
    }

    .price {
        font-size: 95px;
        font-weight: 750;
        text-align: center;
        color: #8C1D1D;
    }

    .confirm-button {
        background-color: #8C1D1D;
        font-family: Poppins;
        font-size: 30px;
        color: white;
        border: none;
        padding: 18px 72px;
        cursor: pointer;
        border-radius: 100px;
        margin-left: 30px;
        margin-top: 0px;
    }

    .order-totals-order {
        position: relative;
    }

    .button{
        display: flex;
        justify-content: center;
        align-items: center;
    }
</style>

<body>

    <div class="containerOrder">
        <div class="sticky-section-order">
            <i class='bx bx-chevron-left' onclick="location.href='betaKasir.php'" style="font-size: 54px;"></i>
            <h1>Konfirmasi Pesanan</h1>
        </div>
        <div class="divider"></div>
        <div class="bagiDua">
            <div class="order-summary-box-order">
                <h2 class="order-id">Order ID <span class="order-number">#<?php echo $orderID; ?></span></h2>

                <div class="divider-menu"></div>

                <!-- Scrollable content wrapper -->
                <div class="scrollable-content">
                    <div class="order-items-header">
                        <div class="flexMenu">Menu</div>
                        <div class="item-price">Harga</div>
                    </div>
                    <ul class="order-items">
                        <?php
                        if ($result->num_rows > 0) {
                            // Loop melalui hasil dan tampilkan item pesanan
                            while ($row = $result->fetch_assoc()) {
                                $menu = $row['nama_menu']; // Ganti dengan nama yang sesuai jika ada join dengan tabel menu
                                $quantity = $row['Quantity'];
                                $sub_total = $row['sub_total'];
                                $notes = $row['Notes'];

                                $subtotal += $sub_total;
                        ?>
                                <li>
                                    <div class="menu-list-order">
                                        <div>
                                            <h4><?php echo htmlspecialchars($menu); ?></h4>
                                            <?php if (!empty($notes)) { ?>
                                                <p class="item-note">- <?php echo htmlspecialchars($notes); ?></p>
                                            <?php } ?>
                                        </div>
                                        <h4 class="qty">x<?php echo $quantity; ?></h4>
                                        <h4 class="item-price">Rp <?php echo number_format($sub_total, 0, ',', '.'); ?></h4>
                                    </div>
                                </li>
                        <?php
                            }
                        } else {
                            echo "<li><p>Tidak ada item pesanan.</p></li>";
                        }
                        ?>
                    </ul>
                </div>

                <!-- Fixed order totals at the bottom -->
                <div class="count-price">
                    <div class="divider-order"></div>
                    <div class="order-totals-order">
                        <table>
                            <tr>
                                <td>Sub-total</td>
                                <td>:</td>
                                <td><span>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span></td>
                            </tr>
                            <tr>
                                <td>Discount</td>
                                <td>:</td>
                                <td><span>- Rp <?php echo number_format($totalDiscount, 0, ',', '.'); ?></span></td>
                            </tr>
                            <tr>
                                <td>Total</td>
                                <td>:</td>
                                <td><span>Rp <?php echo number_format($totalAmount, 0, ',', '.'); ?></span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="container-kanan">
                <form action="takeaway.php" method="POST">
                <h1 class="pembayaran">Pembayaran</h1>
                <h2 class="confirmation">Pastikan pembayaran dari customer telah diterima dan valid</h2>
                <h1 class="price">Rp <?php echo number_format($totalAmount, 0, ',', '.'); ?></h1>
                <div class="button">
                <button type="submit" class="confirm-button">Confirm</button>
                </div>
                </form>
            </div>
        </div>
    </div>
    </div>

    <script>
    </script>
</body>

</html>