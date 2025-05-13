    <?php
session_start();

// Include connection.php
include('../connection.php');

date_default_timezone_set('Asia/Jakarta'); // Set to your desired time zone

// Reset pesananmakanan to 0 when the page loads
    $sqlReset = "UPDATE status SET Status = 1 WHERE StatusID = 6";
    $conn->query($sqlReset);
    
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
        FROM order_list ol
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
        FROM order_list ol
        WHERE status = 'completed' 
        AND DATE(created_at) = CURDATE()";

$resultPromo = $conn->query($sql);

if ($resultPromo->num_rows > 0) {
    $row = $resultPromo->fetch_assoc();
    $promo_terpakai = $row['promo_terpakai'];
} else {
    $promo_terpakai = 0;
}

// Retrieve userID from session
if (isset($_SESSION['userID'])) {
    $userID = $_SESSION['userID'];

    // Update cashier status to 'active'
    $updateStatusQuery = "UPDATE user SET status = 'active', updated_at = NOW() WHERE UserID = ? AND kategori_user = 'kasir'";
    $stmt = $conn->prepare($updateStatusQuery);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->close();

    // Check last shift creation
    $checkShiftQuery = "SELECT last_shift_created FROM user WHERE UserID = ? AND kategori_user = 'kasir'";
    $stmt = $conn->prepare($checkShiftQuery);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $lastShiftCreated = $result->fetch_assoc()['last_shift_created'];
        $currentDate = date("Y-m-d");

        // If no shift today, create a new shift
        if (!$lastShiftCreated || date("Y-m-d", strtotime($lastShiftCreated)) !== $currentDate) {
            // Retrieve ShiftKasirID for the logged-in user
            $shiftKasirQuery = "SELECT ShiftKasirID FROM shiftkasir WHERE UserID = ? LIMIT 1";
            $stmt = $conn->prepare($shiftKasirQuery);
            $stmt->bind_param("i", $userID);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $shiftKasirID = $result->fetch_assoc()['ShiftKasirID'];

                // Insert into historyshift table
                $insertHistoryQuery = "INSERT INTO historyshift (ShiftKasirID, created_at) VALUES (?, NOW())";
                $stmt = $conn->prepare($insertHistoryQuery);
                $stmt->bind_param("i", $shiftKasirID);
                $stmt->execute();
                $stmt->close();

                // Update the last_shift_created timestamp
                $updateShiftTimeQuery = "UPDATE user SET last_shift_created = NOW() WHERE UserID = ?";
                $stmt = $conn->prepare($updateShiftTimeQuery);
                $stmt->bind_param("i", $userID);
                $stmt->execute();
                $stmt->close();
                
            } else {
                die("No active ShiftKasir found for the logged-in user.");
            }
        }
    }
} else {
    die("User is not logged in. Please log in first.");
}

// Fetch cashier name from 'user' table
$cashierQuery = "SELECT nama FROM user WHERE status = 'active' AND kategori_user = 'kasir' AND UserID = ?";
$stmt = $conn->prepare($cashierQuery);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $cashierName = $result->fetch_assoc()['nama'];
} else {
    $cashierName = "No active cashier found";
}

// Reset pesananmakanan to 0 when the page loads
$sqlReset = "UPDATE status SET Status = 0 WHERE StatusID = 1";
$conn->query($sqlReset);

// Query to retrieve category data
$sql = "SELECT * FROM kategori";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $categories = array();
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
} else {
    $categories = array();
}

// Query to retrieve menu data
$sql = "
SELECT m.MenuID, m.nama_menu, m.image, m.harga, k.kategori
FROM menu m
JOIN kategori k ON m.KategoriID = k.KategoriID
";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $menu_data = array();
    while ($row = $result->fetch_assoc()) {
        $menu_data[] = $row;
    }
} else {
    $menu_data = array();
}

// Function to delete ongoing orders
function deleteOngoingOrder($conn) {
    if (isset($_SESSION['userID']) && isset($_SESSION['OrderID'])) {
        $orderID = $_SESSION['OrderID'];
        
        // Delete ongoing orders by OrderID
        $sql = "DELETE FROM order_list WHERE OrderID = ? AND status = 'ongoing'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $orderID);
        
        if ($stmt->execute()) {
            unset($_SESSION['OrderID']); // Remove OrderID from session after deletion
        } else {
            echo "Failed to delete ongoing order: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Check for AJAX request to delete ongoing orders
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_ongoing') {
    deleteOngoingOrder($conn);
    exit; // Stop PHP execution after deletion is complete
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Home Kasir</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<style>
    .timestamp-container {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-size: 16px;
        color: #333;
        width: 200px; /* Optional: Adjust as needed */
        margin: 0 auto; /* Center the container horizontally */
    }

    .timestamp-label {
        font-weight: bold;
        margin-right: 5px;
    }

    .timestamp {
        color: #555;
        font-size: 50px;
        font-style: italic;
    }


    .containerAFK {
    display: flex;  
    flex-direction: column;                /* Flexbox for centering */
    justify-content: center;         /* Horizontally center */
    align-items: center;             /* Vertically center */
    height: calc(100vh - 50px);      /* Adjust the height if you have a header/footer (replace 50px with the actual size) */
    width: calc(150vh - 0px);                   /* Full width */
    background-color: white;         /* White background */
    padding: 20px;                   /* Add padding to create space around the content */
    box-sizing: border-box;          /* Ensure padding is included in the total height/width */
    border-radius: 10px;
}

.teksAFK {
    font-size: 24px;                 /* Adjust the font size as needed */
    color: #333;                     /* Set the text color */
    text-align: center;              /* Center the text */
}

.btn-pesanan {
    color: white;
    background-color: #C47676;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 16px;
    margin: 10px;
    font-size: 18px;
    cursor: pointer;
    white-space: nowrap;
    flex-shrink: 0;
}

.btn-pesanan:hover {
    background-color: #7D0000;
    color: white;
}

div img {
    max-width: 100%;  /* Scale the image to fit the width of its container */
    height: auto;     /* Maintain aspect ratio */
    display: block;   /* Remove bottom space in some browsers */
    margin: 0 auto;   /* Center the image horizontally if the container is narrower than the image */
    padding: 20px;
}

h1{
    font-size: 24px;
    color: #333;
    text-align: center;
    margin-bottom: 0px;
}

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
        
        .MenuSidebar-footer {
    margin-top: auto;
    margin-bottom: 80px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}
</style>

<body>
    <div class="MenuSidebar">
        <a href="betaAFK.php" class="MenuSidebar-item">
            <img src="images/shopping-bag-regular-240.png" alt="Icon 1" class="icon">
            <span class="label"> Home Screen</span>
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
    <div class="containerAFK">
        <h1>Nama Kasir : <?php echo htmlspecialchars($cashierName); ?></h1>
        <div>
            <img src="images/wait.png" alt="Waiting">
        </div>
        <div class="teksAFK">Menunggu pesanan...</div>
        <form method="POST" action="betaKasir.php" style="padding: 20px">
            <a class="btn-pesanan" style="text-decoration: none;" href="betaKasir.php" name="terima_pesanan">Buat pesanan</a>
            <!-- <button type="submit" class="btn-pesanan" name="terima_pesanan">Buat pesanan</button> -->
        </form>
        <div class="timestamp-container">
            <span class="timestamp" id="current-time"></span>
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
        function updateTime() {
            const currentTimeElement = document.getElementById('current-time');
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            currentTimeElement.textContent = `${hours}:${minutes}:${seconds}`;
        }

        setInterval(updateTime, 1000);
        updateTime();

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
                                alert(response.message);
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
        
        function closeOrderAutomatically() {
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
                        console.log("Order closed successfully: " + response.message);
                    } else {
                        console.error("Error: " + response.error);
                    }
                } catch (e) {
                    console.error("Response server tidak valid.");
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
    }
    
    // Jalankan fungsi setiap 1 jam (3600000 ms)
    setInterval(closeOrderAutomatically, 10000);
    
    // Jalankan fungsi saat pertama kali halaman dimuat
    closeOrderAutomatically();

    </script>
</body>

</html>