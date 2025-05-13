<?php
session_start();

// Include the connection file
include('../connection.php');


// Query to fetch cashier name (modify based on your table structure)
$cashierQuery = "SELECT user.UserID, user.nama 
                 FROM user 
                 JOIN kasir ON user.UserID = kasir.UserID
                 WHERE status = 'active' LIMIT 1";
$cashierResult = $conn->query($cashierQuery);

if ($cashierResult && $cashierResult->num_rows > 0) {
    $cashierData = $cashierResult->fetch_assoc();
    $cashierName = $cashierData['nama'];
    $userID = $cashierData['UserID'];  // Store UserID for later use
}

// Query to fetch the latest processing order for the cashier
$orderQuery = "
    SELECT OrderID, Total_harga, Total_promo
    FROM order_list
    WHERE UserID = ? AND status = 'processing'
    ORDER BY created_at DESC LIMIT 1
";
$orderStmt = $conn->prepare($orderQuery);
$orderStmt->bind_param("i", $userID);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
$orderData = $orderResult->fetch_assoc();

if ($orderData) {
    // Order data found, proceed with fetching items for the specific order
    $orderID = $orderData['OrderID'];
    $totalHarga = $orderData['Total_harga'];
    $totalPromo = $orderData['Total_promo'];

    // Step 2: Retrieve items for the specific OrderID from Order_items table
    $itemsQuery = "
        SELECT oi.Quantity, oi.sub_total, oi.Notes, m.nama_menu, m.image
        FROM order_list ol
        JOIN order_items oi ON ol.OrderID = oi.OrderID
        JOIN menu m ON oi.MenuID = m.MenuID
        WHERE oi.OrderID = ?
    ";
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->bind_param("i", $orderID);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();

    // Check if items are found
    $orderItems = [];
    while ($row = $itemsResult->fetch_assoc()) {
        $orderItems[] = $row;
    }
} else {
    header("Location: index.php?reset=true");
    exit();
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Preview</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">


</head>

<style type="text/css">

*{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Poppins", sans-serif;

}

body {
    display: flex;
    flex-direction: column;
    min-height: 100vh; 
    margin: 0;
    overflow: hidden;
    box-sizing: border-box;

}

/*Bagian Header*/

header{
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(to right, #880000 0%, #9D0000 100%);
        height: 100px;
        width: 100%;
        margin: 0px;
        box-sizing: border-box;
        color: white;
        flex-shrink: 0;
        position: fixed; 
        top: 0; 
        left: 0;
        z-index: 1000;
    }

    .logoHeader{
        position: absolute;
        left: 50%;
        top: 15%;
        transform: translateX(-50%); /* Biar logonya di tengah */
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .custGreetings {
        display: flex;
        flex-direction: column; 
        align-items: flex-start; 
        margin-left: 20px;
        font-size: 16px;
    }

    .namaKasir {
        display: flex;
        flex-direction: column; 
        align-items: flex-end; 
        margin-right: 20px;
        font-size: 16px;
    }

/*Bagian tengah halaman*/

    .MiddleSection{
        display: flex;
        flex-direction: column;
        background-color: whitesmoke;
        box-sizing: border-box;
        height: 732px;
        width: 100%;
        flex-grow: 1;
        padding-top: 30px;        
        padding-bottom: 30px;
        padding-left: 70px;
        padding-right: 70px;
    }

    
    .getOrderID{
        padding-top: 7%;
        padding-bottom: 2vh;
        font-size: 20px;
    }
    .getOrderID h2{
        color: grey;
    }

    .getOrderID id{
        color: #880000;
    }

    .order-list{
        height: calc(50vh - 80px); /* Adjust height based on viewport and subtract header/footer size */
        max-height: 1000px; /* Ensures a maximum height */
        overflow-y: auto;
    }
    .pesanan-harga-wrapper{
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        font-size: 22px;
        padding-bottom: 10px;
        padding-right: 70px;
    }

    .product-1, .product-2, .product-3{
        display: flex;
        flex-direction: row;
        height: 70px;
        width: 100%;
        box-sizing: border-box;
        margin-bottom: 20px;
        padding-right: 50px;


    }


    .order-list img{
        border-radius: 15px;
        margin-right: 30px;
    }

    .product{
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        font-size: 18px;
    }

    .product p{
        padding-bottom: 5px;
        font-weight: 600;
    }

    .order-notes {
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: gray;
    }  
    
    .order-notes p1{
        flex-grow: 1;
    }

    .qty-harga {
        display: flex;
        justify-content: space-between; 
        align-items: center;
        width: 200px; /* Atur jarak qty ke harganya disini */
    }

    .qty-harga p2 {
        text-align: right;
        flex-shrink: 0; 
    }

    .qty-harga p3 {
        text-align: right; 
        flex-grow: 1; /* Ini biar kalo harganya beda-beda posisi qtynya tetep sama */
        white-space: nowrap; 
    }

    .dashed-line {
        border: none; 
        height: 2px; 
        background: linear-gradient(to right, #d5d5d5 55%, transparent 0%);
        background-size: 20px 1px; 
        margin-top: 10px;
        width: 100%;
        margin-bottom: 20px;

    
    }

    .harga-penjualan {
        display: flex;
        flex-direction: column;
        width: 100%;
        align-items: flex-end; /* Align all items to the right */
        padding-right: 65px;
        font-size: 20px;
        margin-bottom: 10px;
    }

    .discount{
        color: rgb(32, 186, 21);
    }

    .total{
        color: #c40606;
        font-weight: 600;
    }

    .sub-Total, .discount, .total {
        display: flex;
        justify-content: space-between;
        width: 480px; /* Ensure the same width across all sections */
        margin-bottom: 5px;
    }

    .sub-Total p1, .discount p3, .total p5 {
        flex-shrink: 0; /* Prevent shrinking */
        text-align: left; /* Align text to the left */
    }

    .sub-Total p2, .discount p4, .total p6 {
        text-align: right; /* Align text to the right */
        flex-grow: 1; /* Allow to grow and take up available space */
        white-space: nowrap; /* Prevent wrapping */
    }
    



/*Masuk ke footer*/

    footer{
        display: flex;
        justify-content: space-between;
        background: linear-gradient(to right, #880000 0%, #9D0000 100%);
        height: 100px;
        width: 100%;
        margin:0px;
        padding: 10px;
        box-sizing: border-box;
        color: white;
        position: fixed; 
        bottom: 0; 
        left: 0;
        z-index: 1000;
    }

/* Dari bawah ini udh semua punya footer*/

    
    .followUs {
        display: flex;
        flex-direction: column; /* Biar teks ama icon atas bawah */
        align-items: flex-start; /* Mepet kiri */
        margin-left: 10px;
    }

   
    .social-icons-container {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 10px; /* Adjust gap */
        flex-wrap: wrap; /* Allow wrapping */
    }


    .social-icons {
        display: flex;
        align-items: center;
        font-size: 14px;
        color: white;
        text-decoration: none;
        gap: 10px; /*Jarak dari teext "Follow US:" ke font*/
        transition: font-size 0.3s ease;
    }

    .social-icons i {
        font-size: 24px; /* Default size for large screens */
        transition: font-size 0.3s ease; /* Smooth size adjustment */
    }

    .availableOn {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        margin-right: 10PX;
    }

    .availableOn h2 {
        margin-bottom: -20px;
    }

    .availLogo-container {
        width:210px;
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: right;
        gap: 20px;
        margin-top: 10px;
        
    }

    .availLogo-container img {
    max-width: 40%;
    height: auto;
    transition: max-width 0.3s ease; /* Smooth size adjustment */
    
    }

    .sizingGofood{
        height: 65px;
        width: auto;
        transform: translateY(3px);
    }

    .sizingGrabfood{
        height: 55px;
        width: auto;
    }

</style>


<body>

    <header>
        <div class="custGreetings">
            <h2>Selamat Datang <br> Customer &#128075; </h2>
        </div>
    
        <div class="namaKasir">
            <h2>Nama Kasir: <?php echo htmlspecialchars($cashierName); ?></h2>
    
            <!-- Add an id to the logo -->
            <div class="logoHeader" id="fullscreenLogo">
                <img src="img/logoCan.png" alt="Logo CanNgopi" height="70" width="70">
            </div>
        </div>
    </header>


    <div class="MiddleSection">
    <!-- Order ID -->
    <?php if (!empty($orderData)) : ?>
        <div class="getOrderID">
            <h2>Order ID <id>#<?php echo htmlspecialchars($orderID); ?></id></h2>
        </div>

        <!-- Labels -->
        <div class="pesanan-harga-wrapper">
            <h3>Pesanan</h3>
            <h3>Harga</h3>
        </div>

        <!-- Order items list -->
        <div class="order-list">
            <?php foreach ($orderItems as $item) : ?>
                <div class="product-1">
                    <?php if (!empty($item['image'])) { 
                            // Menyusun base URL dan mengonversi path gambar di database
                            $baseURL = "https://cobaadmin.canngopi.com"; // Base URL domain Anda
                            $imagePath = str_replace('/admin', '', $item['image']); // Menghapus '/admin' agar URL bisa mengaksesnya
                            
                            // Menampilkan gambar
                            ?>
                            <img src="<?php echo htmlspecialchars($baseURL . $imagePath); ?>" alt="<?php echo htmlspecialchars($item['nama_menu']); ?>">
                            <?php } else { ?>
                            <img alt="Tidak ada gambar"> 
                            <?php } ?>>
                    <div class="product">
                        <p><?php echo htmlspecialchars($item['nama_menu']); ?></p>
                        <div class="order-notes">
                            <p1><?php echo !empty($item['Notes']) ? htmlspecialchars($item['Notes']) : '-Tidak ada catatan'; ?></p1>
                            <div class="qty-harga">
                                <p2>x<?php echo htmlspecialchars($item['Quantity']); ?></p2>
                                <p3>Rp. <?php echo number_format($item['sub_total'], 0, ',', '.'); ?></p3>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>


        <!-- Divider -->
        <hr class="dashed-line">

        <!-- Subtotal, Discount, and Total -->
        <div class="harga-penjualan">
            <div class="sub-Total">
                <p1>Sub-Total</p1>
                <p2>Rp <?php echo number_format($totalHarga + $totalPromo, 0, ',', '.'); ?></p2>
            </div>

            <div class="discount">
                <p3>Discount</p3>
                <p4>-Rp <?php echo number_format($totalPromo, 0, ',', '.'); ?></p4>
            </div>

            <div class="total">
                <p5>Total</p5>
                <p6>Rp <?php echo number_format($totalHarga, 0, ',', '.'); ?></p6>
            </div>
        </div>

    <?php else : ?>
        <p>No order with 'processing' status found.</p>
    <?php endif; ?>
</div>


   <!-- Dibawah ini buat nampung bagian promosi sosial media dan ketersediaan Can Ngopi di GoFood dan GrbFood-->
   <footer>

    <!-- Dibawah ini buat sosmed Can Ngopi-->
    <div class="followUs">
        <h2>Follow us:</h2>
        <div class="social-icons-container">
            <a href="#" class="social-icons"><i class="fab fa-tiktok"></i> @cangopi.GS</a>
            <a href="#" class="social-icons"><i class="fab fa-instagram"></i> @cangopi.GS</a>
            <a href="#" class="social-icons"><i class="fab fa-whatsapp"></i> +6281399545166</a>
            <a href="#" class="social-icons"><i class="fab fa-youtube"></i> @CanNgopigs</a>
        </div>
   </div>   

   <!-- Dibawah ini buat GoFood/GrabFood Can Ngopi-->
   <div class="availableOn">
    <h2>Available On</h2>
    <div class="images-wrapper">
        <div class="availLogo-container">
            <img src="img/Gofood (1).png" alt="Logo GoFood" class="sizingGofood">
            <img src="img/Grabfood (1).png" alt="Logo GrabFood" class="sizingGrabfood">
        </div>
    </div> 

   </div>

    
</footer>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function checkStatus() {
        $.ajax({
            url: 'CheckStatus.php', // Endpoint to check order status
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.redirect) {
                    // Redirect to index.php if no orders are found
                    window.location.href = response.redirect;
                } else if (response.refresh) {
                    // Refresh the page if new orders are found
                    window.location.reload();
                }
            },
            error: function(xhr, status, error) {
                console.error("Error checking status:", error);
            }
        });
    }

    // Poll the server every 5 seconds
    setInterval(checkStatus, 2000);

    // Function to toggle fullscreen
    function toggleFullScreen() {
        if (!document.fullscreenElement) {
            // Request full screen
            document.documentElement.requestFullscreen().catch(err => {
                alert(`Error attempting to enable full-screen mode: ${err.message} (${err.name})`);
            });
        } else {
            // Exit full screen
            document.exitFullscreen();
        }
    }

    // Add event listener to the logo
    document.getElementById('fullscreenLogo').addEventListener('click', toggleFullScreen);
</script>
 

</body>

</html>
