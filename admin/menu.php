<?php
session_start();
// Include the database connection
include('../connection.php');

if (!isset($_SESSION['UserID']) || empty($_SESSION['UserID'])) {
    // Redirect to index.php if no session is found
    header("Location: index.php");
    exit();
}

// Check if MenuID is set for deletion
if (isset($_GET['deleteMenuID'])) {
    $MenuID = $_GET['deleteMenuID'];

    // Prepare and execute the delete query
    $sql = "DELETE FROM menu WHERE MenuID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $MenuID); // "i" for integer
    $stmt->execute();

    // Redirect back with a success message
    header("Location: Menu.php?message=Menu deleted successfully");
    exit();
}

// Initialize variables for filtering
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : 'All';

// Query to select menu items with optional filters
$sql = "SELECT m.*, k.kategori 
        FROM menu m 
        LEFT JOIN kategori k ON m.KategoriID = k.KategoriID 
        WHERE m.deleted_at IS NULL";

// Filter based on search input
if (!empty($search)) {
    $sql .= " AND m.nama_menu LIKE ?";
}

// Filter based on category
if ($category != 'All') {
    $sql .= " AND m.KategoriID = ?";
}

// Prepare the SQL query
$stmt = $conn->prepare($sql);

// Bind parameters
if (!empty($search)) {
    $search_param = "%" . $search . "%";
    $stmt->bind_param('s', $search_param); // "s" for string
} 

if ($category != 'All') {
    $stmt->bind_param('i', $category); // "i" for integer
}

$stmt->execute();
$result = $stmt->get_result();
$menus = $result->fetch_all(MYSQLI_ASSOC);

// Query for categories
$category_query = "SELECT KategoriID, kategori FROM kategori WHERE deleted_at IS NULL";
$category_result = $conn->query($category_query);
$categories = $category_result->fetch_all(MYSQLI_ASSOC);

// Process form if POST method is used (for updating menu items)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $MenuID = $_POST['MenuID']; // MenuID to update
    $nama_menu = $_POST['nama_menu'];
    $KategoriID = $_POST['KategoriID'];
    $deskripsi = $_POST['deskripsi'];
    $harga = $_POST['harga'];

    // Check if a file is uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $image = file_get_contents($_FILES['image']['tmp_name']); // Read file as binary
        $image_query = ", image = ?";
    } else {
        $image_query = ""; // If no new image, don't update the image column
    }

    // Prepare update query
    $sqlu = "UPDATE menu SET 
                nama_menu = ?, 
                KategoriID = ?, 
                deskripsi = ?, 
                harga = ?, 
                updated_at = NOW() 
                $image_query 
            WHERE MenuID = ?";

    // Prepare the statement
    $stmt = $conn->prepare($sqlu);

    // Bind parameters
    $stmt->bind_param('ssssd', $nama_menu, $KategoriID, $deskripsi, $harga, $MenuID); // Adjust types accordingly

    // Bind the image parameter if available
    if (!empty($image_query)) {
        $stmt->bind_param('b', $image); // "b" for blob (binary data)
    }

    // Execute the update
    if ($stmt->execute()) {
        echo "Data berhasil diperbarui.";
    } else {
        echo "Terjadi kesalahan saat memperbarui data.";
    }
}

// Close the database connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Menu Page</title>

  <!-- box icons -->
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
    rel="stylesheet">

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
      justify-content: space-between;
      align-items: center;
    }

    .header h2 {
      margin: 0;
      color: black;
      font-size: 26px;
    }

    .header button {
      display: flex;
      background-color: #aa1919;
      border: 2px solid #aa1919;
      color: white;
      padding: 10px 20px;
      border-radius: 10px;
      cursor: pointer;
      font-size: 16px;
      margin-right: 10px;
    }

    .header i {
      font-size: 20px;
    }

    .header button:last-child {
      background-color: white;
      color: #aa1919;
    }

    .search-bar {
      display: flex;
      justify-content: space-between;
      margin-left: -40px;
      margin-bottom: 20px;
      flex-direction: row;
      width: 100%;
    }

    .search {
      display: flex;
      flex-direction: row;
      align-items: center;
      /* Center items vertically */
    }

    .filter {
      display: flex;
      flex-direction: column;
      margin-right: 20px;
      /* Adjust spacing between filter boxes */
    }

    .search label {
      margin-bottom: 5px;
      /* Space between label and input/select */
      font-size: 14px;
      color: #757575;
    }

    .search input,
    .search select {
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      margin-right: 75px;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      width: 150px;
    }

    .item-table {
      width: 100%;
      border-collapse: collapse;
    }

    .item-table th,
    .item-table td {
      font-size: small;
      padding: 10px;
      text-align: left;
    }

    .item-table i {
      font-size: 18px;
    }

    .item-table th {
      background-color: #f8f8f8;
      color: #333;
      border-bottom: 1px solid #ddd;
    }

    .item-table tr {
      border-bottom: 1px solid #ddd;
    }

    .item-table tr:hover {
      background-color: #f1f1f1;
    }

    .item-table img {
      width: 50px;
      height: 50px;
      border-radius: 8px;
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

    .form-group input[type="text"],
    .form-group select,
    .form-group textarea {
      flex: 1;
      padding: 12px;
      font-size: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      box-sizing: border-box;
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

    textarea {
      height: 60px;
      resize: none;
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

    .popup-edit {
      background-color: #fff;
      border-radius: 12px;
      padding: 30px;
      text-align: center;
      width: 600px;
      height: 420px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      display: flex;
      flex-direction: column;
      align-items: center;
      z-index: 1000;
    }

    .popup-edit h2 {
      font-size: 24px;
      color: #990000;
      margin-bottom: 20px;
    }

    .popup-edit button1 {
      margin: 25px 30px;
      padding: 10px 30px;
      background-color: #b27878;
      color: #fff;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
    }

    .popup-edit button1:hover {
      background-color: #d90101;
      color: #fff;
    }

    .popup-edit button2 {
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

    .popup-edit button2:hover {
      background-color: #d90101;
    }

    .popup-hapus {
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
      z-index: 1000;
    }

    .popup-hapus h2 {
      font-size: 20px;
      color: #990000;
      margin-top: 30px;
      margin-bottom: 60px;
    }

    .popup-hapus button1 {
      margin: 25px 30px;
      padding: 10px 30px;
      background-color: #b27878;
      color: #fff;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
    }

    .popup-hapus button1:hover {
      background-color: #d90101;
      color: #fff;
    }

    .popup-hapus button2 {
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

    .popup-hapus button2:hover {
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

    .popup.show {
      visibility: visible;
      opacity: 1;
    }
  </style>
</head>

<body>
  <!-- Side bar -->
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
  <!-- Side bar end -->

  <!-- Content -->
  <div class="content">
    <!-- header -->
    <div class="header">
      <h2>Menu</h2>
      <div>
        <button id="tambah_item">
          Tambah item <i class="bx bx-plus"></i>
        </button>
      </div>
    </div>
    <div class="search-bar">
    <ul>
    <form id="searchForm" action="menu.php" method="GET">
        <div class="search">
            <!-- Input Search -->
            <div class="filter">
                <label for="search-input">Search</label>
                <input type="text" id="search-input" name="search" placeholder="Cari di sini" value="<?php echo htmlspecialchars($search); ?>" oninput="submitForm()" />
            </div>

            <!-- Dropdown Category -->
            <div class="filter">
                <label for="category-select">Category</label>
                <select id="category-select" name="category" onchange="submitForm()">
                    <option value="All">All</option>
                    <?php foreach ($categories as $categoryItem): ?>
                        <option value="<?php echo $categoryItem['KategoriID']; ?>" <?php if ($categoryItem['KategoriID'] == $category) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($categoryItem['kategori']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>
    </ul>
</div>


    <!-- header end -->

    <!-- Table -->
    <table class="item-table">
      <thead>
        <tr>
          <th>Image</th>
          <th>Kode</th>
          <th>Name</th>
          <th>Category</th>
          <th>Deskripsi</th>
          <th>Price</th>
          <th></th> <!--nambal-->
          <th></th> <!--nambal-->
        </tr>
      </thead>
      <tbody>
  <?php foreach ($menus as $menu): ?>
    <tr>
      <td>
        <?php
        if (!empty($menu['image'])) {
        // Path gambar sudah berada di dalam /admin/assets/menu/ di server
        $baseURL = "https://cobaadmin.canngopi.com"; // Domain utama
        
        // Karena gambar berada di dalam /admin/assets/menu/, kita hilangkan /admin dari path
        $relativePath = str_replace('/admin', '', $menu['image']);
        
        // Menambahkan base URL dengan path relatif
        $imagePath = $baseURL . $relativePath;
        
        // Menampilkan gambar
        echo "<img src='$imagePath' alt='Menu Image' width='100' height='100'>";
        } else {
        echo "No Image";
        }
        ?>
      </td>
      <td><?php echo htmlspecialchars($menu['MenuID']); ?></td>
      <td><?php echo htmlspecialchars($menu['nama_menu']); ?></td>
      <td><?php echo htmlspecialchars($menu['kategori']); ?></td>
      <td><?php echo htmlspecialchars($menu['deskripsi']); ?></td>
      <td><?php echo htmlspecialchars($menu['harga']); ?></td>
      <td>
        <!-- Link for editing the menu item -->
        <a href="editMenu.php?MenuID=<?php echo htmlspecialchars($menu['MenuID']); ?>" style="color: black; text-decoration: none;">
          <i class="bx bxs-edit"></i>
        </a>
      </td>
      <td class="button-samping" onclick="showPopup('<?php echo htmlspecialchars($menu['MenuID']); ?>')">
        <!-- Icon for deleting the menu item -->
        <i class="bx bxs-trash"></i>
      </td>
    </tr>
  <?php endforeach; ?>
</tbody>

    </table>
    <!-- Table end -->
  </div>
  <!-- Content end -->

  <!-- Popup -->
  <!-- Edit -->

  
  <div id="popup1" class="popup">
    <div class="popup-edit">
      <h2>Edit Item</h2>
      <form id="menu-form" action="menu.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="MenuID" value="<?php echo $MenuID; ?>" /> <!-- Menyimpan MenuID untuk update -->

        <div class="form-group">
          <label for="image">Ganti image</label>
          <input type="file" class="file" name="image" />
        </div>

        <div class="form-group">
          <label for="item-name">Nama</label>
          <input type="text" id="item-name" name="nama_menu" value="<?php echo $menu['nama_menu']; ?>"
            placeholder="Tambahkan nama item" />
        </div>

        <!-- Category Dropdown -->
        <div class="form-group">
          <label for="item-category">Category</label>
          <select id="KategoriID" name="Category" required>
            <option value="" disabled selected>Pilih kategori</option>
            <?php
            foreach ($categories as $category) {
              echo "<option value='" . $category['KategoriID'] . "'>" . $category['kategori'] . "</option>";
            }
            ?>
          </select>
        </div>

        <div class="form-group">
          <label for="item-deskripsi">Deskripsi</label>
          <textarea id="item-deskripsi" name="deskripsi"
            placeholder="Tambahkan Deskripsi item"><?php echo $menu['deskripsi']; ?></textarea>
        </div>

        <div class="form-group">
          <label for="item-price">Price</label>
          <input type="text" id="item-price" name="harga" value="<?php echo $menu['harga']; ?>"
            placeholder="Masukan harga item" />
        </div>

        <div class="edit">
          <button1 onclick="closePopup1()">Kembali</button1>
          <button2 type="sumbit" onclick="showPopup3()">Tambahkan</button2>
        </div>
      </form>
    </div>
  </div>


  <!-- Hapus -->
  <div id="popup" class="popup">
    <div class="popup-hapus">
      <h2>Yakin untuk membuang item ini?</h2>
      <div class="hapus">
        <button1 onclick="confirmDeletion()">Ya</button1>
        <button2 onclick="closePopup()">Tidak</button2>
      </div>
    </div>
  </div>

  <!-- Berhasil -->
  <div id="popup3" class="popup">
    <div class="popup-berhasil">
      <h2>Berhasil</h2>
      <img src="image/success.png" alt="Success">
      <button2 onclick="closePopup3()">Close</button>
    </div>
  </div>

  <!-- Popup end -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(document).ready(function () {
            $('#search-input, #category-select').on('input change', function () {
                performSearch();
            });

            function performSearch() {
                const search = encodeURIComponent($('#search-input').val());
                const category = $('#category-select').val();

                $.ajax({
                    url: 'menu.php', // Use the same page
                    type: 'GET',
                    data: {
                        search: search,
                        category: category
                    },
                    dataType: 'html',
                    success: function (data) {
                        const tableBody = $('table tbody');
                        tableBody.empty(); // Clear existing rows

                        const menus = $(data).find('table tbody').html(); // Extract the new table rows
                        tableBody.append(menus);
                    },
                    error: function (xhr, status, error) {
                        console.error('Error fetching data:', status, error);
                    }
                });
            }
            $('#tambah_item').on('click', function () {
                window.location.href = "tambah_item.php";
            });
        });

    function showPopup1() {
      document.getElementById("popup1").classList.add("show");
    }

    function closePopup1() {
      document.getElementById("popup1").classList.remove("show");
    }
    let deleteMenuID;      
    
        function showPopup(MenuID) {
            deleteMenuID = MenuID;
            document.getElementById("popup").classList.add("show");
        }

        function closePopup() {
            document.getElementById("popup").classList.remove("show");
        }

    function showPopup3() {
      document.getElementById("popup3").classList.add("show");
    } 

    function confirmDeletion() {
          window.location.href = 'menu.php?deleteMenuID=' + encodeURIComponent(deleteMenuID);
      }

    function closePopup3() {
      window.location.href = "menu.php";
    }

  </script>
</body>

</html>