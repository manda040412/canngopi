<?php
session_start();

// Include the connection file
include('../connection.php');

$errorMessage = ""; // Initialize an empty error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Check if email and password are provided
    if (!empty($email) && !empty($password)) {
        // Prepare and execute a statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT a.UserID, u.kategori_user FROM admin a JOIN user u ON a.UserID = u.UserID WHERE a.email = ? AND a.password = ?");
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $UserID = $row["UserID"];
            $kategori_user = $row["kategori_user"];

            if ($kategori_user == "Admin") {
                // Store UserID in session for admin
                $_SESSION['UserID'] = $UserID;

                // Redirect to the admin dashboard
                header("Location: monitoring.php");
                exit();
            } else {
                $errorMessage = "You do not have admin privileges.";
            }
        } else {
            $errorMessage = "Incorrect email or password. Please try again.";
        }

        $stmt->close();
    } else {
        $errorMessage = "Please fill in both email and password.";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Poppins, sans-serif;
            background-color: #f2f2f2;
        }

        .login-container {
            width: 100%;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(to right, #D90101, #990000);
        }

        .login-box {
            display: flex;
            background-color: #fff;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            padding: 40px;
            align-items: center;
            border-radius: 20px;
        }

        .login-box img {
            width: 200px;
            height: 200px;
            margin-right: 40px;
        }

        .form-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .form-container h2 {
            margin-bottom: 10px;
            font-size: 24px;
            color: #800000;
        }

        .form-container h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
            color: #800000;
        }

        .form-container input[type="email"],
        .form-container input[type="password"] {
            width: 90%;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 10px;
        }

        .form-container button {
            width: 95%;
            padding: 10px;
            background-color: #e63946;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 20px;
        }
        
         .error-message {
        color: red;
        font-size: 12px;
        margin-top: 2px;
        }
    
        .form-container button:hover {
            background-color: #d62828;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-box">
        <img src="image/logo_canngopi.png" alt="Cangopi Logo">
        <div class="form-container">
            <h2>POS System</h2>
            <h3>Login</h3>
            <form id="loginForm" action="index.php" method="POST">
                <input type="email" name="email" placeholder="E-mail" required>
                <input type="password" name="password" placeholder="Password" required>
                <?php if (isset($errorMessage)) { ?>
                    <p class="error-message"><?= $errorMessage ?></p>
                <?php } ?>
                <button type="submit">Login</button>
            </form>
        </div>  
    </div>
</div>

</body>
</html>
