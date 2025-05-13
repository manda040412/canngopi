<?php
session_start();
// Include connection.php to reuse the database connection
require_once '../connection.php';

if (!isset($_SESSION['userID']) || empty($_SESSION['userID'])) {
    // Redirect to index.php if not logged in
    header("Location: index.php");
    exit(); // Ensure no further code is executed
}


// Ambil OrderID yang diklik, misalnya lewat URL atau input form
$orderID = isset($_GET['OrderID']) ? $_GET['OrderID'] : 0; // pastikan ada OrderID yang dikirimkan

// Query untuk mendapatkan informasi order_list berdasarkan OrderID
$sql_order = "SELECT o.*, u.nama AS nama FROM order_list o
              JOIN user u ON o.UserID = u.UserID
              WHERE o.OrderID = $orderID";
$order_result = $conn->query($sql_order);

// Jika pesanan ditemukan
if ($order_result->num_rows > 0) {
    $order = $order_result->fetch_assoc();
    $order_date = date("d M Y", strtotime($order['created_at']));
    $order_time = date("H:i:s", strtotime($order['created_at']));
    $receipt_no = "CAN" . str_pad($order['OrderID'], 7, '0', STR_PAD_LEFT); // Format No. Resi

    // Query untuk mendapatkan item-menu yang dipesan
    $sql_items = "SELECT oi.*, m.nama_menu, oi.sub_total, oi.notes AS notes FROM order_items oi
                  JOIN menu m ON oi.MenuID = m.MenuID
                  WHERE oi.OrderID = $orderID";
    $items_result = $conn->query($sql_items);
} else {
    // Handle the case when no order is found (Optional)
    echo "Order not found.";
}

// Ambil data terbaru dari tabel order_list berdasarkan OrderID
$sqlOrder = "SELECT Total_promo, Total_harga, DiscountID FROM order_list WHERE OrderID = ?";
$stmtOrder = $conn->prepare($sqlOrder);
$stmtOrder->bind_param("i", $orderID);
$stmtOrder->execute();
$resultOrder = $stmtOrder->get_result();
$orderRow = $resultOrder->fetch_assoc();

$totalDiscount = $orderRow['Total_promo'] ?? 0;
$totalAmount = $orderRow['Total_harga'] ?? 0;
$DiscountID = $orderRow['DiscountID'] ?? null;

$persentase = 0; // Default jika tidak ada diskon

// Cek jika DiscountID ada, ambil persentase diskon dari tabel Discounts
if ($DiscountID !== null) {
    $sqlDiscount = "SELECT persentase FROM discount WHERE DiscountID = ?";
    $stmtDiscount = $conn->prepare($sqlDiscount);
    $stmtDiscount->bind_param("i", $DiscountID);
    $stmtDiscount->execute();
    $resultDiscount = $stmtDiscount->get_result();
    $discountRow = $resultDiscount->fetch_assoc();

    $persentase = $discountRow['persentase'] ?? 0;
}

// Hitung subtotal jika diperlukan
$subtotal = $subtotal ?? ($totalAmount + $totalDiscount);


$conn->close();
?>



<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title></title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<body>
    <div class="MenuSidebar">
        <a href="betaAFK.php" class="MenuSidebar-item">
            <img src="images/shopping-bag-regular-240.png" alt="Icon 1" class="icon">
            <span class="label">Home Screen</span>
        </a>
        <a href="betaRiwayat.php" class="MenuSidebar-item">
            <img src="images/history-regular-240.png" alt="Icon 2" class="icon">
            <span class="label">Riwayat pesanan</span>
        </a>
        <div class="MenuSidebar-footer">
            <img src="logo/logo.png" alt="Sign Out" class="icon">
            <span class="label">Sign Out</span>
        </div>
    </div>
    <div class="containerNotes">
        <div class="sticky-section-notes">
            <i class='bx bx-chevron-left' onclick="location.href='betaRiwayat.php'" style="font-size: 54px;"></i>
            <h1>Notes</h1>
        </div>
        <div class="divider"></div>
        <form class="bagiDua" method="POST" action="saveNotes.php">
            <div class="order-summary-container-notes">
                <div class="order-summary-box-notes">
                    <?php if (isset($order)): ?>
                        <h2 class="order-id">Order ID <span class="order-number">#<?php echo $order['OrderID']; ?></span></h2>
                        <input type="hidden" name="OrderID" value="<?php echo $order['OrderID']; ?>">
                        <div class="order-info-notes">
                            <table>
                                <tr>
                                    <td>Date</td>
                                    <td class="colon-order-info-notes">:</td>
                                    <td class="isi-order-info"><?php echo $order_date; ?></td>
                                </tr>
                                <tr>
                                    <td>Time</td>
                                    <td class="colon-order-info-notes">:</td>
                                    <td class="isi-order-info"><?php echo $order_time; ?></td>
                                </tr>
                                <tr>
                                    <td>Receipt No</td>
                                    <td class="colon-order-info-notes">:</td>
                                    <td class="isi-order-info"><?php echo $receipt_no; ?></td>
                                </tr>
                                <tr>
                                    <td>Cashier</td>
                                    <td class="colon-order-info-notes">:</td>
                                    <td class="isi-order-info"><?php echo $order['nama']; ?></td>
                                </tr>
                                <tr>
                                    <td>Table No</td>
                                    <td class="colon-order-info-notes">:</td>
                                    <td class="isi-order-info"><?php echo $order['nomormeja']; ?></td>
                                </tr>
                            </table>
                        </div>

                        <div class="divider-menu"></div>
                        <ul class="order-items">
                            <li>
                                <div>
                                    <p class="flexMenu">Menu</p>
                                </div>
                                <span class="item-price">Harga</span>
                            </li>

                            <!-- Menampilkan setiap item yang dipesan -->
                            <?php while ($item = $items_result->fetch_assoc()): ?>
                                <li>
                                    <div class="menu-list-notes">
                                        <div>
                                            <h4><?php echo $item['nama_menu']; ?></h4>
                                            <p class="item-note"><?php echo $item['notes'] ?: "- Tidak ada catatan"; ?></p>
                                        </div>
                                        <h4 class="qty">x<?php echo $item['Quantity']; ?></h4>
                                        <h4 class="item-price">Rp <?php echo number_format($item['sub_total'], 0, ',', '.'); ?></h4>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        </ul>

                        <div class="count-price">
                            <div class="divider-notes"></div>
                            <div class="order-totals-notes">
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

                        <div class="payment-info">
                            <div class="divider-notes"></div>
                            <div class="order-totals">
                                <table>
                                    <tr>
                                        <td>Tipe Penjualan</td>
                                        <td class="colon-payment-info">:</td>
                                        <td><span class="payment-method"><?php echo $order['TipePenjualan'] ?></span></td>
                                    </tr>
                                    <tr>
                                        <td>Status</td>
                                        <td class="colon-payment-info">:</td>
                                        <td><span class="payment-status">Done</span></td>
                                    </tr>
                                    <tr>
                                        <td>Rating</td>
                                        <td class="colon-payment-info">:</td>
                                        <td><span class="payment-status"><?= $order["Rating"]; ?></span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                    <?php else: ?>
                        
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex-container">
                <h3>Notes</h3>
                <textarea id="note-input" name="notes" placeholder="Tambah notes anda disini"></textarea>
                <button id="save-note" type="submit">Save Changes</button>
            </div>
        </form>
    </div>
    <!-- signout -->
    <div id="popupSignout" class="modal hidden">
        <div class="modal-content">
            <p>Are you sure you want to sign out?</p>
            <button id="konfirmSignout">Yes</button>
            <button id="cancelSignout">No</button>
        </div>
    </div>
    <script src="kasirFunction.js"></script>
</body>

</html>