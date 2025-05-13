<?php
session_start(); // Start the session

// Include the database connection file
require_once ('../connection.php'); // Include the connection.php file

if (!isset($_SESSION['UserID']) || empty($_SESSION['UserID'])) {
    // Redirect to index.php if no session is found
    header("Location: index.php");
    exit();
}

if (isset($_GET['UserID'])) {
    $userID = $_GET['UserID'];

    // Fetch user data
    $sql = "SELECT u.UserID, u.nama, u.kategori_user, u.status, s.ShiftID, s.Shift, sk.status AS shift_status 
            FROM user u
            LEFT JOIN shiftkasir sk ON u.UserID = sk.UserID
            LEFT JOIN shift s ON sk.ShiftID = s.ShiftID
            WHERE u.UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();

    if ($userData) {
        $nama = $userData['nama'];
        $currentShiftID = $userData['ShiftID'];
        $shiftStatus = $userData['shift_status'];
    } else {
        echo "User not found!";
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'update_pin') {
        $userID = $_POST['UserID'];
        $nama = isset($_POST['nama']) ? $_POST['nama'] : ''; // Safe access
        $shiftID = $_POST['shift'];
        $oldPin = $_POST['verif-kode'];
        $newPin = $_POST['kode-baru'];
        $confirmPin = $_POST['konfirmasi-kode-baru'];

        // Fetch the stored PIN
        $stmt = $conn->prepare("SELECT pin FROM kasir WHERE UserID = ?");
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        $storedPin = $result->fetch_assoc()['pin'];

        if ($storedPin && $oldPin == $storedPin) {
            if ($newPin === $confirmPin) {
                $conn->begin_transaction();

                // Update PIN
                $updatePinStmt = $conn->prepare("UPDATE kasir SET pin = ? WHERE UserID = ?");
                $updatePinStmt->bind_param("si", $newPin, $userID);
                $updatePinStmt->execute();

                // Update name
                $updateNameStmt = $conn->prepare("UPDATE user SET nama = ? WHERE UserID = ?");
                $updateNameStmt->bind_param("si", $nama, $userID);
                $updateNameStmt->execute();

                // Update shift
                $updateShiftStmt = $conn->prepare("UPDATE shiftkasir SET ShiftID = ? WHERE UserID = ?");
                $updateShiftStmt->bind_param("ii", $shiftID, $userID);
                $updateShiftStmt->execute();

                $conn->commit();

                // Set session variable for success
                $_SESSION['success'] = true;

                // Redirect to the same page
                header("Location: " . $_SERVER['PHP_SELF'] . "?UserID=" . $userID);
                exit();
            } else {
                echo "New PIN and confirmation do not match.";
            }
        } else {
            echo "Old PIN is incorrect.";
        }
    }
}

// Close the connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kode Akses</title>

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
            z-index: 900;
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
            padding-right:30px;    
            padding-left: 30px;  
            padding-top: 15px;    
            padding-bottom: 30px;
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
        .content::-webkit-scrollbar{
            display: none;
        }

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ccc;
        }

        .header i {
            font-size: 28px;
            margin-right: 15px;
        }

        .header h2 {
            margin: 0;
            color: black;
        }

        .header button {
            background-color: #AA1919;
            border: 2px solid #AA1919;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 15px;
        }
        .header button:last-child {
            background-color: white;
            color: #AA1919;
        }

        h1{
            margin-top: 60px;
            margin-bottom: 30px;
            font-size: 28px;
        }
        
        h2 {
            font-size: 24px;
            margin: 0;
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

        
        .form-group p{
            font-size: 15px;
            font-family: Poppins, sans-serif;
            margin-right: 30px;
            color: #990000;
            font-weight: 600;
        }
        .voucher-details {
            display: flex;
            flex-direction: flex-start;
            justify-content: space-evenly;
            align-items: center;
        }
        
        .form-group label {
            width: 250px;
            margin-right: 15px;
            font-size: 18px;
        }

        .form-group input[type="text"]
         {
            padding: 12px;
            flex: auto;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: Poppins,sans-serif;
        }
        
        .form-group input[type="password"]
         {
            padding: 12px;
            flex: auto;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: Poppins,sans-serif;
        }

        .form-group select {
            padding: 12px;
            flex: auto;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: Poppins, sans-serif;
        }

        .input-style {
            flex: 0.1;
            resize: none;
            font-family: 'Poppins', sans-serif;
        }

        .input-style1 {
            flex: 0.3;
            resize: none;
            font-family: 'Poppins', sans-serif;
        }
        
        .input-style2 {
            flex: 1;
            resize: none;
            font-family: 'Poppins', sans-serif;
        }

        .button-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 25px;
        }

        .save-button {
            padding: 10px 20px;
            background-color: #6C0000;
            color: #fff;
            border: none;
            border-radius: 22px;
            cursor: pointer;
            font-size: 16px;
        }

        .save-button:hover {
            background-color: #d90101;
        }

        .popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s, opacity 0.3s ease;
            z-index: 1000;
        }

        .popupback,
        .popup-confirm {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s, opacity 0.3s ease;
            z-index: 1000;
        }
        .popup-content {
            background-color: #fff;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            width: 400px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }


        .popup-content h2 {
            font-size: 24px;
            color: #990000;
            margin-bottom: 30px;
        }

        .popup-content img {
            width: 100px;
            margin-bottom: 20px;
        }

        .popup-content button {
            padding: 10px 20px;
            background-color: #990000;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }

        .popup-content button:hover {
            background-color: #d90101;
        }

        .popupback-content {
            background-color: #fff;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            width: 400px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .popupback-content h2 {
            font-size: 24px;
            color: #990000;
            margin-bottom: 30px;
        }

        .popupback-content h3 {
            font-size: 20px;
            color: #990000;
            margin-bottom: 30px;
        }

        .popupback-content img {
            width: 100px;
            margin-bottom: 20px;
        }

        .popupback-content button1 {
            padding: 10px 20px;
            background-color: #990000;
            color: #fff;
            border: none;
            border-radius: 21px;
            cursor: pointer;
            font-size: 16px;
        }

        .popupback-content button:hover {
            background-color: #d90101;
        }

        .popup-confirm-content{
            background-color: #fff;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            width: 400px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .popup-confirm-content h2 {
            font-size: 24px;
            color: #990000;
            margin-bottom: 10px;
        }

        .popup-confirm-content p {
            font-size: 18px;
            color: #990000;
            margin-bottom: 30px;
        }

        .popup-confirm-content button1 {
            padding: 10px 20px;
            background-color: #990000;
            color: #fff;
            border: none;
            border-radius: 21px;
            cursor: pointer;
            font-size: 16px;
        }

        .popup-confirm-content button:hover {
            background-color: #d90101;
        }


        .popup.show {
            visibility: visible;
            opacity: 1;
        }

        .popupback.show {
            visibility: visible;
            opacity: 1;
        }

        .popup-confirm.show{
            visibility: visible;
            opacity: 1;
        }

        .back {
            padding: 10px 20px;
            background-color: #B27878;
            color: #fff;
            border: none;
            border-radius: 21px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 15px;
        }
        .back1 {
            padding: 10px 20px;
            background-color: #6C0000;
            color: #fff;
            border: none;
            border-radius: 21px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 15px;
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
        <a href="promo.php">
            <i class='bx bxs-purchase-tag'></i> Promo
        </a>
        <a href="events.php">
            <i class='bx bxs-calendar-event'></i> Events
        </a>
        <a href="access.php" class="active">                             
            <i class='bx bxs-key'></i> Access
        </a>
        <a href="index.php" class="logout">Logout</a>
    </div>
    <div class="content">
        <div class="header">
            <i class='bx bx-chevron-left' onclick="showPopupBack()" style="font-size: 54px;"></i>
            <h2>Edit Access Pin</h2>
        </div>
        <form id="update-form" form method="POST" action="edit_kode_akses.php?UserID=<?php echo $userID; ?>">
            <input type="hidden" name="UserID" value="<?php echo $userID; ?>">
            <input type="hidden" name="action" value="update_pin">

            <div class="form-group">
                <label for="nama">Nama Karyawan</label>
                <input type="text" id="nama" name="nama" class="input-style2" value="<?php echo htmlspecialchars($nama); ?>" required>
            </div>

            <div class="form-group">
            <label for="shift">Shift</label>
            <select id="shift" name="shift" class="input-style2" required>
                <option value="1" <?php echo ($currentShiftID == 1) ? 'selected' : ''; ?>>Pagi</option>
                <option value="2" <?php echo ($currentShiftID == 2) ? 'selected' : ''; ?>>Siang</option>
            </select>
        </div>

            <h1>Ganti Pin Akses</h1>

            <div class="form-group">
                <label for="verif-kode">Masukan pin akses sebelumnya</label>
                <input type="password" id="verif-kode" name="verif-kode" class="input-style2" placeholder="Masukkan pin akses sebelumnya disini">
            </div>

            <div class="form-group">
                <label for="kode-baru">Masukan pin akses baru</label>
                <input type="password" id="kode-baru" name="kode-baru" class="input-style2" placeholder="Masukkan pin akses baru disini">
            </div>

            <div class="form-group">
                <label for="konfirmasi-kode-baru">Masukan ulang kode akses baru</label>
                <input type="password" id="konfirmasi-kode-baru" name="konfirmasi-kode-baru" class="input-style2" placeholder="Masukkan ulang pin akses baru disini">
            </div>


            <div class="button-container">
                <button type="submit" class="save-button"  onclick="showPopupConfirm()">Ubah</button>
            </div>
        </form>
    </div>

    <div id="popup-confirm" class="popup-confirm">
        <div class="popup-confirm-content">
            <header>
                <h2>Yakin untuk melakukan perubahan kode akses?</h2>
                </header>
                <p>Setelah anda menyetujui perubahan kode akses, maka kode akses yang lama akan otomatis tidak berlaku</p>
                <div>
                <button id="confirm-button" class="back">Confirm</button>
                <button id="cancel-button" class="back1">Cancel</button>
                </div>
        </div>
    </div>

    <div id="popup" class="popup">
        <div class="popup-content">
            <h2>Berhasil mengubah kode akses</h2>
            <img src="image/success.png" alt="Success">
            <button id="close-popup-button" onclick="closePopup()">Close</button>
        </div>
    </div>

    <div id="popupback" class="popupback">
        <div class="popupback-content">
            <header>
            <h2>Yakin untuk kembali?</h2>
            </header>
            <h3>Perubahan yang anda buat belum tersimpan</h3>
            <div>
            <button onclick="goBack()" class="back">Ya</button>
            <button onclick="closePopupBack()" class="back1">Tidak</button>
            </div>
        </div>
    </div>


    <script>
       function handleFormSubmit() {
    showPopupConfirm();
    return false; // Prevent form submission
}

function goBack() {
    window.location.href = "access.php";
}

function showPopupBack() {
    document.getElementById('popupback').classList.add('show');
}

function closePopupBack() {
    document.getElementById('popupback').classList.remove('show');
}

function showPopup() {
    document.getElementById('popup-confirm').classList.remove('show');
    document.getElementById('popup').classList.add('show');
}

function closePopup() {
    document.getElementById('popup').classList.remove('show');
}

// Function to show confirmation popup
function showPopupConfirm() {
    var popupConfirm = document.getElementById('popup-confirm');
    popupConfirm.classList.add('show');
}

// Function to close confirmation popup
function closePopupConfirm() {
    var popupConfirm = document.getElementById('popup-confirm');
    popupConfirm.classList.remove('show');
}

// Function to show success popup after form submission
function showPopupSuccess() {
    var popup = document.getElementById('popup');
    popup.classList.add('show');
}

// Function to close success popup and redirect to access.php
function closePopupAndRedirect() {
    var popup = document.getElementById('popup');
    popup.classList.remove('show');
    window.location.href = "access.php";  // Redirect to access.php
}

// Event handler for the form submission
document.getElementById('update-form').addEventListener('submit', function (event) {
    event.preventDefault();  // Prevent form submission
    showPopupConfirm();      // Show confirmation popup
});

// Handle confirmation when "Confirm" button is pressed
document.getElementById('confirm-button').addEventListener('click', function () {
    closePopupConfirm();     // Close confirmation popup
    document.getElementById('update-form').submit();  // Submit the form
});

// Handle cancellation when "Cancel" button is pressed
document.getElementById('cancel-button').addEventListener('click', function () {
    closePopupConfirm();  // Close confirmation popup without submitting
});

// After the form submission, show the success popup
if (<?php echo json_encode(isset($_SESSION['success'])); ?>) {
    showPopupSuccess();
    <?php unset($_SESSION['success']); ?> // Clear session variable after showing
}

// Handle closing the success popup and redirecting when "Close" is pressed
document.getElementById('close-popup-button').addEventListener('click', function () {
    closePopupAndRedirect();  // Close success popup and redirect to access.php
});

</script>


</body>

</html>