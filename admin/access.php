<?php
session_start();
require_once '../connection.php';

if (!isset($_SESSION['UserID']) || empty($_SESSION['UserID'])) {
    // Redirect to index.php if no session is found
    header("Location: index.php");
    exit();
}

try {
    // Check if UserID is set for deletion
    if (isset($_GET['deleteUserID'])) {
        $userID = $_GET['deleteUserID'];

        // Prepare and execute the delete query
        $sql = "DELETE FROM user WHERE UserID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userID); // "i" untuk integer
        $stmt->execute();

        // Redirect back with a success message
        header("Location: access.php?message=User deleted successfully");
        exit();
    }

    // Fetch all cashiers
    $sql_cashiers = "
    SELECT
        user.UserID,
        user.nama AS nama_kasir,
        shift.Shift AS shift_name,
        kasir.pin AS pin,
        shiftkasir.ShiftID AS shift
    FROM kasir
    JOIN user ON kasir.UserID = user.UserID
    JOIN shiftkasir ON kasir.UserID = shiftkasir.UserID
    JOIN shift ON shiftkasir.ShiftID = shift.ShiftID
    ORDER BY user.nama ASC
    ";
    $stmt_cashiers = $conn->prepare($sql_cashiers);
    $stmt_cashiers->execute();
    $active_cashiers = $stmt_cashiers->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}
?>

  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Access Page</title>

    <!-- box icons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

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
        justify-content: space-between;
        display: flexbox;  
        align-items: center;
      }
    
      #title-page, #subtitle-page {
        display: column;
        text-align: left;
        margin-right: auto;
        margin-bottom: 10px;
    }


    .buat_PIN_baru {
        margin-left: auto;
        transform: translateY(-85px);
        background-color: #aa1919;
        border: 2px solid #aa1919;
        color: white;
        padding: 10px 20px;
        border-radius: 15px;
        cursor: pointer;
        font-size: 16px;
        display: flex;
        align-items: center;
      }

      .buat_PIN_baru i {
        font-size: 20px;
      }
      .buat_PIN_baru button:last-child {
        background-color: white;
        color: #aa1919;
      }

      .table {
        transform: translateY(-35px);
      }
      .item-table {
        width: 100%;
        border-collapse: collapse;
      }

      .item-table th,
      .item-table td {
        font-size: small;
        padding: 15px;
        height: 30px;
        text-align: left;
      }

      .button-samping i {
        font-size: 20px;
        margin-left: -12px;
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

      .button1 {
        margin: 25px 30px;
        padding: 10px 30px;
        background-color: #b27878;
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
      }

      .button1:hover {
        background-color: #d90101;
        color: #fff;
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
    <!-- Side bar end -->

    <!-- Content -->
    <div class="content">
      <div class="header">
        <div>
        <h2 id="title-page">Akses Kasir</h2>
        <h3 id="subtitle-page"> Yang memiliki akses sekarang</h3>
        </div>
        <div><button class="buat_PIN_baru" onclick="window.location.href='buat_kode_akses.php'">Buat PIN baru <i class="bx bx-plus"></i></button></div>
      </div>
      
      <!-- Table 1-->
      <div class="table">
      <table class="item-table">
        <thead>
          <tr>
            <th>Nama Kasir</th>
            <th>Shift</th>
            <th>PIN</th>
            <th>     </th> <!--nambal-->
            <th>     </th> <!--nambal-->
          </tr>
        </thead>
        <tbody>
                      <?php foreach ($active_cashiers as $cashier): ?>
                      <tr>
                          <td><?php echo htmlspecialchars($cashier['nama_kasir']); ?></td>
                          <td><?php echo htmlspecialchars($cashier['shift_name']); ?></td>
                          <td><?php echo htmlspecialchars($cashier['pin']); ?></td>
                          <td class="button-samping" onclick="window.location.href='edit_kode_akses.php?UserID=<?php echo htmlspecialchars($cashier['UserID']); ?>'">
                              <i class="bx bxs-edit"></i>
                          </td>

                          

                      </tr>
                      <?php endforeach; ?>
                  </tbody>

      </table>
      </div>
    </div>
    <!-- Content end -->

    
    
    <!-- Popup -->
      <!-- Edit -->
      <div id="popup" class="popup">
        <div class="popup-hapus">
            <h2>Yakin untuk delete user ini?</h2>
            <div class="hapus">
                <button class="button1" onclick="confirmDeletion()">Ya</button> <!-- Use standard button element -->
                <button class="button2" onclick="closePopup()">Tidak</button> <!-- Use standard button element -->
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

    <script>
      let userIDToDelete;      
    
        function showPopup(userID) {
            userIDToDelete = userID;
            document.getElementById("popup").classList.add("show");
        }

        function closePopup() {
            document.getElementById("popup").classList.remove("show");
        }

        function confirmDeletion() {
            window.location.href = 'access.php?deleteUserID=' + encodeURIComponent(userIDToDelete);
        }

      function closePopup2() {
        document.getElementById("popup2").classList.remove("show");
      }
      function showPopup3() {
        document.getElementById("popup3").classList.add("show");
      }

      function closePopup3() {
        window.location.href = "access.php";
      }

      function closePopup() {
          document.getElementById("popup").classList.remove("show");
      }

      function confirmDeletion() {
          window.location.href = 'access.php?deleteUserID=' + encodeURIComponent(userIDToDelete);
      }
    </script>
  </body>

  </html>