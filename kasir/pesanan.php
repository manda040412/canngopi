<?php
session_start();

// Include connection.php to reuse the database connection
require_once '../connection.php';

if (!isset($_SESSION['userID']) || empty($_SESSION['userID'])) {
    // Redirect to index.php if not logged in
    header("Location: index.php");
    exit(); // Ensure no further code is executed
}

// Ambil data dari URL
$MenuID = isset($_GET['MenuID']) ? htmlspecialchars($_GET['MenuID']) : null;

// Query untuk mengambil detail menu berdasarkan MenuID jika diperlukan
if ($MenuID) {
    $sql = "SELECT * FROM menu WHERE MenuID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $MenuID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $pesanan = $result->fetch_assoc();
    } else {
        $pesanan = null; // Jika tidak ada menu ditemukan
    }
} else {
    $pesanan = null; // Jika tidak ada MenuID
}

// Ensure OrderID is available from the session
$OrderID = isset($_SESSION['OrderID']) ? $_SESSION['OrderID'] : null;
if (!$OrderID) {
    die("OrderID tidak ditemukan.");
}

// Get MenuID from URL
$MenuID = isset($_GET['MenuID']) ? $_GET['MenuID'] : null;
$OrderID = isset($_GET['OrderID']) ? $_GET['OrderID'] : null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $OrderID = $_POST['OrderID'];
    $MenuID = $_GET['MenuID']; // MenuID diambil dari URL
    $Quantity = $_POST['Quantity'];
    $Notes = $_POST['Notes'];

    // Jika kuantitas 0, hapus pesanan
    if ($Quantity <= 0) {
        // Ambil Quantity dan harga per item di order_items sebelum menghapus
        $queryGetQuantity = "SELECT Quantity, MenuID FROM order_items WHERE OrderID = '$OrderID' AND MenuID = '$MenuID'";
        $resultGetQuantity = $conn->query($queryGetQuantity);

        if ($resultGetQuantity && $resultGetQuantity->num_rows > 0) {
            $row = $resultGetQuantity->fetch_assoc();
            $quantityToDelete = $row['Quantity']; // Quantity yang dihapus
            $menuID = $row['MenuID']; // MenuID untuk mengambil harga item

            // Ambil harga per item dari tabel Menu
            $queryGetPrice = "SELECT harga FROM menu WHERE MenuID = '$menuID'";
            $resultGetPrice = $conn->query($queryGetPrice);
            if ($resultGetPrice && $resultGetPrice->num_rows > 0) {
                $rowPrice = $resultGetPrice->fetch_assoc();
                $pricePerItem = $rowPrice['harga']; // Harga per item

                // Hitung pengurangan total harga
                $totalPriceToDelete = $quantityToDelete * $pricePerItem;

                // Hapus item dari order_items
                $queryDelete = "DELETE FROM order_items WHERE OrderID = '$OrderID' AND MenuID = '$MenuID'";

                if ($conn->query($queryDelete)) {
                    echo "Pesanan berhasil dihapus!";

                    // Kurangi Quantity dan total_harga di order_list
                    $queryReduceQuantityAndPrice = "UPDATE order_list 
                                                     SET Quantity = Quantity - $quantityToDelete, 
                                                         total_harga = total_harga - $totalPriceToDelete 
                                                     WHERE OrderID = '$OrderID'";

                    if ($conn->query($queryReduceQuantityAndPrice)) {
                        // Redirect ke halaman ringkasan pesanan atau halaman lain jika perlu
                        header("Location: betaKasir.php");
                        exit;
                    } else {
                        echo "Error saat memperbarui Quantity dan total_harga: " . $conn->error;
                        exit;
                    }
                } else {
                    echo "Error saat menghapus pesanan: " . $conn->error;
                    exit;
                }
            } else {
                echo "Harga menu tidak ditemukan.";
                exit;
            }
        } else {
            echo "Pesanan tidak ditemukan untuk dihapus.";
            exit;
        }
    }

    // Jika kuantitas lebih dari 0, tambahkan atau perbarui pesanan
    // Cek apakah pesanan sudah ada
    $queryCheck = "SELECT * FROM order_items WHERE OrderID = '$OrderID' AND MenuID = '$MenuID'";
    $result = $conn->query($queryCheck);

    if ($result && $result->num_rows > 0) {
        // Pesanan sudah ada, lakukan update
        $menu = $result->fetch_assoc();
        $harga = $pesanan['harga'];
        $sub_total = $Quantity * $harga;

        $queryUpdate = "UPDATE order_items 
                        SET Quantity = '$Quantity', sub_total = '$sub_total', Notes = '$Notes'
                        WHERE OrderID = '$OrderID' AND MenuID = '$MenuID'";

        if ($conn->query($queryUpdate)) {
            echo "Pesanan berhasil diperbarui!";
            header("Location: betaKasir.php");
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        // Pesanan baru, lakukan insert
        $queryHarga = "SELECT harga FROM menu WHERE MenuID = '$MenuID'";
        $resultHarga = $conn->query($queryHarga);

        if ($resultHarga && $resultHarga->num_rows > 0) {
            $menu = $resultHarga->fetch_assoc();
            $harga = $pesanan['harga'];
            $sub_total = $Quantity * $harga;

            $queryInsert = "INSERT INTO order_items (OrderID, MenuID, Quantity, sub_total, Notes) 
                            VALUES ('$OrderID', '$MenuID', '$Quantity', '$sub_total', '$Notes')";

            if ($conn->query($queryInsert)) {
                echo "Pesanan berhasil ditambahkan!";
                // Tambahkan Quantity ke tabel order_list
                $queryAddQuantity = "UPDATE order_list 
                                     SET Quantity = Quantity + $Quantity 
                                     WHERE OrderID = '$OrderID'";
                $conn->query($queryAddQuantity);
                header("Location: betaKasir.php");
            } else {
                echo "Error: " . $conn->error;
            }
        } else {
            echo "Menu tidak ditemukan.";
        }
    }

    // Tutup koneksi
    $conn->close();
}

$conn->close();
?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Detail Pesanan</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- <link rel="stylesheet" href="styles.css"> -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <!-- <script src="kasirFunction.js"></script> -->
    <style>
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

        .MenuSidebar {

            position: fixed;
            top: 0;
            left: 15px;
            width: 80px;
            height: 100vh;
            background: linear-gradient(to bottom,
                    #FF0000 0%,
                    #C30000 40%,
                    #8A1518 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            /* z-index: 1000; Untuk memastikan sidebar berada di atas elemen lain */
        }
.MenuSidebar-footer {
    margin-top: auto;
    margin-bottom: 80px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

        .content {
            /* width: 75%; */
            padding: 4px;
            box-sizing: border-box;
            overflow-y: auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            /* Responsif grid */
            gap: 10px;
            justify-content: s;
            overflow-y: scroll;
            /* Allow vertical scrolling */
            overflow-x: hidden;
            /* Hide horizontal scrolling */
            scrollbar-width: none;
            /* Hide scrollbar */
        }

        .content h2 {
            width: 100%;
            font-size: 32px;
            font-weight: bold;
            color: #000000;
            margin-top: 0px;
            margin-bottom: 0px;
            padding-bottom: 10px;
        }

        .MenuSidebar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            text-align: center;
            border-radius: 8px;
            text-decoration: none;
            /* Menghapus garis bawah dari link */
            color: #fff;
            /* Warna teks untuk sidebar item */
            transition: background-color 0.3s ease;
        }

        .MenuSidebar a {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .MenuSidebar-item img {
            filter: grayscale(100%);
            /* Mengubah gambar menjadi grayscale untuk default state */
            transition: filter 0.3s ease;
            /* Transisi halus untuk efek hover */
        }

        .MenuSidebar-item.active img,
        .MenuSidebar-item:hover img {
            filter: grayscale(0%);
            /* Mengembalikan gambar ke warna penuh saat hover atau aktif */
        }

        .icon {
            width: 40px;
            height: 40px;
            margin-top: 30px;
            margin-bottom: 5px;
            background-color: #fff;
            border: 2px solid #fff;
            border-radius: 15px;
            padding: 10px;
        }

        .label {
            font-size: 12px;
            text-align: center;
        }

        .MenuSidebar-footer {
            margin-top: auto;
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .MenuSidebar-footer .icon {
            width: 40px;
            height: 40px;
            margin-bottom: 5px;
            background-color: #fff;
            border: 2px solid #fff;
            border-radius: 15px;
            padding: 10px;
        }

        .container {
            background-color: #fff;
            width: calc(100% - 100px);
            margin-left: 110px;
            margin-top: 30px;
            /* Add space at the top */
            margin-bottom: 30px;
            /* Add space at the bottom */
            margin-right: 30px;
            padding: 20px;
            border-radius: 20px;
            box-sizing: border-box;
            overflow-y: auto;
            height: calc(100vh - 60px);
            /* Adjust height to accommodate margin */
            display: flex;
            flex-direction: column;
        }

        .containerKatalog {
            background-color: #fff;
            width: calc(100% - 100px);
            margin-left: 110px;
            margin-top: 30px;
            /* Add space at the top */
            margin-bottom: 30px;
            /* Add space at the bottom */
            padding: 20px;
            border-radius: 20px;
            box-sizing: border-box;
            height: calc(100vh - 60px);
            /* Adjust height to accommodate margin */
            display: flex;
            flex-direction: column;
        }

        .sticky-section {
            position: sticky;
            top: 0;
            background-color: #fff;
            z-index: 10;
            /* Ensure it stays on top of other content */
        }

        .sticky-section-notes {
            display: flex;
            flex-direction: row;
            align-items: center;
            position: sticky;
            top: 0;
            background-color: #fff;
            z-index: 10;
            padding-bottom: 10px;
        }

        .sticky-section-notes h1 {
            margin-bottom: 0;
        }

        h1 {
            margin: 0;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .divider {
            height: 2px;
            background-color: #ddd;
            width: 100%;
        }

        .divider-list {
            height: 2px;
            background-color: #ddd;
            width: 100%;
            margin-bottom: 10px;
            margin-top: 10px;
        }

        .bagiDua {
            display: flex;
            gap: 10px;
            overflow-y: auto;
            box-sizing: border-box;

        }

        /* styles.css */
        body {
            font-family: Poppins, sans-serif;
            display: flex;
            margin: 0;
            padding: 0;
        }

        .container-menu {
            background-color: #ffffff;
            /* Warna putih */
            border-radius: 15px;
            /* Border bulat */
            /* display: flex; Flex layout */
            flex-direction: column;
            /* Mengatur arah flex ke kolom */
            padding: 20px;
            /* Padding dalam container */
            justify-content: center;
            /* Konten di tengah secara horizontal */
            align-items: center;
            /* Konten di tengah secara vertikal */
            flex-grow: 1;
            /* Mengambil sisa ruang yang ada */
            overflow-y: auto;
            /* Membuat container scrollable secara vertikal */
            margin-top: 20px;
            /* Margin atas */
            margin-bottom: 10px;
            /* Margin bawah */
            scrollbar-width: none;
            /* For Firefox */
            -ms-overflow-style: none;
            /* For Internet Explorer and Edge */
        }

        .container-menu::-webkit-scrollbar {
            display: none;
            /* For Chrome, Safari, and Opera */
        }

        /* Order item */
        .order-item-menu {
            margin-bottom: 20px;
        }

        .labels-grid {
            display: grid;
            grid-template-columns: 200px 0.8fr 0.5fr 1fr;
            gap: 10px;
            text-align: center;
            font-weight: bold;
            color: #8C1D1D;
            margin-bottom: 10px;
        }

        .item-grid {
            display: grid;
            grid-template-columns: 80px 1fr 1fr 1fr 1fr;
            gap: 10px;
            align-items: center;
        }

        .item-image img {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
        }

        .item-details {
            text-align: left;

        }

        .item-name {
            font-weight: 500;
            font-size: 16px;
        }

        .item-price-menu,
        .item-total,
        .item-quantity {
            text-align: center;
            color: #8C1D1D;
            font-size: 16px;
        }

        .item-quantity {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantity-btn {
            background-color: #8C1D1D;
            color: white;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 8px;
            margin: 0 5px;
        }

        #menuQuantity {
            width: 50px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 5px;
        }

        /* Notes section */
        .notes-section {
            margin-top: 20px;
            margin-bottom: 20px;
            text-align: left;
        }

        .notes-section label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .notes-section textarea {
            height: 100px;
            font-family: Poppins, sans-serif;
            resize: none;
        }

        #menuNote {
            width: calc(100% - 20px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 14px;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;

        }

        .back-btn,
        .add-btn {
            padding: 12px 24px;
            border-radius: 24px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            width: 30%;
        }

        .back-btn {
            background-color: #D69D9D;
            color: white;
            margin-left: 100px;
        }

        .add-btn {
            background-color: #8C1D1D;
            color: white;
            margin-right: 100px;
        }

        /* Modal overlay */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            /* Semi-transparent background */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            /* Ensures modal is on top */
        }

        /* Hide modal by default */
        .hidden {
            display: none;
        }

        /* Modal content */
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 300px;
        }

        /* Modal text */
        .modal-content p {
            font-size: 18px;
            margin-bottom: 20px;
        }

        /* Button styles */
        .modal-content button {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        /* Confirm (Yes) button */
        #confirmSignout {
            background-color: #f44336;
            /* Red */
            color: white;
            font-weight: 400;
        }

        /* Cancel (No) button */
        #cancelSignout {
            background-color: #910000;
            /* Light gray */
            color: rgb(255, 255, 255);
            font-weight: 400;
        }

        /* Button hover effects */
        #confirmSignout:hover {
            background-color: #d32f2f;
        }

        #cancelSignout:hover {
            background-color: #bbb;
        }
    </style>

</head>


<body>
    <div class="MenuSidebar">
        <a href="BetaAFK.php" class="MenuSidebar-item">
            <img src="images/shopping-bag-regular-240.png" alt="Icon 1" class="icon">
            <span class="label"> Home Screen</span>
        </a>
        <a href="betaRiwayat.html" class="MenuSidebar-item">
            <img src="images/history-regular-240.png" alt="Icon 2" class="icon">
            <span class="label">Riwayat pesanan</span>
        </a>
        <div class="nama-kasir">
        </div>
        <div class="MenuSidebar-footer">
            <img src="logo/logo.png" alt="Sign Out" class="icon">
            <span class="label">Sign Out</span>
        </div>
    </div>
    <div class="containerKatalog">
        <div class="sticky-section">
            <h1 style="margin-bottom: 10px;">Pesan makanan</h1>
        </div>
        <div class="divider"></div>
        <div class="bagiDua">

            <!-- Container Menu -->
            <form class="container-menu" action="pesanan.php?MenuID=<?php echo $MenuID; ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="OrderID" value="<?php echo $_SESSION['OrderID']; ?>">

                <!-- Content -->
                <div class="order-item-menu">
                    <?php if ($pesanan) { ?>
                        <div class="labels-grid">
                            <p class="label-item">Item</p>
                            <p class="label-price">Price</p>
                            <p class="label-total">Total</p>
                            <p class="label-qty">Qty</p>
                        </div>

                        <div class="item-grid">
                            <div class="item-image">
                            <?php if (!empty($pesanan['image'])) { 
                            // Menyusun base URL dan mengonversi path gambar di database
                            $baseURL = "https://cobaadmin.canngopi.com"; // Base URL domain Anda
                            $imagePath = str_replace('/admin', '', $pesanan['image']); // Menghapus '/admin' agar URL bisa mengaksesnya
                            
                            // Menampilkan gambar
                            ?>
                            <img src="<?php echo htmlspecialchars($baseURL . $imagePath); ?>" alt="<?php echo htmlspecialchars($pesanan['nama_menu']); ?>" style="max-width: 200px;">
                            <?php } else { ?>
                            <!-- Tampilkan pesan atau gambar default jika image tidak ada -->
                            <img src="default-image.jpg" alt="Default Image" style="max-width: 200px;">
                            <?php } ?>
                            </div>
                            <div class="item-details">
                                <p class="item-name"><?php echo $pesanan['nama_menu']; ?></p>
                            </div>
                            <div class="item-price-menu">
                                <p class="price-text">Rp <?php echo number_format($pesanan['harga'], 0, ',', '.'); ?></p>
                            </div>
                            <div class="item-total">
                                <p class="total-price" id="totalPrice">Rp <?php echo number_format($pesanan['harga'], 0, ',', '.'); ?></p>
                            </div>
                            <div class="item-quantity">
                                <button type="button" class="quantity-btn" id="decreaseQuantity">-</button>
                                <input type="number" id="menuQuantity" name="Quantity" value="1" min="0">
                                <button type="button" class="quantity-btn" id="increaseQuantity">+</button>
                            </div>
                        </div>

                        <!-- text area notes -->
                        <div class="notes-section">
                            <label for="menuNote">Catatan</label>
                            <textarea id="menuNote" name="Notes" placeholder="Tambahkan catatan dari pelanggan disini"></textarea>
                        </div>
                    <?php } else { ?>
                        <p>Menu tidak ditemukan.</p>
                    <?php } ?>
                </div>

                <div class="action-buttons">
                    <button type="button" class="back-btn" onclick="window.location.href='betaKasir.php'">Kembali</a></button>
                    <button type="submit" class="add-btn" id="addOrderBtn">Tambahkan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- signout -->
    <div id="popupSignout" class="modal hidden">
        <div class="modal-content">
            <p>Are you sure you want to sign out?</p>
            <button id="konfirmSignout">Yes</button>
            <button id="cancelSignout">No</button>
        </div>
    </div>

    <script>
        const decreaseButton = document.getElementById('decreaseQuantity');
        const increaseButton = document.getElementById('increaseQuantity');
        const quantityInput = document.getElementById('menuQuantity');
        const totalPriceElement = document.getElementById('totalPrice');

        const itemPrice = <?php echo $pesanan['harga']; ?>;

        decreaseButton.addEventListener('click', () => {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > 0) {
                quantityInput.value = currentValue - 1;
                updateTotalPrice(); // Update total price setelah decrease
            }
        });

        increaseButton.addEventListener('click', () => {
            let currentValue = parseInt(quantityInput.value);
            quantityInput.value = currentValue + 1;
            updateTotalPrice(); // Update total price setelah increase
        });

        function updateTotalPrice() {
            const quantity = parseInt(quantityInput.value);
            const totalPrice = itemPrice * quantity;

            // Jika quantity 0, tampilkan "Rp 0" sebagai total price
            totalPriceElement.textContent = quantity === 0 ? "Rp 0" : "Rp " + totalPrice.toLocaleString("id-ID");
        }

        // Inisialisasi awal
        updateTotalPrice();
    </script>
</body>

</html>