<?php
// Include connection.php to use the existing database connection
include('../connection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil OrderID dari request
    $OrderID = $_POST['OrderID'];

    // Pastikan OrderID ada
    if (!empty($OrderID)) {
        // Menghitung total harga yang akan dihapus dari order_items terkait BundlingID atau PromoID
        $queryGetTotal = "
            SELECT SUM(oi.sub_total) AS total_removed 
            FROM order_items oi
            JOIN order_list ol ON oi.OrderID = ol.OrderID
            WHERE oi.OrderID = '$OrderID' AND (ol.BundlingID IS NOT NULL OR ol.PromoID IS NOT NULL)
        ";

        $result = $conn->query($queryGetTotal);
        $row = $result->fetch_assoc();
        $totalRemoved = $row['total_removed'] ?? 0;

        // Query untuk menghapus order_items terkait BundlingID atau PromoID terlebih dahulu
        $queryRemoveItems = "
            DELETE oi FROM order_items oi
            JOIN order_list ol ON oi.OrderID = ol.OrderID
            WHERE oi.OrderID = '$OrderID' AND (ol.BundlingID IS NOT NULL OR ol.PromoID IS NOT NULL)
        ";

        // Query untuk menghapus DiscountID dari Order_list dan update Total_harga
        $queryRemoveDiscount = "
            UPDATE order_list 
            SET DiscountID = NULL, PromoID = NULL, BundlingID = NULL, VoucherID = NULL, Total_Promo = NULL, 
                Total_harga = CASE 
                                WHEN Total_harga - ? < 0 THEN 0
                                ELSE Total_harga - ? 
                              END
            WHERE OrderID = '$OrderID'
        ";

        // Begin a transaction to ensure atomicity
        $conn->begin_transaction();

        try {
            // Eksekusi query untuk menghapus order_items terlebih dahulu
            if ($conn->query($queryRemoveItems) === TRUE) {
                // Eksekusi query untuk mengupdate discount dan total_harga
                if ($stmt = $conn->prepare($queryRemoveDiscount)) {
                    $stmt->bind_param("dd", $totalRemoved, $totalRemoved);
                    if ($stmt->execute()) {
                        // Commit jika semua berhasil
                        $conn->commit();
                        echo "Diskon berhasil dihapus, item terkait Bundling atau Promo berhasil dihapus, dan Total_harga berhasil diperbarui!";
                    } else {
                        // Rollback jika penghapusan discount gagal
                        $conn->rollback();
                        echo "Error saat mengupdate total_harga: " . $stmt->error;
                    }
                } else {
                    $conn->rollback();
                    echo "Error saat mempersiapkan query untuk update discount: " . $conn->error;
                }
            } else {
                // Rollback jika penghapusan order_items gagal
                $conn->rollback();
                echo "Error saat menghapus order items: " . $conn->error;
            }
        } catch (Exception $e) {
            // Rollback jika terjadi kesalahan
            $conn->rollback();
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "OrderID tidak ditemukan.";
    }
}

// Tutup koneksi
$conn->close();
?>
