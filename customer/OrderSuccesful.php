<?php
session_start();

// Include the connection file
include('../connection.php');

// Reset the status if "reset" parameter is passed in the URL
if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
    $reset_query = "UPDATE status SET Status = 0 WHERE StatusID = 3";
    $conn->query($reset_query);
}


// Query to fetch cashier name (modify based on your table structure)
$cashierQuery = "SELECT nama FROM user 
                 JOIN kasir ON user.UserID = kasir.UserID
                 WHERE status = 'active' LIMIT 1";
$cashierResult = $conn->query($cashierQuery);
$cashierName = $cashierResult->fetch_assoc()['nama'];

// Query to fetch Nomor Meja and created_at from Order_list where status is 'processing'
$orderQuery = "SELECT nomormeja, created_at FROM order_list WHERE status = 'processing' ORDER BY created_at DESC LIMIT 1";
$result = $conn->query($orderQuery);

// Initialize default values
$nomorMeja = "N/A";
$orderTime = "N/A";

// Fetch results if there is a completed order
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $nomorMeja = $row['nomormeja'];
    $orderTime = date("H:i", strtotime($row['created_at'])); // Format time as HH:MM
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Successful Screen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">


</head>

<!-- Style buat ngatur estetika website-->
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
        transform: translateX(-50%); /* Ini biar logonya di tengah*/
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
        justify-content: center;
        box-sizing: border-box;
        align-items: center;        
        width: 100%;
        padding: 5%;
        flex-grow: 1;
        text-align: center;
    }

   .MiddleSection svg{
        margin-bottom: 30px;
        width: 80%;
        max-width: 200px;
        height: auto;
    }

    .MiddleSection h2 {
        text-align: center;
        color:#000000;
        font-size: 30px;
        margin-bottom: 20px;

    }

    .MiddleSection p{
        color: grey;
        font-size: 24px;
        margin-bottom: 30px;
    }

    .MiddleSection h4{
        font-weight: 600;
        font-size: 28px;
        margin-bottom: 40px;
        color: #880000;
    }

    .MiddleSection p1{
        color: rgb(202, 202, 202);
        font-size: 18px;
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
 
    <!-- Bagian dibawah ini buat nampung isian dari header/topbar/navbar-->
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

    <!--Dibawah ini buat nampung yang ada di bagian tengah screen standby mode-->
    <div class="MiddleSection">

        <!--Ini buat icon checknya, kalo mau ganti warga, ganti HEX Codenya di fill="" ya-->
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="#1ec221" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg> 

        <h2>Pesanan akan segera diproses!</h2>

        <p>Mohon ditunggu</p>

        <h4>No Meja: <?php echo $nomorMeja; ?></h4>
        <p1>Order Time: <?php echo $orderTime; ?></p1>
   
        
       
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
                url: 'CheckStatus.php', // The PHP script to check the status
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.redirect) {
                        // If the status is updated, redirect to the specified page
                        window.location.href = response.redirect;
                    }
                },
                error: function() {
                    console.error("Error checking status.");
                }
            });
        }

        // Poll every 5 seconds (5000ms)
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