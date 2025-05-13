<?php
// Start the session (if needed)
session_start();

// Include the database connection file
require_once ('../connection.php'); // Include the connection.php file

if (!isset($_SESSION['UserID']) || empty($_SESSION['UserID'])) {
    // Redirect to index.php if no session is found
    header("Location: index.php");
    exit();
}

// Initialize variables with default values
$VoucherID = "";
$Voucher = "";
$Persentase = "";
$Potongan = "";
$Deskripsi = "";
$Syarat = "";
$Masa_Berlaku = "";

// Check if the VoucherID is provided in the URL
if (isset($_GET['VoucherID'])) {
    $VoucherID = $_GET['VoucherID'];

    // Fetch the Voucher details from the database
    $sql = "SELECT * FROM voucher WHERE VoucherID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $VoucherID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Set the variables with the fetched data
        $Voucher = $row['Voucher'];
        $Persentase = $row['Persentase'];
        $Potongan = $row['Potongan'];
        $Deskripsi = $row['Deskripsi'];
        $Syarat = $row['Syarat'];
        $Masa_Berlaku = $row['Masa_Berlaku'];
    }
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data, with fallback to existing values if not set
    $Voucher = isset($_POST['Voucher']) ? $_POST['Voucher'] : $Voucher;
    $Persentase = isset($_POST['Persentase']) ? $_POST['Persentase'] : $Persentase;
    $Potongan = isset($_POST['Potongan']) ? $_POST['Potongan'] : $Potongan;
    $Deskripsi = isset($_POST['Deskripsi']) ? $_POST['Deskripsi'] : $Deskripsi;
    $Syarat = isset($_POST['Syarat']) ? $_POST['Syarat'] : $Syarat;
    $Masa_Berlaku = isset($_POST['Masa_Berlaku']) ? $_POST['Masa_Berlaku'] : $Masa_Berlaku;

    // Update the discount details in the database
    $sql_update = "UPDATE voucher SET 
        Voucher = ?, Persentase = ?, Potongan = ?, Deskripsi = ?, Syarat = ?, Masa_Berlaku = ?, updated_at = NOW()
        WHERE VoucherID = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sddsssi", $Voucher, $Persentase, $Potongan, $Deskripsi, $Syarat, $Masa_Berlaku, $VoucherID);

    if ($stmt_update->execute()) {
        // Set a success flag
        $success = true;
    } else {
        echo "<script>alert('Error updating Voucher: " . $stmt_update->error . "');</script>";
    }
}

// Close the database connection
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Voucher</title>

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
            padding-bottom: 20px;
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

        .form-group label {
            width: 120px;
            margin-right: 15px;
            font-size: 15px;
            font-family: Poppins, sans-serif;
        }
       
        .form-group p{
            font-size: 15px;
            font-family: Poppins, sans-serif;
            margin-right: 30px;
            color: #990000;
            font-weight: 600;
        }
        .Voucher-details {
            display: flex;
            flex-direction: flex-start;
            justify-content: space-evenly;
            align-items: center;
        }
       /*.label-atau {
            margin-left: 10px;
            position: absolute;
            left: 50%;      
            transform: translateX(-20%);     
        } */
        
        .form-group input[type="text"],  
        .form-group textarea {
            padding: 12px;
            flex: auto;
            font-size: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            margin-right: 30px;
        }

        .form-group input[type="number"]{
            padding: 12px;
            flex: 1;
            font-size: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            margin-right: 30px;
        }

        .form-group select {
            flex: auto;
            padding: 12px;
            font-size: 15px;
            color: #757575;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-right: 30px;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input[type="date"]{
            padding: 12px;
            font-size: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            margin-right: 30px;
            flex-grow: 0.2; 
        }

        textarea {
            flex: 1;
            height: 150px;
            resize: none;
            font-family: 'Poppins', sans-serif;
        }
        
        .textarea-style {
            height: 80px;
            resize: none;
            font-family: 'Poppins', sans-serif;
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
            font-family: Poppins, sans-serif;
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

        .popupback {
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

        .popup.show {
            visibility: visible;
            opacity: 1;
        }

        .popupback.show {
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
            <i class='bx bx-chevron-left' onclick="showPopupBack()" style="font-size: 54px;"></i>
            <h2>Detail Voucher</h2>
        </div>
        <form id="edit-Voucher-form" action="editvoucher.php?VoucherID=<?php echo $VoucherID; ?>" method="POST">
            <div class="form-group">
                <label for="Nama">Nama Voucher</label>
                <input type="text" id="Nama" name="Voucher" class="input-style2" value="<?php echo $Voucher; ?>" placeholder="Masukkan nama Voucher" required>
            </div>

            <div class="form-group">
                <div class="Voucher-details">
                    <label for="Persentase">Persentase</label>
                    <input type="text" id="Persentase" name="Persentase" class="input-style" value="<?php echo $Persentase; ?>" placeholder="....%" />

                    <p>atau</p>

                    <label for="Potongan">Potongan IDR</label>
                    <input type="text" id="Potongan" name="Potongan" class="input-style1" value="<?php echo $Potongan; ?>" placeholder="Rp." />
                </div>
            </div>

            <div class="form-group">
                <label for="Deskripsi">Deskripsi</label>
                <textarea id="Deskripsi" name="Deskripsi" class="textarea-style" placeholder="Tambahkan Deskripsi Voucher"><?php echo $Deskripsi; ?></textarea>
            </div>

            <div class="form-group">
                <label for="SK">S&K</label>
                <textarea id="SK" name="Syarat" class="textarea-style" placeholder="Masukkan Syarat dan ketentuan Voucher"><?php echo $Syarat; ?></textarea>
            </div>

            <div class="form-group">
                <label for="MasaBerakhir">Masa Berlaku</label>
                <input type="date" id="masa" name="Masa_Berlaku" class="input-style" value="<?php echo date('Y-m-d', strtotime($Masa_Berlaku)); ?>" required>
            </div>

            <div class="button-container">
                <button type="submit" class="save-button">Update Voucher</button>
            </div>
    </form>
    </div>

    <div id="popup" class="popup">
        <div class="popup-content">
            <h2>Berhasil memperbarui Voucher</h2>
            <img src="image/success.png" alt="Success">
            <button onclick="closePopup()">Close</button>
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
        // Event listener for the Persentase input
        document.getElementById('Persentase').addEventListener('input', function() {
            const PotonganInput = document.getElementById('Potongan');
            
            // If user starts typing in Persentase, clear the Potongan field
            if (this.value) {
                PotonganInput.value = ''; 
            }

            // Format the input to accept only numbers and add percentage sign
            let value = this.value.replace(/[^0-9]/g, ''); // Remove non-numeric characters
            this.value = value ? value + '%' : ''; // Add the % sign back
        });

        // Event listener for the Potongan input
        document.getElementById('Potongan').addEventListener('input', function() {
            const PersentaseInput = document.getElementById('Persentase');
            
            // If user starts typing in Potongan, clear the Persentase field
            if (this.value) {
                PersentaseInput.value = ''; 
            }
        });

        // Function to validate and submit the form
        function validateAndSubmit() {
            const Persentase = document.getElementById('Persentase').value;
            const Potongan = document.getElementById('Potongan').value;

            // Ensure that only one of Persentase or Potongan is provided
            if ((Persentase && Potongan) || (!Persentase && !Potongan)) {
                alert('Please provide either a percentage or a discount amount, but not both.');
            } else {
                // Submit the form if validation passes
                document.getElementById('report-form').submit();
            }
        }

        // Existing event listener for Persentase
        document.getElementById('Persentase').addEventListener('input', function() {
            let value = this.value.replace(/[^0-9]/g, '');
            this.value = value ? value + '%' : '';
            
            // Clear Potongan if Persentase is filled
            document.getElementById('Potongan').value = '';
        });

        // New event listener for Potongan
        document.getElementById('Potongan').addEventListener('input', function() {
            // Clear Persentase if Potongan is filled
            document.getElementById('Persentase').value = '';
        });

        function goBack() {
            window.location.href = "promo.php";
        }

        function showPopupBack() {
            document.getElementById('popupback').classList.add('show');
        }
        
        function closePopupBack() {
            document.getElementById('popupback').classList.remove('show');
        }

        function showPopup() {
            document.getElementById('popup').classList.add('show');
        }

        function closePopup() {
        window.location.href = "promo.php";
    }

    // Show the success popup if the success flag is set
    <?php if (isset($success) && $success): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('popup').classList.add('show');
        });
    <?php endif; ?>
    </script>
</body>

</html>
