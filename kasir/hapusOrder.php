<?php
// Include connection.php to use the existing database connection
include('../connection.php');

// Query untuk menghapus data dengan status 'processing'
$sql = "DELETE FROM order_list WHERE status = 'processing'";

if ($conn->query($sql) === TRUE) {
    // Redirect ke halaman BetaAFK.php setelah penghapusan berhasil
    header("Location: betaAFK.php");
    exit();
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
