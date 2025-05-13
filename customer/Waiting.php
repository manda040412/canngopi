<?php
// Menghubungkan file connection.php
include('../connection.php');

if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
    $reset_query = "UPDATE status SET Status = 0 WHERE StatusID = 2";
    $conn->query($reset_query);
}

// Reset pesananmakanan to 0 when the page loads
$pesanReset = "UPDATE status SET Status = 0 WHERE StatusID = 8";
$conn->query($pesanReset);


// Query to fetch cashier name (modify based on your table structure)
$cashierQuery = "SELECT nama FROM user 
                JOIN kasir ON user.UserID = kasir.UserID
                WHERE status = 'active' LIMIT 1";
$cashierResult = $conn->query($cashierQuery);
$cashierName = $cashierResult->fetch_assoc()['nama'];

// Query to fetch Total_harga from Order_list where status is 'processing'
$totalQuery = "SELECT Total_harga FROM order_list WHERE status = 'processing' LIMIT 1";
$stmt = $conn->prepare($totalQuery);
$stmt->execute();
$result = $stmt->get_result();

// Check if a result was returned
$totalPrice = "0";
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $totalPrice = number_format($row['Total_harga']);
}

$stmt->close();
$conn->close();

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Waiting Screen</title>
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
        height: 100%;

    }

   .MiddleSection i{
    font-size: 300px;
    color: green;

   }
   
    .MiddleSection p{
        color: grey;
        font-size: 22px;
        margin-bottom: 0px;
    }

    .MiddleSection h4{
        font-weight: 600;
        font-size: 40px;
        margin-bottom: 5px;
        color: #880000;
    }

    .MiddleSection p1{
        color: #9D0000;
        font-size: 40px;
        font-weight: 600;
        margin-top: 10px;
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


    </header>

    <!--Dibawah ini buat nampung yang ada di bagian tengah screen standby mode-->
    <div class="MiddleSection">

        <h4>Menunggu Pembayaran</h4>

        
        <i class='bx bxs-time-five'></i>
        
        
        <p1>Total: <?php echo htmlspecialchars($totalPrice); ?></p1>
        
       
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
                url: 'CheckStatus.php', // Lightweight backend script
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    // Check if there is a redirect URL
                    if (response.redirect) {
                        // Redirect to the specified page
                        window.location.href = response.redirect;
                    }
                    // Optionally handle status or other logic here
                    else {
                        console.log("Status check completed, no redirect needed.");
                    }
                },
                error: function() {
                    console.error("Error checking status.");
                }
            });
        }

        // Poll every 2 seconds (2000ms)
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