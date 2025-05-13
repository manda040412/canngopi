<?php
session_start();
// Include the database connection
include('../connection.php');

// Initialize variables
$promoID = isset($_GET['PromoID']) ? intval($_GET['PromoID']) : 0;

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture form inputs
    $promoID = intval($_POST['PromoID']);
    $promoName = $_POST['promo_name'];
    $promoDescription = $_POST['promo_description'];
    $discountAmount = str_replace(['Rp ', ',', ' '], ['', '', ''], $_POST['discount_amount']);
    $termsConditions = $_POST['terms_conditions'];
    $masaBerlaku = $_POST['masa_berlaku'];
    
    if (is_numeric($discountAmount)) {
        // Update the promo in the database
        $updateQuery = "UPDATE promo SET nama_menu = ?, deskripsi = ?, pengurangan_harga = ?, syarat = ?, masa_berlaku = ? WHERE PromoID = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssssi", $promoName, $promoDescription, $discountAmount, $termsConditions, $masaBerlaku, $promoID);

        if ($stmt->execute()) {
            echo '<div id="popup" class="popup show">
                    <div class="popup-content">
                        <h2>Berhasil memperbarui promo!</h2>
                        <img src="image/success.png" alt="Success">
                        <button onclick="closePopup()">Close</button>
                    </div>
                  </div>';
        } else {
            echo '<div id="popup" class="popup show">
                    <div class="popup-content">
                        <h2>Error: ' . $stmt->error . '</h2>
                        <button onclick="closePopup()">Close</button>
                    </div>
                  </div>';
        }
    }
}

// Query to fetch promo details
$promoQuery = "SELECT * FROM promo WHERE PromoID = ? AND deleted_at IS NULL";
$stmt = $conn->prepare($promoQuery);
$stmt->bind_param("i", $promoID);
$stmt->execute();
$promoResult = $stmt->get_result();

// Fetch the promo details
if ($promoResult->num_rows > 0) {
    $promo = $promoResult->fetch_assoc();
} else {
    die("Promo not found.");
}

// Query to fetch menu items related to this promo
$menuQuery = "SELECT m.nama_menu, m.harga, m.image FROM menu m 
               JOIN menu_promo mp ON m.MenuID = mp.MenuID 
               WHERE mp.PromoID = ?";
$menuStmt = $conn->prepare($menuQuery);
$menuStmt->bind_param("i", $promoID);
$menuStmt->execute();
$menuResult = $menuStmt->get_result();

$menuItems = [];
while ($menuRow = $menuResult->fetch_assoc()) {
    $menuItems[] = $menuRow;
}

// Close the connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail promo aktif</title>

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
            padding-left:30px;    
            padding-top: 5px;
            padding-right: 30px;
            padding-bottom: 30px;      
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

        .content::-webkit-scrollbar{
            display: none;
        }
        
        .content h2 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            color: #333;
        }

        .content h2 i {
            margin-right: 10px;
            cursor: pointer;
        }

        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;

        }

        .form-group label {
            width: 150px;
            margin-right: 15px;
            font-size: 16px;
            color: #333;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            flex-grow: 1;
            padding: 10px;
            font-size: 14px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-family: Poppins,sans-serif;
            color: #757575;
        }

        .form-group input[type="date"]{
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: Poppins,sans-serif;
            color:#757575;
        }

        .form-group textarea {
            resize: none;
            height: 100px;
        }

        .menu-group {
            margin-top: 20px;
            margin-bottom: 20px;

        }

        .menu-selection {
            display: flex;
            gap: 20px;
            background-color: #F9F9F9;
            border-radius: 10px;
            padding: 15px;
        }

        .menu-items {
            display: flex;
            flex-direction: row;
            gap: 10px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            max-height: 280px;
            overflow-y: auto;
        }

        .selected-items {
            display: flex; /* Use flexbox for horizontal alignment */
            flex-wrap: wrap; /* Ensure items wrap to the next line if necessary */
            gap: 10px; /* 10px gap between images */
            align-items: start;
            flex-direction: column;
        }

        .selected-items .menu-item {
            flex: 0 0 auto; /* Prevents flex items from shrinking */
            width: auto; /* Adjust width as needed */
        }

        .selected-items .menu-item img {
            display: block; /* Removes any inline spacing */
            max-width: 100%; /* Ensures images do not exceed container width */
            height: auto; /* Maintains aspect ratio */
            object-fit: contain; /* Ensures images are contained within the container */
        }  
        
        .wrapper-selected-items{
            display: flex;
            flex-direction: row;
            margin-left: 5px;
            gap: 15px
        }

        h3 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #990000;
        }

        .selected-items h3 {
            color: #757575;
            grid-column: span 2;
            margin-bottom: 2px;
            margin-top: 0px;
            margin-left: 5px;
        }

        .menu-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            transition: transform 0.3s;
        }

        .menu-item img {
            width: 100%;
            height: auto;
            max-height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 5px;
        }

        .menu-item span {
            font-size: 12px;
            color: #333;
            text-align: center;
        }

        .menu-item span.price {
            color: #555;
            font-weight: bold;
        }

        .selected-items .menu-item img {
            max-height: 120px;
        }

        .form-actions {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .form-actions button {
            padding: 15px 30px;
            font-size: 16px;
            font-weight: bold;
            color: white;
            background-color: #990000;
            border: none;
            border-radius: 50px;
            cursor: pointer;
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
        <h2><i class='bx bx-chevron-left' onclick="showPopupBack()" style="font-size: 54px;"></i>Detail Promo</h2>

        <form method="POST" action="">
            <input type="hidden" name="PromoID" value="<?php echo $promoID; ?>">
            <div class="form-group">
                <label for="promo-name">Nama Promo</label>
                <input type="text" id="promo-name" name="promo_name" value="<?php echo htmlspecialchars($promo['nama_menu']); ?>">
            </div>

            <div class="form-group">
                <label for="promo-description">Deskripsi</label>
                <textarea id="promo-description" name="promo_description"><?php echo htmlspecialchars($promo['deskripsi']); ?></textarea>
            </div>

            <div class="menu-group">
                <label>Menu yang akan dipromosikan</label>
                <div class="menu-selection">
                <div class="selected-items">
                    <h3>Selected</h3>
                    <div class="wrapper-selected-items">
                                <?php foreach ($menuItems as $item): ?>
                                <div class="menu-item">
                                    <?php if (!empty($item['image'])) { 
                                // Menyusun base URL dan mengonversi path gambar di database
                                $baseURL = "https://cobaadmin.canngopi.com"; // Base URL domain Anda
                                $imagePath = str_replace('/admin', '', $item['image']); // Menghapus '/admin' agar URL bisa mengaksesnya
                                
                                // Menampilkan gambar
                                ?>
                                <img src="<?php echo htmlspecialchars($baseURL . $imagePath); ?>" alt="<?php echo htmlspecialchars($item['nama_menu']); ?>">
                                <?php } else { ?>
                                <img alt="Tidak ada gambar"> 
                                <?php } ?>
                                <span><?php echo htmlspecialchars($item['nama_menu']); ?></span>
                                <span class="price">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                </div>
            </div>

            <div class="form-group">
                <label for="discount-amount">Pengurangan Harga</label>
                <input type="number" id="discount-amount" name="discount_amount" value="<?php echo number_format($promo['pengurangan_harga'], 0, ',', '.'); ?>">
            </div>

            <div class="form-group">
                <label for="terms-conditions">S&K</label>
                <textarea id="terms-conditions" name="terms_conditions"><?php echo htmlspecialchars($promo['syarat']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="masa_berlaku">Masa Berlaku</label>
                <input type="date" id="masa_berlaku" name="masa_berlaku" value="<?php echo date('Y-m-d', strtotime($promo['masa_berlaku'])); ?>">
            </div>

            <div class="form-actions">
                <button type="submit" class="save-button">Update Promo</button>
            </div>
        </form>
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
    </script>
</body>

</html>
