<?php
session_start(); // Start the session

// Include the connection file to connect to the database
include('../connection.php');

// Reset pesananmakanan to 0 when the page loads
$sqlReset = "UPDATE status SET Status = 0 WHERE StatusID = 4";
$conn->query($sqlReset);

// Fetch background image where status is active
$bg_query = "SELECT image FROM event WHERE status = 'active' LIMIT 1";
$bg_result = $conn->query($bg_query);

if ($bg_result->num_rows > 0) {
    $bg_row = $bg_result->fetch_assoc();
    // Use the path stored in the database, assuming it's relative to your web server
    $background_image_path = $bg_row['image'];
} else {
    $background_image_path = ''; // Fallback in case no background is active
}

// Fetch active cashier
$kasir_query = "SELECT nama FROM user 
                JOIN kasir ON user.UserID = kasir.UserID
                WHERE status = 'active' LIMIT 1";
$kasir_result = $conn->query($kasir_query);

// Initialize variables
$nama_kasir = 'Unknown'; // Default value

// Check if query returned any rows
if ($kasir_result && $kasir_result->num_rows > 0) {
    $kasir_row = $kasir_result->fetch_assoc();
    $nama_kasir = $kasir_row['nama'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">    
    <title>Standby Screen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>

<style type="text/css">
    * {
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

    /* Bagian Header */
    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(to right, #880000 0%, #9D0000 100%);
        height: 100px;
        width: 100%;
        color: white;
        position: fixed; 
        top: 0; 
        left: 0;
        z-index: 1000;
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

    /* Bagian tengah halaman */
    .MiddleSection {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        background-size: cover; 
        background-repeat: no-repeat; 
        background-position: center; 
        width: 100%;
        padding: 5%;
        flex-grow: 1;
        <?php if (!empty($background_image_path)):
        
        // Menghapus '/admin' dari path gambar jika ada
        $imagePath = str_replace('/admin', '', $background_image_path);
        // Menggabungkan dengan base URL yang sesuai
        $baseURL = "https://cobaadmin.canngopi.com"; 
        $finalImagePath = $baseURL . $imagePath; // Path lengkap gambar
        ?>
        background-image: url('<?php echo $finalImagePath; ?>');
        <?php endif; ?>
    }

    .logoWelcome {
        margin-bottom: 20px;
        width: 100%;
        max-width: 400px;
        height: auto;
        cursor: pointer; /* Make it look like a clickable button */
    }

    .MiddleSection h2 {
        text-align: center;
        color: #880000;
    }

    /* Footer */
    footer {
        display: flex;
        justify-content: space-between;
        background: linear-gradient(to right, #880000 0%, #9D0000 100%);
        height: 100px;
        width: 100%;
        padding: 10px;
        color: white;
        position: fixed; 
        bottom: 1px; 
        left: 0;
        z-index: 1000;
    }

    .followUs {
        display: flex;
        flex-direction: column; 
        align-items: flex-start; 
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
            <h2>Nama Kasir: <?= $nama_kasir ?></h2>
        </div>
    </header>

    <div class="MiddleSection">
        <!-- Logo that acts as Fullscreen button -->
        <div class="logoWelcome">
            <img src="img/logoCan.png" alt="Logo CanNgopi" height="400" width="400" id="fullscreenLogo">
        </div>

        <h2>Hi there, how can I help you?</h2>
    </div>

    <footer>
        <div class="followUs">
            <h2>Follow us:</h2>
            <div class="social-icons-container">
                <a href="#" class="social-icons"><i class="fab fa-tiktok"></i> @cangopi.GS</a>
                <a href="#" class="social-icons"><i class="fab fa-instagram"></i> @cangopi.GS</a>
                <a href="#" class="social-icons"><i class="fab fa-whatsapp"></i> +6281399545166</a>
                <a href="#" class="social-icons"><i class="fab fa-youtube"></i> @CanNgopigs</a>
            </div>
        </div>   

        <div class="availableOn">
            <h2>Available On</h2>
            <div class="availLogo-container">
                <img src="img/Gofood (1).png" alt="Logo GoFood" class="sizingGofood">
                <img src="img/Grabfood (1).png" alt="Logo GrabFood" class="sizingGrabfood">
            </div>
        </div>
    </footer>

    <!-- Fullscreen JavaScript -->
    <script>
        const fullscreenLogo = document.getElementById('fullscreenLogo');

        fullscreenLogo.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        });

        // Automatically enter fullscreen on page load
        window.onload = function() {
            document.documentElement.requestFullscreen();
        };

        
        function checkStatus() {
        console.log("Checking status...");

        fetch('CheckStatus.php')
            .then(response => response.json())
            .then(data => {
                console.log("Response from server:", data);
                if (data.status === 1) {
                    console.log("Redirecting to OrderPreview...");
                    window.location.href = 'OrderPreview.php?reset=true';
                } else {
                    console.log("Status is not 1, staying on the current page.");
                }
            })
            .catch(error => console.error("Error fetching status:", error));
        }

        // Poll every 5 seconds
        setInterval(checkStatus, 2000);
        
    </script>
</body>
</html>
