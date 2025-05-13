<?php
// Sertakan koneksi ke database
include('../connection.php');

session_start();

date_default_timezone_set('Asia/Jakarta'); // Set to your desired time zone


if (!isset($_SESSION['userID']) || empty($_SESSION['userID'])) {
    // Redirect to index.php if not logged in
    header("Location: index.php");
    exit(); // Ensure no further code is executed
}


$userID = $_SESSION['userID'];

// Query untuk mendapatkan riwayat pesanan dengan status "completed"
$sql = "SELECT * 
        FROM order_list 
        WHERE status = 'completed' 
        AND DATE(created_at) = CURDATE() 
        ORDER BY created_at DESC";

$result = $conn->query($sql);

// Query untuk mendapatkan total penjualan per hari
$sql = "SELECT SUM(Total_harga) as total_harga 
        FROM order_list 
        WHERE status = 'completed' 
        AND DATE(created_at) = CURDATE()";

$resultPenjualan = $conn->query($sql);

if ($resultPenjualan->num_rows > 0) {
    $row = $resultPenjualan->fetch_assoc();
    $penjualan_per_hari = $row['total_harga'];
} else {
    $penjualan_per_hari = 0;
}

// Query untuk mendapatkan jumlah menu terjual per hari
$sql = "SELECT SUM(Quantity) as menu_terjual 
        FROM order_list 
        WHERE status = 'completed' 
        AND DATE(created_at) = CURDATE()";

$resultMenu = $conn->query($sql);

if ($resultMenu->num_rows > 0) {
    $row = $resultMenu->fetch_assoc();
    $menu_terjual = $row['menu_terjual'];
} else {
    $menu_terjual = 0;
}

// Query untuk mendapatkan jumlah promo terpakai per hari
$sql = "SELECT SUM(total_promo) as promo_terpakai 
        FROM order_list 
        WHERE status = 'completed' 
        AND DATE(created_at) = CURDATE()";

$resultPromo = $conn->query($sql);

if ($resultPromo->num_rows > 0) {
    $row = $resultPromo->fetch_assoc();
    $promo_terpakai = $row['promo_terpakai'];
} else {
    $promo_terpakai = 0;
}

// Menutup koneksi
$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Riwayat Penjuala</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    <style>
        .konfirmBtn {
            /* background-color: #910000; */
            /* color: rgb(255, 255, 255); */
            font-weight: 400;
        }

        .cancelBtn {
            background-color: #910000;
            color: rgb(255, 255, 255);
            font-weight: 400;
        }

        .konfirmBtn:hover {
            background-color: #bbb;
        }

        .cancelBtn:hover {
            background-color: #bbb;
        }

        .modal-content button {
            color: white;
            background-color: #C47676;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .modal-content button:hover {
            background-color: #7D0000;
            color: white;
        }
    </style>

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
            <span class="label">Setting</span>
        </div>
    </div>
    <div class="container">
        <div class="sticky-section">
            <h1>Riwayat pesanan</h1>
        </div>
        <div class="divider"></div>
        <div class="main-content">
            <?php
            // Mengecek apakah ada hasil dari query
            if ($result->num_rows > 0) {
                $current_date = ""; // Menyimpan tanggal saat ini
                while ($row = $result->fetch_assoc()) {
                    $my_date = getdate(date("U"));
                    $order_date = date("d F Y", strtotime($row['created_at'])); // Mengambil tanggal pesanan
                    $order_time = date("H:i:s", strtotime($row['created_at'])); // Mengambil tanggal pesanan
                    $order_id = $row['OrderID'];
                    // $total_price = $row['Total_harga'];
                    $notes = !empty($row['notes']) ? $row['notes'] : "Tidak ada catatan";

                    // Memisahkan pesanan berdasarkan tanggal
                    if ($my_date != $current_date) {
                        if ($current_date != "") {
                            echo "</ul>"; // Tutup list sebelumnya jika ada
                        }
                        echo "<h2>$my_date[mday] $my_date[month] $my_date[year]</h2>"; // Tampilkan tanggal baru
                        echo "<ul class='notes-list'>";
                        $current_date = $my_date; // Update tanggal saat ini
                    }

                    // Menampilkan detail pesanan
                    echo "<li class='note-item'>
                    <h2 class='note-title'>Order ID #$order_id</h2>
                    <span class='note-date'>$order_time</span>
                    <div class='note-description-container'>
                        <p class='note-description'>$notes</p>
                        <div class='note-actions'>
                            <a class='btn-edit' href='notes.php?OrderID=$order_id'>Ubah</a>
                        </div>
                    </div>
                    </li>";
                }
                echo "</ul>"; // Menutup ul terakhir
            } else {
                echo "<p>Tidak ada pesanan untuk hari ini.</p>";
            }
            ?>
        </div>
    </div>


    <!-- setting -->
    <div id="popupSetting" class="modal hidden">
        <div class="modal-content">
            <p>What would you like to do?</p>
            <button id="signoutButton">Sign Out</button>
            <button id="closeOrderButton">Close Order</button>
        </div>
    </div>

    <!-- signout -->
    <div id="popupSignout" class="modal hidden">
        <div class="modal-content">
            <p>Are you sure you want to sign out?</p>
            <button class="konfirmBtn" id="konfirmSignout">Yes</button>
            <button class="cancelBtn" id="cancelSignout">No</button>
        </div>
    </div>

    <!-- close order -->
    <div id="popupCloseOrder" class="modal hidden">
        <div class="modal-content">
            <p>Are you sure you want to close this order?</p>
            <button class="konfirmBtn konfirmCloseOrder" id="konfirmCloseOrder">Yes</button>
            <button class="cancelBtn" id="cancelCloseOrder">No</button>
        </div>
    </div>

    <script>
        const settingModal = document.getElementById('popupSetting');
        const signoutButton = document.getElementById('signoutButton');
        const closeOrderButton = document.getElementById('closeOrderButton');
        const footerElement = document.querySelector('.MenuSidebar-footer');
        const signoutModal = document.getElementById('popupSignout');
        const closeOrderModal = document.getElementById('popupCloseOrder');
        const konfirmSignout = document.getElementById('konfirmSignout');
        const cancelSignout = document.getElementById('cancelSignout');
        const konfirmCloseOrder = document.getElementById('konfirmCloseOrder');
        const cancelCloseOrder = document.getElementById('cancelCloseOrder');

        // setting
        footerElement.addEventListener('click', function() {
            settingModal.classList.remove('hidden');
        });

        signoutButton.addEventListener('click', function() {
            settingModal.classList.add('hidden');
            signoutModal.classList.remove('hidden');
        });

        closeOrderButton.addEventListener('click', function() {
            settingModal.classList.add('hidden');
            closeOrderModal.classList.remove('hidden');
        });

        // close order
        cancelCloseOrder.addEventListener('click', function() {
            closeOrderModal.classList.add('hidden');
        });

        konfirmCloseOrder.addEventListener('click', function() {
            closeOrderModal.classList.add('hidden');
        });


        // sign out
        cancelSignout.addEventListener('click', function() {
            signoutModal.classList.add('hidden');
        });

        konfirmSignout.addEventListener('click', function() {
            window.location.href = 'index.php';
        });
        
        // Ajax untuk memasukkan data ke database
        document.querySelectorAll('.konfirmCloseOrder').forEach(button => {
            button.addEventListener('click', function() {
                // Mendapatkan data dari PHP
                const penjualan_per_hari = <?php echo json_encode($penjualan_per_hari); ?>;
                const menu_terjual = <?php echo json_encode($menu_terjual); ?>;
                const promo_terpakai = <?php echo json_encode($promo_terpakai); ?>;
                const UserID = <?php echo json_encode($userID); ?>;
                const tanggal = new Date().toISOString().split('T')[0];

                // Membuat permintaan AJAX
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "closeOrder.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                alert("Order berhasil dimasukkan.");
                                location.reload(); // Reload halaman
                            } else {
                                alert("Error: " + response.error);
                            }
                        } catch (e) {
                            alert("Response server tidak valid.");
                        }
                    }
                };

                // Mengirimkan data ke server
                xhr.send(
                    'penjualan_per_hari=' + encodeURIComponent(penjualan_per_hari) +
                    '&menu_terjual=' + encodeURIComponent(menu_terjual) +
                    '&promo_terpakai=' + encodeURIComponent(promo_terpakai) +
                    '&UserID=' + encodeURIComponent(UserID) +
                    '&tanggal=' + encodeURIComponent(tanggal)
                );
            });
        });
    </script>
</body>

</html>