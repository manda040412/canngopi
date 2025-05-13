<?php
// Include the connection file
include('../connection.php');

date_default_timezone_set('Asia/Jakarta'); // Set to your desired time zone

// Start the session
session_start();

// Check if a previous userID exists in the session
if (isset($_SESSION['userID'])) {
    $previousUserID = $_SESSION['userID'];

    // Update the status to 'inactive' for the previous user
    $updateStatusQuery = "UPDATE user SET status = 'inactive', updated_at = NOW() WHERE UserID = ?";
    $stmt = $conn->prepare($updateStatusQuery);
    $stmt->bind_param("i", $previousUserID);
    $stmt->execute();

    // Check if the status update was successful
    if ($stmt->affected_rows > 0) {
        // Retrieve the ShiftKasirID for the user
        $shiftKasirQuery = "SELECT ShiftKasirID FROM shiftkasir WHERE UserID = ? LIMIT 1";
        $stmtShift = $conn->prepare($shiftKasirQuery);
        $stmtShift->bind_param("i", $previousUserID);
        $stmtShift->execute();
        $resultShift = $stmtShift->get_result();

        if ($resultShift->num_rows > 0) {
            $shiftKasirID = $resultShift->fetch_assoc()['ShiftKasirID'];

            // Insert into historyshift table
            $insertHistoryQuery = "INSERT INTO historyshift (ShiftKasirID, created_at) VALUES (?, NOW())";
            $stmtHistory = $conn->prepare($insertHistoryQuery);
            $stmtHistory->bind_param("i", $shiftKasirID);
            $stmtHistory->execute();
            $stmtHistory->close();
        } else {
            error_log("No ShiftKasirID found for UserID: $previousUserID");
        }

        $stmtShift->close();
    } else {
        error_log("Failed to update user status to inactive for UserID: $previousUserID");
    }

    $stmt->close();

    // Clear the session data for userID
    unset($_SESSION['userID']);
}

// Verify PIN
if (isset($_POST['pin'])) {
    $pin = $_POST['pin'];

    // Query to get userID and check the PIN
    $query = "SELECT userID FROM kasir WHERE pin = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $pin); // Assuming pin is a string
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Get userID from the Kasir table
        $row = $result->fetch_assoc();
        $userID = $row['userID'];
        $_SESSION['userID'] = $userID;

        // Now get the name and user category from the user table
        $queryUser = "SELECT nama, kategori_user FROM user WHERE userID = ?";
        $stmtUser = $conn->prepare($queryUser);
        $stmtUser->bind_param("i", $userID); // Assuming userID is an integer
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();

        if ($resultUser->num_rows > 0) {
            // Fetch the user information
            $userRow = $resultUser->fetch_assoc();
            $nama = $userRow['nama'];
            $kategori_user = $userRow['kategori_user'];

            // Store user information in session
            $_SESSION['userID'] = $userID;
            $_SESSION['userName'] = $nama;
            $_SESSION['kategori_user'] = $kategori_user;

            // Redirect to betaAFK.php
            header('Location: betaAFK.php');
            exit;
        } else {
            // User not found in user table
            $errorMessage = "User not found.";
        }
    } else {
        // PIN is incorrect, display error message
        $errorMessage = "Incorrect PIN. Please try again.";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Login Kasir</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <script src="kasirFunction.js"></script>
</head>
<style>
    .loginPage {
        display: flex;
        justify-content: space-evenly;
        align-items: center;
        background-color: #f1f1f1;
        border-radius: 20px;
        width: 90%;
        height: calc(100vh - 100px);
    }

    .formPage {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 400px;
        width: 300px;
        border-radius: 20px;
        padding: 20px;
    }

    .formPage h1 {
        font-weight: 600;
        margin: 0px;
    }

    .formPage h2 {
        font-weight: 600;
        padding-top: 10;
        margin: 0px;
        margin-bottom: 10px;
    }

    .formPage p {
        margin: 0px;
    }

    .logoPage img {
        width: 350px;
        height: auto;
        max-width: 100%;
        margin: 0 auto;
        display: block;
        padding: 20px;
    }

    form {
        display: flex;
        flex-direction: column;
        width: 100%;
        gap: 15px;
        padding: 20px;
        border-radius: 10px;
    }

    input[type="password"] {
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 16px;
        transition: border-color 0.3s ease;
    }

    input[type="password"]:focus {
        border-color: #d90101;
        outline: none;
    }

    .error-message {
        color: red;
        font-size: 12px;
        margin-top: 2px;
    }

    button[type="submitLogin"] {
        padding: 10px;
        background-color: #990000;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    button[type="submitLogin"]:hover {
        background-color: #d90101;
    }
</style>

<body>
    <div class="loginPage">
        <div class="logoPage">
            <img src="logo/logoCan.png" alt="">
        </div>
        <div class="formPage">
            <h1>POS System</h1>
            <h2>Login</h2>
            <form action="" method="post">
                <p>Masukkan pin:</p>
                <input type="password" id="pinInput" name="pin" placeholder="Password" pattern="\d*" maxlength="6">
                <?php if (isset($errorMessage)) { ?>
                    <p class="error-message"><?= $errorMessage ?></p>
                <?php } ?>
                <button type="submitLogin">Login</button>
            </form>
        </div>
    </div>
</body>

</html>
