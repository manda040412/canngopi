<?php
// Include connection.php to use the existing database connection
include('../connection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil OrderID dari request
    $OrderID = $_POST['OrderID'];

    // Pastikan OrderID ada
    if (!empty($OrderID)) {
        // Step 1: Ambil PromoID dari tabel order_list
        $queryGetPromoID = "SELECT PromoID FROM order_list WHERE OrderID = ?";
        $stmt = $conn->prepare($queryGetPromoID);
        $stmt->bind_param("i", $OrderID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $promoID = $row['PromoID'];

            if (!is_null($promoID)) {
                // Step 2: Ambil MenuID dari tabel menu_promo berdasarkan PromoID
                $queryGetMenuIDp = "SELECT MenuID FROM menu_promo WHERE PromoID = ?";
                $queryGetMenuIDp = $conn->prepare($queryGetMenuIDp);
                $queryGetMenuIDp->bind_param("i", $promoID);
                $queryGetMenuIDp->execute();
                $resultMenuIDp = $queryGetMenuIDp->get_result();

                // Step 3: Hapus data dari tabel order_items berdasarkan MenuID
                while ($menuRow = $resultMenuIDp->fetch_assoc()) {
                    $menuID = $menuRow['MenuID'];

                    $queryDeleteOrderItems = "DELETE FROM order_items WHERE MenuID = ? AND OrderID = ?";
                    $stmtDelete = $conn->prepare($queryDeleteOrderItems);
                    $stmtDelete->bind_param("ii", $menuID, $OrderID);

                    if ($stmtDelete->execute()) {
                        echo "Data di tabel order_items dengan MenuID $menuID berhasil dihapus.\n";
                    } else {
                        echo "Gagal menghapus data di tabel order_items untuk MenuID $menuID: " . $stmtDelete->error;
                    }

                    $stmtDelete->close();
                }

                $queryGetSubTotal = "SELECT SUM(Sub_Total) AS Total FROM order_items WHERE OrderID = ? AND MenuID IN (SELECT MenuID FROM menu_promo WHERE PromoID = ?)";
                $stmtSubTotal = $conn->prepare($queryGetSubTotal);
                $stmtSubTotal->bind_param("ii", $OrderID, $promoID);
                $stmtSubTotal->execute();
                $resultSubTotal = $stmtSubTotal->get_result();

                if ($subTotalRow = $resultSubTotal->fetch_assoc()) {
                    $totalHarga = $subTotalRow['Total'];

                    $queryUpdateTotalHarga = "UPDATE order_list SET Total_Harga = Total_Harga - ? WHERE OrderID = ?";
                    $stmtUpdateTotalHarga = $conn->prepare($queryUpdateTotalHarga);
                    $stmtUpdateTotalHarga->bind_param("ii", $totalHarga, $OrderID);

                    if ($stmtUpdateTotalHarga->execute()) {
                        echo "Total harga di tabel order_list berhasil diupdate.\n";
                    } else {
                        echo "Gagal mengupdate total harga di tabel order_list: " . $stmtUpdateTotalHarga->error;
                    }

                    $stmtUpdateTotalHarga->close();
                }

                $queryGetMenuIDp->close();
            }
        }

        $queryGetBundlingID = "SELECT BundlingID FROM order_list WHERE OrderID = ?";
        $stmt = $conn->prepare($queryGetBundlingID);
        $stmt->bind_param("i", $OrderID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $bundlingID = $row['BundlingID'];

            if (!is_null($bundlingID)) {
                // Step 2: Ambil MenuID dari tabel menu_bundling berdasarkan BundlingID
                $queryGetMenuIDb = "SELECT MenuID FROM menu_bundling WHERE BundlingID = ?";
                $stmt = $conn->prepare($queryGetMenuIDb);
                $stmt->bind_param("i", $bundlingID);
                $stmt->execute();
                $resultMenuIDb = $stmt->get_result();

                // Step 3: Hapus data dari tabel order_items berdasarkan MenuID
                while ($menuRow = $resultMenuIDb->fetch_assoc()) {
                    $menuID = $menuRow['MenuID'];

                    $queryDeleteOrderItems = "DELETE FROM order_items WHERE MenuID = ? AND OrderID = ?";
                    $stmtDelete = $conn->prepare($queryDeleteOrderItems);
                    $stmtDelete->bind_param("ii", $menuID, $OrderID);

                    if ($stmtDelete->execute()) {
                        echo "Data di tabel order_items dengan MenuID $menuID berhasil dihapus.\n";
                    } else {
                        echo "Gagal menghapus data di tabel order_items untuk MenuID $menuID: " . $stmtDelete->error;
                    }

                    $stmtDelete->close();
                }

                // Step 4: Hapus total_harga dari tabel order_list
                $queryGetSubTotal = "SELECT SUM(Sub_Total) AS Total FROM order_items WHERE OrderID = ? AND MenuID IN (SELECT MenuID FROM menu_bundling WHERE BundlingID = ?)";
                $stmtSubTotal = $conn->prepare($queryGetSubTotal);
                $stmtSubTotal->bind_param("ii", $OrderID, $bundlingID);
                $stmtSubTotal->execute();
                $resultSubTotal = $stmtSubTotal->get_result();

                if ($subTotalRow = $resultSubTotal->fetch_assoc()) {
                    $totalHarga = $subTotalRow['Total'];

                    $queryUpdateTotalHarga = "UPDATE order_list SET Total_Harga = Total_Harga - ? WHERE OrderID = ?";
                    $stmtUpdateTotalHarga = $conn->prepare($queryUpdateTotalHarga);
                    $stmtUpdateTotalHarga->bind_param("ii", $totalHarga, $OrderID);

                    if ($stmtUpdateTotalHarga->execute()) {
                        echo "Total harga di tabel order_list berhasil diupdate.\n";
                    } else {
                        echo "Gagal mengupdate total harga di tabel order_list: " . $stmtUpdateTotalHarga->error;
                    }

                    $stmtUpdateTotalHarga->close();
                }

                $stmtSubTotal->close();
            }
        }
    }

    $queryRemoveDiscount = "UPDATE order_list 
                            SET DiscountID= NULL, BundlingID = NULL, PromoID = NULL, VoucherID = NULL, Total_Promo = NULL 
                            WHERE OrderID = ?";
    $stmtUpdate = $conn->prepare($queryRemoveDiscount);
    $stmtUpdate->bind_param("i", $OrderID);

    if ($stmtUpdate->execute()) {
        echo "Data terkait berhasil dihapus dari order_list.";
    } else {
        echo "Gagal menghapus data dari order_list: " . $stmtUpdate->error;
        echo "Error: " . $conn->error;
    }

    $stmtUpdate->close();
}

$stmt->close();

// Tutup koneksi
mysqli_close($conn);
?>
