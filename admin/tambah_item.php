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

// Check if form is submitted
$isSubmitted = false;
$categoryCreated = false; // Add this line

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_category'])) {
        // Create new category
        $new_category = $_POST['new_category'];
        $created_at = date('Y-m-d H:i:s');

        $sql = "INSERT INTO kategori (kategori, created_at) VALUES (?, ?)";
        $stmt = $conn->prepare($sql); // Menggunakan $conn
        $stmt->bind_param("ss", $new_category, $created_at); // Bind parameter tipe string (s)

        if ($stmt->execute()) {
            $categoryCreated = true;
        } else {
            echo "Error adding category: " . $stmt->error;
        }
    } else {
        // Get form data
        $kategori_id = $_POST['Category'];
        $nama_menu = $_POST['nama_menu'];
        $deskripsi = $_POST['deskripsi'];
        $harga = $_POST['harga'];
        $created_at = date('Y-m-d H:i:s');

        // Handle file upload (Image)
        $image_path = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['foto']['tmp_name'];
            $file_name = $_FILES['foto']['name'];

            // Tentukan folder target di luar folder PHP yang dijalankan
            $relative_target_dir = "/admin/assets/menu/";

            // Folder absolut untuk penyimpanan (gunakan path di luar folder PHP yang berjalan)
            $root_path = dirname(__DIR__);
            $absolute_target_dir = $root_path . $relative_target_dir;

            // Periksa apakah folder target ada, jika tidak, buat foldernya
            if (!is_dir($absolute_target_dir)) {
                mkdir($absolute_target_dir, 0777, true);
            }

            // Generate unique name to prevent overwriting
            $unique_name = uniqid() . '-' . basename($file_name);
            $target_file = $absolute_target_dir . $unique_name;

            // Move the uploaded file to the target directory
            if (move_uploaded_file($file_tmp_name, $target_file)) {
                $image_path = $relative_target_dir . $unique_name; // Save relative path to database
            } else {
                echo "Error saving file.";
                exit;
            }
        } else {
            echo "Error uploading file.";
            exit;
        }

        // Prepare SQL query to insert data into Menu table
        $sql = "INSERT INTO menu (KategoriID, nama_menu, image, deskripsi, harga, created_at)
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql); // Menggunakan $conn

        // Bind parameters
        $stmt->bind_param("isssds", $kategori_id, $nama_menu, $image_path, $deskripsi, $harga, $created_at); 
        // Parameter:
        // i -> integer, s -> string, d -> double (harga)

        // Execute the query
        if ($stmt->execute()) {
            $isSubmitted = true; // Set flag to true to trigger popup
        } else {
            // Error message
            echo "<div style='position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 400px; padding: 40px; background-color: #f0f0f0; border: 3px solid #ccc; border-radius: 10px; text-align: center; z-index: 9999;'> 
                  Error adding menu item: " . $stmt->error . 
                 "</div>";
        }
    }
}

// Fetch categories from the Kategori table
$category_query = "SELECT KategoriID, kategori FROM kategori WHERE deleted_at IS NULL"; // Fetch categories that are not deleted
$categories = $conn->query($category_query)->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tambah item</title>
  </head>
  <!-- box icons -->
  <link
        href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
        rel="stylesheet"
      />

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
          padding:30px;          
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
        .item-category input[type="text"] {
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
        .foto {
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

        .form-group input[type="text"], 
        .form-group input[type="number"],
        .form-group-category select,
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

        .form-group-category{
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

        .button-container button {
          padding: 10px 20px;
          background-color: #990000;
          color: #fff;
          border: none;
          border-radius: 8px;
          cursor: pointer;
          font-size: 18px;
        }

        .button-container button:hover {
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

        .popup-content h3 {
            font-size: 20px;
            color: #990000;
            margin-bottom: 30px;
        }

        .popup-content img {
            width: 100px;
            margin-bottom: 20px;
        }

        .popup-content button1 {
            padding: 10px 20px;
            background-color: #990000;
            color: #fff;
            border: none;
            border-radius: 21px;
            cursor: pointer;
            font-size: 16px;
        }

        .popup-content button:hover {
            background-color: #d90101;
        }
      
      .popup.show {
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
      .button1 {
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
        <h3><i class="bx bx-arrow-back" onclick="showPopup1()"></i> Tambah Item</h3>
      </div>

      <!-- Updated Form with POST action -->
      <form id="menu-form" action="tambah_item.php" method="POST" enctype="multipart/form-data">
      <!-- Image Upload -->
      <div class="form-group">
        <label for="foto">Upload foto</label>
        <input type="file" id="foto" name="foto" class="file" required />
      </div>

      <!-- Nama Menu -->
      <div class="form-group">
        <label for="nama_menu">Nama</label>
        <input type="text" id="nama_menu" name="nama_menu" placeholder="Tambahkan nama item" required />
      </div>

      <!-- Category Dropdown -->
      <div class="item-category">
        <div class="form-group-category">
          <label for="Category">Category</label>
          <select id="KategoriID" name="Category" required>
            <option value="" disabled selected>Pilih kategori</option>
            <?php
            foreach ($categories as $category) {
              echo "<option value='" . $category['KategoriID'] . "'>" . $category['kategori'] . "</option>";
            }
            ?>
          </select>
          <button type="button" class="tambah-category" onclick="showPopup3()">Buat category baru</button>
        </div>
      </div>

      <!-- Deskripsi -->
      <div class="form-group">
        <label for="deskripsi">Deskripsi</label>
        <textarea id="deskripsi" name="deskripsi" placeholder="Tambahkan Deskripsi item" required></textarea>
      </div>

      <!-- Price -->
      <div class="form-group">
        <label for="item-price">Price</label>
        <input type="number" id="harga" name="harga" placeholder="Masukan harga item" required />
      </div>

      <!-- Submit Button -->
      <div class="button-container">
        <button type="submit" class="save-button">Tambahkan</button>
      </div>
    </form>
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
    <div id="popup2" class="popup">
      <div class="popup-berhasil">
        <h2>Item berhasil ditambahkan</h2>
        <img src="image/success.png" alt="Success">
        <button2 onclick="goBack()">Close</button>
      </div>
    </div>

    <!-- Category -->
    <div id="popup3" class="popup">
        <div class="popup-category">
            <div class="head">
                <a><i class='bx bx-arrow-back' onclick="closePopup3()"></i> Nama Category</a>
            </div>
            <form id="create-category-form" method="POST">
                <div class="category">
                    <input type="text" name="new_category" placeholder="Masukkan nama category baru" required>
                    <button type="submit" class="button1" name="create_category">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="popup4" class="popup">
        <div class="popup-content">
            <h2>Berhasil menambahkan Kategori</h2>
            <img src="image/success.png" alt="Success">
            <button class="button1" onclick="closePopup()">Close</button>
        </div>
    </div>

    <script>
      function goBack() {
        window.location.href = "menu.php";
      }

      function showPopup1() {
        document.getElementById("popup1").classList.add("show");
      }

      function closePopup1() {
        document.getElementById("popup1").classList.remove("show");
      }

      function showPopup2() {
        document.getElementById("popup2").classList.add("show");
      }

      function closePopup2() {
        document.getElementById("popup2").classList.remove("show");
      }

      function showPopup3() {
        document.getElementById("popup3").classList.add("show");
      }

      function closePopup3() {
      
        document.getElementById("popup3").classList.remove("show");
      }

      function closePopup() {
          document.getElementById("popup4").classList.remove("show"); // Make sure you're targeting the right popup
      }

        window.onload = function() {
    if (<?php echo json_encode($isSubmitted); ?>) {
        showPopup2();
    }
    if (<?php echo json_encode($categoryCreated); ?>) {
        document.getElementById("popup4").classList.add("show");
    }
};

    </script>

  </body>
</html>

        