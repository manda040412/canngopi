<?php
session_start();
// Include the database connection
include('../connection.php');

if (!isset($_SESSION['UserID']) || empty($_SESSION['UserID'])) {
    // Redirect to index.php if no session is found
    header("Location: index.php");
    exit();
}

// Check if form is submitted
// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $voucherName = $_POST['Nama'];
    $persentase = $_POST['Persentase'] ? $_POST['Persentase'] : null;
    $potongan = $_POST['potongan'] ? $_POST['potongan'] : null;
    $deskripsi = $_POST['deskripsi'];
    $syarat = $_POST['SK'];
    $masa_berlaku = $_POST['masa'];
    $jumlah = $_POST['Jumlah'];

    // Check that either persentase or potongan is provided, but not both
    if (($persentase && $potongan) || (!$persentase && !$potongan)) {
        echo "<script>alert('Please provide either a percentage or a discount amount, but not both.');</script>";
    } else {
        // Current timestamp for created_at and updated_at fields
        $timestamp = date("Y-m-d H:i:s");

        // SQL query to insert data into Voucher table
        $sql = "INSERT INTO voucher (Voucher, Persentase, Potongan, Jumlah, Masa_Berlaku, Deskripsi, Syarat, created_at, updated_at)
                VALUES ('$voucherName', '$persentase', '$potongan', '$jumlah', '$masa_berlaku', '$deskripsi', '$syarat', '$timestamp', '$timestamp')";

        if ($conn->query($sql) === TRUE) {
            echo "<script>
                    localStorage.setItem('formSubmitted', 'true');
                    document.getElementById('report-form').reset();
                    window.location.href = window.location.href; // Refresh the page
                  </script>";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
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
    <title>Generate Voucher</title>

    <!-- box icons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />

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
            padding-top: 15px;
            padding-left: 30px;
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
            font-family: 'Poppins', sans-serif;
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
        .voucher-details {
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
            margin-bottom: 15px;
        }

        .save-button {
            padding: 10px 20px;
            background-color: #6C0000;
            color: #fff;
            border: none;
            border-radius: 22px;
            cursor: pointer;
            font-size: 20px;
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
            <h2>Generate Voucher</h2>
        </div>
        <form id="report-form" action="promo_generate_voucher.php" method="POST">
            <div class="form-group">
                <label for="Nama">Nama Voucher</label>
                <input type="text" id="Nama" name="Nama" class="input-style2" placeholder="Masukkan nama voucher">
            </div>
            <div class="form-group">
               <div class="voucher-details">
                    <label for="Persentase">Persentase</label>
                    <input type="text" id="Persentase" name="Persentase" class="input-style" placeholder="....%">
               
                    <p>atau</p>
                 
                    <label for="potongan">Potongan IDR</label>
                    <input type="text" id="potongan" name="potongan" class="input-style1" placeholder="Rp.">
                </div>
            </div>  

            <div class="form-group">
                <label for="deskripsi">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" placeholder="Tambahkan deskripsi voucher" class="textarea-style"></textarea>
            </div>
            <div class="form-group">
                <label for="SK">S&K</label>
                <textarea id="SK" name="SK" placeholder="Masukkan syarat dan ketentuan voucher" class="textarea-style"></textarea>
            </div>
            <div class="form-group">
            <label for="Jumlah">Jumlah</label>
            <input type="number" id="Jumlah" name="Jumlah" class="input-style1" placeholder="Quantity voucher">
            </div>
            <div class="form-group">
                <label for="masa">Masa Berlaku</label>
                <input type="date" id="masa" name="masa" placeholder="Tentukan periode voucher" required>
            </div>
            <div class="button-container">
                <button type="button" class="save-button" onclick="validateAndSubmit()">Generate</button>
            </div>
        </form>
    </div>

    <div id="popup" class="popup">
        <div class="popup-content">
            <h2>Generating voucher file</h2>
            <img src="image/success.png" alt="Success">
            <button onclick="goBack()">Close</button>
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
        document.getElementById('Persentase').addEventListener('input', function() {
            const potonganInput = document.getElementById('potongan');
            if (this.value) {
                potonganInput.value = ''; // Clear the other field
            }
        });

        document.getElementById('potongan').addEventListener('input', function() {
            const persentaseInput = document.getElementById('Persentase');
            if (this.value) {
                persentaseInput.value = ''; // Clear the other field
            }
        });

        function validateAndSubmit() {
        const persentase = document.getElementById('Persentase').value;
        const potongan = document.getElementById('potongan').value;

        if ((persentase && potongan) || (!persentase && !potongan)) {
            alert('Please provide either a percentage or a discount amount, but not both.');
        } else {
            document.getElementById('report-form').submit();
        }
    }

    // Existing event listener for persentase
    document.getElementById('Persentase').addEventListener('input', function() {
        let value = this.value.replace(/[^0-9]/g, '');
        this.value = value ? value + '%' : '';
        
        // Clear potongan if persentase is filled
        document.getElementById('potongan').value = '';
    });

    // New event listener for potongan
    document.getElementById('potongan').addEventListener('input', function() {
        // Clear persentase if potongan is filled
        document.getElementById('Persentase').value = '';
    });

        function showPopup() {
            document.getElementById('popup').classList.add('show');
        }

        function closePopup() {
            document.getElementById('popup').classList.remove('show');
        }

        window.onload = function() {
            if (localStorage.getItem('formSubmitted') === 'true') {
                showPopup(); // Show the popup
                localStorage.removeItem('formSubmitted'); // Clear the flag
            }
        };

        function goBack() {
            window.location.href = "promo.php";
        }

        function showPopupBack() {
            document.getElementById('popupback').classList.add('show');
        }
        
        function closePopupBack() {
            document.getElementById('popupback').classList.remove('show');
        }

        //dibawah ini untuk ngatur persentase. jadi ketika admin masukin 
        //angka itu automatically dalam bentuk % input nya. ga cuma angka doang 
        document.getElementById('Persentase').addEventListener('input', function() {
        // Ambil nilai input
        let value = this.value;
        
        // Hapus karakter yang bukan angka
        value = value.replace(/[^0-9]/g, '');

        // Format nilai sebagai persentase
        if (value) {
            this.value = value + '%';
        } else {
            this.value = '';
        }
    });
    </script>
</body>

</html>