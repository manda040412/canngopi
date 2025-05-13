<?php
require_once '../connection.php';

// Ambil tanggal dari GET
if (isset($_GET['tanggal'])) { // Ubah 'tanggalRange' menjadi 'tanggal'
    $tanggalRange = $_GET['tanggal'];
    $dates = explode(" - ", $tanggalRange); // Pisahkan rentang tanggal

    if (count($dates) == 2) { // Pastikan formatnya benar
        $start_date = trim($dates[0]) . " 00:00:00"; // Tambahkan waktu awal
        $end_date = trim($dates[1]) . " 23:59:59";   // Tambahkan waktu akhir
    } else {
        die("Format tanggal tidak valid.");
    }
} else {
    die("Tanggal belum dipilih.");
}

// Query untuk mengambil data berdasarkan rentang tanggal
$sql = "SELECT SUM(total_harga) AS gross_sales, SUM(Total_promo) AS discount 
        FROM order_list 
        WHERE created_at BETWEEN '$start_date' AND '$end_date'";

$result = $conn->query($sql);

// Inisialisasi variabel
$gross_sales = 0;
$discount = 0;
$net_sales = 0;
$total_collected = 0;

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $gross_sales = $row["gross_sales"] ?? 0;
    $discount = $row["discount"] ?? 0;
    $net_sales = $gross_sales - $discount;
    $total_collected = $net_sales;
}

// Tutup koneksi database
$conn->close();

// Set header untuk download file Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=sales_summary_" . str_replace(" - ", "_", $tanggalRange) . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Menampilkan isi file Excel
echo "Sales Summary\n";
echo "---------------------------------\n";
echo "Tanggal: $tanggalRange\n";
echo "Gross Sales\tRp. " . number_format($gross_sales, 0, ',', '.') . "\n";
echo "Discount\t(Rp. " . number_format($discount, 0, ',', '.') . ")\n";
echo "Net Sales\tRp. " . number_format($net_sales, 0, ',', '.') . "\n";
echo "Total Collected\tRp. " . number_format($total_collected, 0, ',', '.') . "\n";

exit();
