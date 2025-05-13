<?php
// Start session
session_start();

// Include the connection.php file to connect to the database
include('../connection.php');

if (!isset($_SESSION['UserID']) || empty($_SESSION['UserID'])) {
    // Redirect to index.php if no session is found
    header("Location: index.php");
    exit();
}

// Fetch all menu items from the Menu table, including images
$query = "SELECT MenuID, nama_menu, harga, image FROM menu WHERE deleted_at IS NULL";
$result = $conn->query($query);

if (!$result) {
    die("Error fetching menu items: " . $conn->error);
}

$menu_items = $result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture form inputs and sanitize
    $nama_promo = htmlspecialchars(trim($_POST['nama_promo'] ?? ''));
    $deskripsi = htmlspecialchars(trim($_POST['deskripsi'] ?? ''));
    $menu_id = htmlspecialchars(trim($_POST['selected_menu_ids'] ?? '')); 
    $pengurangan_harga = htmlspecialchars(trim($_POST['pengurangan_harga'] ?? ''));
    $syarat = htmlspecialchars(trim($_POST['syarat'] ?? ''));
    $masa_berlaku = htmlspecialchars(trim($_POST['masa_berlaku'] ?? '')); 

    // Validate required fields
    if (empty($nama_promo) || empty($deskripsi) || empty($menu_id) || empty($pengurangan_harga) || empty($syarat) || empty($masa_berlaku)) {
        echo "<div class='error'>All fields are required!</div>";
        return;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Prepare and execute insert statement for Promo
        $query = "INSERT INTO promo (nama_menu, deskripsi, pengurangan_harga, syarat, masa_berlaku, created_at, updated_at, deleted_at) 
                  VALUES (?, ?, ?, ?, ?, NOW(), NOW(), NULL)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssdss", $nama_promo, $deskripsi, $pengurangan_harga, $syarat, $masa_berlaku);

        if ($stmt->execute()) {
            // Get the last inserted PromoID
            $promo_id = $conn->insert_id;

            // Split the menu IDs and insert them into the menu_promo table
            $menu_ids = explode(',', $menu_id); // Assuming menu IDs are stored as a comma-separated string

            $promo_menu_query = "INSERT INTO menu_promo (PromoID, MenuID) VALUES (?, ?)";
            $promo_menu_stmt = $conn->prepare($promo_menu_query);

            foreach ($menu_ids as $menu_id) {
                // Validate menu_id
                if (!is_numeric($menu_id)) {
                    throw new Exception("Invalid Menu ID: $menu_id");
                }

                // Bind parameters for each menu ID
                $promo_menu_stmt->bind_param("ii", $promo_id, $menu_id);
                $promo_menu_stmt->execute(); // Insert each menu ID
            }

            // Commit the transaction
            $conn->commit();

            // Redirect back to the form page with a success query parameter
            header("Location: tambah_promo.php?success=1");
            exit();
        } else {
            throw new Exception("Error inserting promo: " . $stmt->error);
        }
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
    }

    // Close statements
    $stmt->close();
    if (isset($promo_menu_stmt)) {
        $promo_menu_stmt->close();
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
    <title>Add Promo Page</title>

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
            padding-top: 0px;    
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
        .form-group textarea,
        .form-group select {
            flex-grow: 1;
            padding: 10px;
            font-size: 14px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-family: Poppins, sans-serif;
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
            display: block;
            gap: 20px;
            background-color: #F9F9F9;
            border-radius: 10px;
            padding: 15px;
        }

        .menu-items {
            flex: 1;
            display: flex;
            flex-direction: column;
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
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            max-height: auto;
            overflow-y: auto;
            align-items: start;
        }

        .selected-items .menu-item {
            display: flex;
            flex-direction: column;
            width: 125px;
            padding: 5px;
            box-sizing: border-box;
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
            margin-bottom: 10px;
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
            max-width: 100%;
            height: auto;
            max-height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 5px;
        }

        .menu-item h3 {
        margin: 10px 0; /* Adjusts spacing around the item name */
        }

        .menu-item button {
        align-self: stretch; /* Makes all buttons the same width */
        margin-top: auto; /* Pushes the button to the bottom of the item */
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
        
    .selectbut {
        background-color: #4CAF50;
        border: none;
        color: white;
        padding: 10px 20px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        margin: 10px 0;
        cursor: pointer;
        border-radius: 5px;
        transition: background-color 0.3s ease;
    }

    .selectbut:hover {
        background-color: #45a049;
    }

    .selectbut:active {
        background-color: #3e8e41;
    }

    .removebut {
        background-color: #990000;
        border: none;
        color: white;
        padding: 10px 20px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        margin: 10px 0;
        cursor: pointer;
        border-radius: 5px;
        transition: background-color 0.3s ease;
    }

    .remove:hover {
        background-color: #6C0000;
    }

    .removebut:active {
        background-color: #3e8e41;
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
        <h2><i class='bx bx-chevron-left' onclick="showPopupBack()"  style="font-size: 54px;"></i>Tambah Promo</h2>

        <form method="POST" action="tambah_promo.php">
            <div class="form-group">
                <label for="promo-name">Nama Promo</label>
                <input type="text" id="promo-name" name="nama_promo" placeholder="Masukkan nama promo" required>
            </div>

            <div class="form-group">
                <label for="promo-description">Deskripsi</label>
                <textarea id="promo-description" name="deskripsi" placeholder="Tambahkan deskripsi promo" required></textarea>
            </div>

            <div class="menu-group">
                <label>Menu yang akan dipromosikan</label>
                <div class="menu-selection">
                    <div class="menu-items">
                        <h3>Tentukan menu yang ingin diberikan harga promo!</h3>
                        <div class="menu-grid" id="menu-list">
                                <?php foreach ($menu_items as $menu): ?>
                                <div class="menu-item" data-menu-id="<?= $menu['MenuID']; ?>">
                                    <?php if (!empty($menu['image'])) { 
                                // Menyusun base URL dan mengonversi path gambar di database
                                $baseURL = "https://cobaadmin.canngopi.com"; // Base URL domain Anda
                                $imagePath = str_replace('/admin', '', $menu['image']); // Menghapus '/admin' agar URL bisa mengaksesnya
                                
                                // Menampilkan gambar
                                ?>
                                <img src="<?php echo htmlspecialchars($baseURL . $imagePath); ?>" alt="<?php echo htmlspecialchars($menu['nama_menu']); ?>">
                                <?php } else { ?>
                                <img alt="Tidak ada gambar"> 
                                <?php } ?>
                                <span><?= $menu['nama_menu']; ?></span>
                                <span class="price">Rp <?= number_format($menu['harga'], 0, ',', '.'); ?></span>
                                <button type="button" class="selectbut" onclick="selectMenu(<?= $menu['MenuID']; ?>, '<?= addslashes($menu['nama_menu']); ?>', '<?= base64_encode($menu['image']); ?>')">Select</button>
                            </div>
                        <?php endforeach; ?>

                        </div>
                    </div>
                    <div class="selected-items">
                        <h3>Selected</h3>
                        <div class="menu-grid" id="selected-list">
                            <!-- Selected menu items will be displayed here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden input to store selected MenuIDs -->
            <input type="hidden" name="selected_menu_ids" id="selected_menu_ids" value="">

            <div class="form-group">
                <label for="discount-amount">Pengurangan Harga</label>
                <input type="text" id="discount-amount" name="pengurangan_harga" placeholder="Rp." required>
            </div>

            <div class="form-group">
                <label for="terms-conditions">S&K</label>
                <textarea id="terms-conditions" name="syarat" placeholder="Masukkan syarat dan ketentuan bundle" required></textarea>
            </div>

            <div class="form-group">
                <label for="Masa Berlaku">Masa Berlaku</label>
                <input type="date" id="masa_berlaku" name="masa_berlaku" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="save-button">Tambahkan</button>
            </div>
        </form>

      
    <div id="popup" class="popup">
        <div class="popup-content">
            <h2>Berhasil menambahkan promo</h2>
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
        window.onload = function() {
        // Check if URL contains the success parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('success')) {
            // Show the popup if success=1
            document.getElementById('popup').classList.add('show');
        }
    };

    let selectedMenu = [];

    function selectMenu(menuId, menuName, menuImage) {
        const menuData = { id: menuId, name: menuName, image: menuImage };
        
        // Add menu to selected array if it doesn't exist
        if (!selectedMenu.some(item => item.id === menuId)) {
            selectedMenu.push(menuData);
            updateSelectedList();
        }
    }

    function updateSelectedList() {
        const selectedList = document.getElementById('selected-list');
        const menuIdsInput = document.getElementById('selected_menu_ids');

        // Clear current list
        selectedList.innerHTML = '';

        selectedMenu.forEach(menu => {
            const menuItem = document.createElement('div');
            menuItem.classList.add('menu-item');
            menuItem.innerHTML = `
                <img src="data:image/jpeg;base64,${menu.image}" alt="${menu.name}" style="width: 50px; height: 50px;">
                <span>${menu.name}</span>
                <button class="removebut" onclick="removeMenu(${menu.id})">Remove</button>
            `;
            selectedList.appendChild(menuItem);
        });

        // Update hidden input value with selected IDs
        menuIdsInput.value = selectedMenu.map(item => item.id).join(',');
    }

    function removeMenu(menuId) {
        selectedMenu = selectedMenu.filter(item => item.id !== menuId);
        updateSelectedList();
    }


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
