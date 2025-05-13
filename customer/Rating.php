<?php
session_start();

// Include the connection file
include('../connection.php');

if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
    $reset_query = "UPDATE status SET Status = 0 WHERE StatusID = 4";
    $conn->query($reset_query);
}

// Query to fetch cashier name (modify based on your table structure)
$cashierQuery = "SELECT nama FROM user 
                JOIN kasir ON user.UserID = kasir.UserID
                WHERE status = 'active' LIMIT 1";
$cashierResult = $conn->query($cashierQuery);
$cashierName = $cashierResult->fetch_assoc()['nama'];

// Check if the rating was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    $rating = (int) $_POST['rating'];
    // Update the Order_list table with the rating
    $updateQuery = "UPDATE order_list SET rating = ? WHERE status = 'pending' ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("i", $rating);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Define feedback messages based on the rating
        $feedbackMessages = [
            1 => ["message" => "Yikes", "additional" => "Kami mohon maaf mendengar Anda sangat tidak puas. Mohon beri tahu kami bagaimana kami bisa memperbaiki layanan kami."],
            2 => ["message" => "Meh", "additional" => "Kami mohon maaf atas ketidakpuasan Anda. Kami menghargai masukan Anda dan akan berusaha untuk menjadi lebih baik."],
            3 => ["message" => "Not Bad", "additional" => "Terima kasih atas masukan Anda! Kami berusaha untuk terus meningkatkan layanan kami."],
            4 => ["message" => "Cool", "additional" => "Kami senang Anda puas! Kami selalu siap untuk membuat pengalaman Anda lebih baik."],
            5 => ["message" => "Awesome", "additional" => "Terima kasih atas penilaian yang luar biasa! Kami sangat senang Anda sangat puas dengan layanan kami."]
        ];

        // Store the feedback message in the session
        $_SESSION['feedback'] = $feedbackMessages[$rating];

        // Send a response without redirecting
        echo json_encode(["status" => "success", "feedback" => $_SESSION['feedback']]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to submit rating. Please try again."]);
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rating</title>
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
    .MiddleSection h1 {
        text-align: center;
        color:#880000;
        margin-bottom: 20px;
    }

    .MiddleSection p {
        text-align: center;
        color:#880000;
        font-size: 20px;

    }

    .ratings-wrapper{
        display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap; /* Agar gambar bisa menyesuaikan layar */
    width: 100%;

    }

.ratings {
    display: flex;
    flex-wrap: wrap; /* Agar bisa turun ke baris berikutnya jika tidak cukup */
    justify-content: center;
    gap: 20px; /* Memberikan jarak antar gambar */
    max-width: 100%;
}

.ratings img {
    width: 18vw; /* Ukuran gambar disesuaikan agar tidak terlalu besar */
    max-width: 100px; /* Batasan agar tidak terlalu besar di layar lebih besar */
    height: auto;
}

    .ratings span{
        cursor: pointer;
        transition: color .2s, transform .2s;
        font-size: 150px;
    }

    .ratings span:hover{ /*Ini buat pas dia hover doang*/
        color: gold;
        transform: scale(1.3);
    }

    .ratings span:hover ~ span { /*Ini pas dia udh klik warna yg lainnya ngikut gold*/
        color: inherit;
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
    <h1>Bagaimana pelayanan kami?</h1>
    <p>Berikan penilaian anda terhadap pelayanan yang kami berikan sebagai bentuk evaluasi</p>

    <!-- Star Rating -->
    <div class="ratings-wrapper">
        <form id="ratingForm" method="POST" action="rating.php">
            <div class="ratings">
                <span data-rating="1"><img src="img/1. Yikes.png" alt="Rating 1" /></span>
                <span data-rating="2"><img src="img/2. Meh.png" alt="Rating 2" /></span>
                <span data-rating="3"><img src="img/3. Not Bad.png" alt="Rating 3" /></span>
                <span data-rating="4"><img src="img/4. Cool.png" alt="Rating 4" /></span>
                <span data-rating="5"><img src="img/5. Awesome.png" alt="Rating 5" /></span>
            </div>
            <input type="hidden" name="rating" id="ratingValue" value="">
        </form>
    </div>

        <h3 id="rating-feedback" style="display: none; color: #880000; font-size: 20px; text-align: center; margin-top: 20px;"></h3>
        <p id="additional-feedback" style="display: none; color: #880000; font-size: 18px; text-align: center; margin-top: 30px;"></p>
    </div>


    <!--Dibawah ini js buat munculin pesan ke cust setelah dia input starsnya-->
    <script>
       const stars = document.querySelectorAll('.ratings span');
        const feedback = document.getElementById('rating-feedback');
        const additionalFeedback = document.getElementById('additional-feedback');
        const ratingForm = document.getElementById('ratingForm');
        const ratingInput = document.getElementById('ratingValue');

    // Feedback messages based on rating
    const feedbackMessages = {
        1: "Yikes",
        2: "Meh",
        3: "Not Bad",
        4: "Cool",
        5: "Awesome"
    };

    const additionalMessages = {
        1: "Kami mohon maaf mendengar Anda sangat tidak puas. Mohon beri tahu kami bagaimana kami bisa memperbaiki layanan kami.",
        2: "Kami mohon maaf atas ketidakpuasan Anda. Kami menghargai masukan Anda dan akan berusaha untuk menjadi lebih baik.",
        3: "Terima kasih atas masukan Anda! Kami berusaha untuk terus meningkatkan layanan kami.",
        4: "Kami senang Anda puas! Kami selalu siap untuk membuat pengalaman Anda lebih baik.",
        5: "Terima kasih atas penilaian yang luar biasa! Kami sangat senang Anda sangat puas dengan layanan kami."
    };

        stars.forEach(star => {
        star.addEventListener('click', () => {
            const rating = star.getAttribute('data-rating');
            ratingInput.value = rating;

            feedback.textContent = feedbackMessages[rating];
            feedback.style.display = 'block';
            additionalFeedback.textContent = additionalMessages[rating];
            additionalFeedback.style.display = 'block';

            // Disable further clicks on stars and highlight selected stars
            stars.forEach(s => s.style.pointerEvents = 'none');
            stars.forEach(s => s.style.color = '');
            star.style.color = 'gold';
            let currentStar = star;
            while (currentStar) {
                currentStar.style.color = 'gold';
                currentStar = currentStar.previousElementSibling;
            }
            
            // Pass the rating to the submitRatingAJAX function
            submitRatingAJAX(rating);
        });
    });

    function submitRatingAJAX(rating) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "Rating.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                console.log("Rating submitted successfully.");
                console.log("Server response:", xhr.responseText);
            }
        };
        
        // Send the form data with the rating value
        xhr.send(`rating=${rating}`);
    }
        
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
    //document.getElementById('fullscreenLogo').addEventListener('click', toggleFullScreen);
    setTimeout(function() {
            window.location.href = "index.php";
        }, 10000);

    </script>


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
    
    
</body>

</html>
