<?php
session_start(); // Start the session

// Include the database connection file
require_once ('../connection.php'); // Include the connection.php file

$updateSuccess = false; // Flag for successful update

if (!isset($_SESSION['UserID']) || empty($_SESSION['UserID'])) {
    // Redirect to index.php if no session is found
    header("Location: index.php");
    exit();
}

// Fetch the menu data to be edited
if (isset($_GET['MenuID'])) {
    $MenuID = $_GET['MenuID'];
    $query = "SELECT * FROM menu WHERE MenuID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $MenuID);
    $stmt->execute();
    $result = $stmt->get_result();
    $menu = $result->fetch_assoc();

    if (!$menu) {
        echo "Menu not found!";
        exit;
    }

    // Handle the form submission via POST
    if (isset($_POST['edit'])) {
        $nama_menu = $_POST['nama_menu'];
        $KategoriID = $_POST['KategoriID'];
        $deskripsi = $_POST['deskripsi'];
        $harga = $_POST['harga'];

        // Handle the image if uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $image = file_get_contents($_FILES['image']['tmp_name']);
            $image_query = ", image = ?";
        } else {
            $image_query = ""; // No image update
        }

        // Update query
        $sql = "UPDATE menu SET 
                  nama_menu = ?, 
                  KategoriID = ?, 
                  deskripsi = ?, 
                  harga = ?, 
                  updated_at = NOW() 
                  $image_query 
              WHERE MenuID = ?";

        // Prepare the query
        $stmt = $conn->prepare($sql);

        // Bind parameters
        $stmt->bind_param("siisi", $nama_menu, $KategoriID, $deskripsi, $harga, $MenuID);

        // Bind image if necessary
        if (!empty($image_query)) {
            $stmt->bind_param("b", $image); // Bind the image as blob (binary)
        }

        // Execute the query
        if ($stmt->execute()) {
            $updateSuccess = true; // Set flag to true when update is successful
        } else {
            echo "Error updating data!";
        }
    }
} else {
    echo "Invalid MenuID!";
    exit;
}

// Fetch categories from the Kategori table
$category_query = "SELECT KategoriID, kategori FROM kategori WHERE deleted_at IS NULL";
$categories_result = $conn->query($category_query);
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Close the database connection
$conn->close();
?>





<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tambah item</title>
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

        .menu-container {
            padding: 30px;
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
        }

        .header i {
            font-size: 28px;
            margin-right: 15px;
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
            font-size: 18px;
        }

        .item-category {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .item-category label {
            width: 120px;
            margin-right: 15px;
            font-size: 18px;
        }

        .item-category select {
            /* flex: 1; */
            width: 325px;
            padding: 12px;
            font-size: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .tambah-category {
            display: flex;
            justify-content: flex-end;
            /* margin-top: 25px; */
        }

        .tambah-category {
            margin-left: 10px;
            padding: 10px;
            background-color: #e2e2e2;
            color: black;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
        }

        .tambah-category:hover {
            background-color: #ccc;
        }

        .image {
            height: 42px;
            width: 470px;
            display: flex;
            padding: 1px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .file {
            flex: 1;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 8px;
            font-size: 14px;
        }

        .fie::-webkit-file-upload-button {
            background: #990000;
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
        }

        .form-group input[type="text"], [type="number"],
        .form-group select,
        .form-group textarea {
            flex: 1;
            padding: 12px;
            font-size: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: Poppins, sans-serif;
        }

        #fileInput {
            border: #d90101;
            margin-right: 10px;
            padding: 10px;
            border-radius: 8px 0px 0px 8px;
            /* background-color: #E2E2E2; */
            /* color: black; */
        }

        .form-group ul {
            flex: 1;
            padding: 0px 0px 0px 0px;
            align-items: center;
            display: flex;
            font-size: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .form-group-category {
            display: flex;
            align-items: center;
        }

        textarea {
            height: 60px;
            resize: none;
        }

        .button-container {
            display: flex;
            justify-content: center;
            margin-top: 25px;
        }

        .button-container button1 {
            padding: 10px 20px;
            background-color: #b27878;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            margin-right: 20px;
        }

        .button-container button1:hover {
            background-color: #d90101;
            color: #fff;
        }

        .button-container button2 {
            padding: 10px 20px;
            background-color: #990000;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
        }

        .button-container button2:hover {
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
        
        .popupitem {
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

        .popupitem img {
            width: 100px;
            margin-bottom: 10px;
        }

        .popupitem.show {
            visibility: visible;
            opacity: 1;
        }

        .popup-back {
            background-color: #fff;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            width: 400px;
            height: 200px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .popup-back h2 {
            font-size: 20px;
            color: #990000;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .popup-back span {
            color: #990000;
            margin: 20px;
        }

        .back {
            /* padding: 20px; */
            margin: 20px;
        }

        .popup-back button1 {
            margin: 30px 30px;
            padding: 10px 30px;
            background-color: #b27878;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }

        .popup-back button1:hover {
            background-color: #d90101;
            color: #fff;
        }

        .popup-back button2 {
            margin: 0 30px;
            margin-bottom: 0;
            padding: 10px 20px;
            background-color: #990000;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }

        .popup-back button2:hover {
            background-color: #d90101;
        }

        .button2 {
            margin: 0 30px;
            margin-bottom: 0;
            padding: 10px 20px;
            background-color: #990000;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }

        .button2:hover {
            background-color: #d90101;
        }

        .popup-berhasil {
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

        .popup-berhasil h2 {
            font-size: 24px;
            color: #990000;
            margin-bottom: 20px;
        }

        .popup-berhasil img {
            width: 100px;
            margin-bottom: 20px;
        }

        .popup-berhasil button2 {
            padding: 10px 20px;
            background-color: #990000;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }

        .popup-berhasil button2:hover {
            background-color: #d90101;
        }

        .popup3 {
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
        }

        .popup-category {
            background-color: #fff;
            border-radius: 12px;
            padding: 30px;
            /* text-align: center; */
            width: 400px;
            /* height: 200px; */
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            /* align-items: center; */
        }

        .head {
            margin-bottom: 20px;
        }

        .category {
            display: flex;
            flex-direction: column;
        }

        .popup-category i {
            font-size: 18px;
            margin-right: 15px;
        }

        .popup-category h3 {
            font-size: 10px;
        }

        .category input[type="text"] {
            flex: 1;
            padding: 12px;
            font-size: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .category button1 {
            /* width: 150px; */
            display: flex;
            flex-direction: column;
            padding: 10px 10px;
            background-color: #990000;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
        }


        .popup.show {
            visibility: visible;
            opacity: 1;
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
        <a href="menu.php" class="active">
            <i class='bx bxs-food-menu'></i> Menu
        </a>
        <a href="promo.php">
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

    <div class="menu-container">
        <div class="header">
            <h3><i class='bx bx-arrow-back' onclick="showPopup1()"></i> Edit Item</h3>
        </div>
        <?php 
        if (isset($menu) && is_array($menu)) {
            // kode untuk mengakses nilai array $menu
            ?>
        <form id="menu-form" action="editMenu.php?MenuID=<?php echo $MenuID; ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="MenuID" value="<?php echo $menu['MenuID']; ?>">

            <div class="form-group">
                <label for="image">Image</label>
                <input type="file" name="image" id="image" class="file" />
            </div>

            <div class="form-group">
                <label for="nama_menu">Nama</label>
                <input type="text" name="nama_menu" id="nama_menu" value="<?php echo $menu['nama_menu']; ?>" placeholder="Nama item" />
            </div>

            <!-- Category Dropdown -->
            <div class="form-group">
                <label for="KategoriID">Category</label>
                <select id="KategoriID" name="KategoriID" required>
                    <option value="" disabled selected>Pilih kategori</option>
                    <?php foreach ($categories as $category) { ?>
                        <option value="<?php echo $category['KategoriID']; ?>" <?php if ($menu['KategoriID'] == $category['KategoriID']) { echo 'selected'; } ?>><?php echo $category['kategori']; ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-group">
                <label for="item-deskripsi">Deskripsi</label>
                <textarea name="deskripsi" id="item-deskripsi" placeholder="Deskripsi item"><?php echo $menu['deskripsi']; ?></textarea>
            </div>

            <div class="form-group">
                <label for="item_price">Price</label>
                <input type="text" name="harga" value="<?php echo $menu['harga']; ?>" placeholder="Harga item" />
            </div>

            <div class="button-container">
                <button class="button2" type="submit" name="edit" class="save-button">Edit</button>
            </div>
        </form>

        <?php
        } else {
            // kode untuk menangani kasus jika $menu bukan array
            echo "Data menu tidak ditemukan.";
        } 
        ?>
    </div>

    <!-- Popup -->
    <!-- Kembali -->
    <div id="popup1" class="popup">
        <div class="popup-back">
            <h2>Yakin untuk kembali?</h2>
            <span>Perubahan yang anda buat <br>BELUM TERSIMPAN</span>
            <div class="back">
                <button1 onclick="goBack()">Ya</button1>
                <button2 onclick="closePopup1()">Tidak</button>
            </div>
        </div>
    </div>

    <!-- Berhasil -->
    <div id="successPopup" class="popupitem">
        <div class="popup-back">
            <h2>Item berhasil diperbarui!</h2>
            <img src="image/success.png" alt="Success">
            <button class="button2" onclick="closePopup()">Tutup</button>
        </div>
    </div>

    <script>
        // Show the popup if the update was successful
        window.onload = function() {
            const updateSuccess = <?php echo json_encode($updateSuccess); ?>;
            if (updateSuccess) {
                document.getElementById("successPopup").classList.add("show");
            }
        }

        // Function to close the popup
        function closePopup() {
            document.getElementById("successPopup").classList.remove("show");
            window.location.href = "menu.php"; // Optional: redirect to another page
        }

        function goBack() {
            window.location.href = "menu.php";
        }


        function showPopup1() {
            document.getElementById("popup1").classList.add("show");
        }

        function closePopup1() {
            document.getElementById("popup1").classList.remove("show");
        }

        function showPopup3() {
            document.getElementById("popup3").classList.add("show");
        }

        function closePopup3() {

            document.getElementById("popup3").classList.remove("show");
        }

        // function setInputValue() {
        // const popupInput = document.getElementById("popupInput");
        // const resultInput = document.getElementById("resultInput");
        // resultInput.value = popupInput.value;
        // document.getElementById("popup3").classList.value("show");
        // }
    </script>
</body>

</html>