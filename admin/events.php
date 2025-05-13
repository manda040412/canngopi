<?php
session_start();
// Include the connection.php file
include('../connection.php');

if (!isset($_SESSION['UserID']) || empty($_SESSION['UserID'])) {
    // Redirect to index.php if no session is found
    header("Location: index.php");
    exit();
}

// Handle form submission
if (isset($_POST['submit'])) {
    $item_name = $_POST['item-name'];
    $status = 'active';

    // Pastikan file telah di-upload dengan sukses
    if (isset($_FILES['upload-photo']) && $_FILES['upload-photo']['error'] == 0) {
        // Tentukan folder tujuan (relative to your PHP project root)
        $targetDir = dirname(__DIR__) . '/admin/assets/events/'; // Folder penyimpanan file

        // Membuat nama file unik berdasarkan waktu dan nama asli file
        $fileName = time() . '-' . basename($_FILES['upload-photo']['name']);
        $targetFilePath = $targetDir . $fileName;

        // Pastikan folder tujuan ada
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true); // Membuat folder jika belum ada
        }

        // Pindahkan file dari lokasi sementara ke folder tujuan
        if (move_uploaded_file($_FILES['upload-photo']['tmp_name'], $targetFilePath)) {
            // Path relatif untuk disimpan di database
            $relativeFilePath = '/admin/assets/events/' . $fileName;
            
            // Deactivate previous active images
            $sql_update = "UPDATE event SET status='inactive' WHERE status='active'";
            if (!$conn->query($sql_update)) {
                echo "Error updating status: " . $conn->error;
            }

            // Siapkan query SQL untuk memasukkan data ke tabel
            try {
                // Gunakan mysqli untuk koneksi database
                $sql_insert = "INSERT INTO event (nama_bg, image, status, created_at, updated_at) 
                               VALUES (?, ?, ?, NOW(), NOW())";

                // Persiapkan statement SQL
                if ($stmt = $conn->prepare($sql_insert)) {
                    // Bind parameter untuk mencegah SQL injection
                    $stmt->bind_param('sss', $item_name, $relativeFilePath, $status);

                    // Eksekusi query
                    if ($stmt->execute()) {
                        echo "<script>alert('Image uploaded and saved successfully');</script>";
                    } else {
                        echo "<script>alert('Failed to save to database');</script>";
                    }
                } else {
                    echo "<script>alert('Failed to prepare the SQL statement');</script>";
                }
            } catch (Exception $e) {
                // Tangani kesalahan dengan mysqli
                echo "<script>alert('Database error: " . $e->getMessage() . "');</script>";
            }
        } else {
            echo "<script>alert('Failed to upload file to target directory');</script>";
        }
    } else {
        echo "<script>alert('Error uploading image');</script>";
    }
}

// Get current active photo with image
$sql = "SELECT nama_bg, image FROM event WHERE status='active' ORDER BY updated_at DESC LIMIT 1";
$result = $conn->query($sql);
$current_photo = $result->fetch_assoc();

// Retrieve history data
$sql_history = "SELECT nama_bg, image FROM event ORDER BY updated_at DESC";
$result_history = $conn->query($sql_history);
$history_events = $result_history->fetch_all(MYSQLI_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Page</title>

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
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header h3 {
            margin: 0;
            color: black;
        }

        .form-container {
            margin-top: 20px;
            flex-grow: 1;
        }

        .form-group {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .form-group label {
            display: block;
            font-size: 16px;
            margin-right: 10px;
            color: black;
            width: 200px;
        }

        .form-group input[type="text"],
        .form-group input[type="file"] {
            flex-grow: 1;
            padding: 10px;
            font-size: 14px;
            border-radius: 9px;
            border: 1px solid #a8a8a8;
            background-color: #f1f1f1;
        }

        .form-group input[type="file"] {
            padding: 7px;
        }

        .form-separator {
            margin: 30px 0;
            font-size: 18px;
            color: #333;
            text-align: left;
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 50px;
            margin-top: 20px;
        }

        .form-actions button {
            width: 20%;
            padding: 10px 10px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 50px;
            cursor: pointer;
        }

        .form-actions button:first-child {
            background-color: #B27878;
            color: white;
        }

        .form-actions button:last-child {
            background-color: #D90101;
            color: white;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            height: 50%;
            border-radius: 10px;
            overflow: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .modal-header h2 {
            margin: 0;
        }

        .modal-header .close {
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px 0;
            
        }

        .modal-body table {
            width: 100%;
            border-collapse: collapse;
        }

        .modal-body th,
        .modal-body td {
            text-align: left;
            padding: 8px;
        }

        .modal-body th {
            font-weight: bold;
        }

        .modal-body td {
            border-bottom: 1px solid #ddd;
        }

        .modal-body .view-link {
            color: #D90101;
            text-decoration: none;
            font-weight: bold;
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
            visibility: hidden; /* Initial state */
            opacity: 0; /* Initial state */
            transition: visibility 0s, opacity 0.3s ease; /* Smooth transition */
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
        <a href="events.php" class="active">
            <i class='bx bxs-calendar-event'></i> Events
        </a>
        <a href="access.php">                             
            <i class='bx bxs-key'></i> Access
        </a>
        <a href="index.php" class="logout">Logout</a>
    </div>
    <div class="content">
        <div class="header">
            <h3>Ganti background screen customer sesuai dengan event tertentu</h3>
        </div>
        <div class="form-container">
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="current-photo">Foto yang sekarang</label>
                <input type="text" id="current-photo" name="current-photo" value="<?php echo htmlspecialchars($current_photo['nama_bg'] ?? 'No active photo'); ?>" readonly>
            </div>

            <?php if (!empty($current_photo['image'])): ?>
                <div class="form-group">
                    <label>Preview Foto Sekarang:</label>
                    <?php
                    if (!empty($current_photo['image'])) {
                    // Path gambar sudah berada di dalam /admin/assets/events/ di server
                    $baseURL = "https://cobaadmin.canngopi.com"; // Domain utama
                    
                    // Menggunakan str_replace untuk menghilangkan '/admin' dari path gambar
                    $relativePath = str_replace('/admin', '', $current_photo['image']);
                    
                    // Menambahkan base URL dengan path relatif yang sudah diubah
                    $imagePath = $baseURL . $relativePath;
                    
                    // Menampilkan gambar
                    echo "<img src='$imagePath' alt='" . htmlspecialchars($current_photo['nama_bg']) . "' style='max-width: 200px;'>";
                    } else {
                    echo "No Image";
                    }
                    ?>
                </div>
                <?php endif; ?>

            <div class="form-separator">
                Ganti background screen customer sesuai dengan event tertentu
            </div>

            <div class="form-group">
                <label for="item-name">Nama</label>
                <input type="text" id="item-name" name="item-name" placeholder="Tambahkan nama item" required>
            </div>
            <div class="form-group">
                <label for="upload-photo">Upload Foto</label>
                <input type="file" id="upload-photo" name="upload-photo" accept="image/*" required>
            </div>
        </div>
        <div class="form-actions">
            <button type="button" id="historyBtn">History</button>
            <button type="submit" name="submit">Tambahkan</button>
        </div>
    </div>

    <!-- The Modal -->
    <div id="historyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>History ganti foto</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Foto</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($history_events as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['nama_bg']); ?></td>
                        <td>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($event['image']); ?>" alt="<?php echo htmlspecialchars($event['nama_bg']); ?>" style="max-width: 100px;">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="popup" class="popup" style="visibility: hidden; opacity: 0;">
        <div class="popup-content">
            <h2>Berhasil menambahkan background</h2>
            <img src="image/success.png" alt="Success">
            <button onclick="closePopup()">Close</button>
        </div>
    </div>

    <script>
        // Check if popup should be shown
            window.onload = function() {
                <?php if (isset($_SESSION['popup'])): ?>
                    var popup = document.getElementById("popup");
                    popup.style.visibility = "visible";
                    popup.style.opacity = "1";
                    <?php unset($_SESSION['popup']); ?> // Clear the session variable
                <?php endif; ?>
            };

            // Existing closePopup function remains unchanged
            function closePopup() {
                var popup = document.getElementById("popup");
                popup.style.visibility = "hidden";
                popup.style.opacity = "0";
            }

        var modal = document.getElementById("historyModal");

        var btn = document.getElementById("historyBtn");

        var span = document.getElementsByClassName("close")[0];

        btn.onclick = function() {
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>

</html>