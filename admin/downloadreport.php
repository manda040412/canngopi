<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_GET['ReportID'])) {
    $reportID = $_GET['ReportID'];

    require_once('../connection.php');

    // Fetch report data
    $sql = "SELECT *, DATE(created_at) AS ReportDate FROM report WHERE ReportID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reportID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $report = $result->fetch_assoc();
        $reportDate = $report['ReportDate']; // Get the report creation date
    } else {
        echo "No report found with this ID.";
        exit();
    }

    // Fetch transactions related to the report
    $sql_transactions = "
   SELECT 
    ol.OrderID, 
    u.nama AS Nama_Kasir, 
    (SELECT SUM(oi.sub_total) 
     FROM order_items oi 
     WHERE oi.OrderID = ol.OrderID) AS Sub_Total, -- Correctly calculate sub_total for this OrderID only
    ol.total_promo AS Discount_IDR, 
    ol.total_harga AS Total_Bayar_IDR, -- Assuming this is already finalized in the order_list table
    (SELECT SUM(oi.quantity) 
     FROM order_items oi 
     WHERE oi.OrderID = ol.OrderID) AS Total_Items_Sold -- Correctly calculate total items for this OrderID only
FROM 
    order_list ol
JOIN 
    order_penjualan op ON ol.OrderID = op.OrderID
JOIN
    penjualan p ON op.PenjualanID = p.PenjualanID
JOIN 
    user u ON ol.UserID = u.UserID
JOIN
    report r ON p.PenjualanID = r.PenjualanID
WHERE 
    r.ReportID = ? -- Replace with actual ReportID
    AND ol.status = 'completed'
GROUP BY 
    ol.OrderID, u.nama, ol.total_promo, ol.total_harga;
    ";

    $stmt_transactions = $conn->prepare($sql_transactions);
    $stmt_transactions->bind_param("i", $reportID);
    $stmt_transactions->execute();
    $transactions_result = $stmt_transactions->get_result();

    // Wait until all data is fetched
    if (!$transactions_result) {
        echo "Failed to fetch transactions.";
        exit();
    }

    // Optional delay to ensure processing time
    sleep(1); // 1-second delay

    // Prepare summary data
    $totalTransactions = 0;
    $totalItemsSold = 0;
    $totalRevenue = 0;
    $totalDiscountGiven = 0;

    $excelData[] = ['Order ID', 'Nama Kasir', 'Sub-Total (IDR)', 'Discount (IDR)', 'Total Bayar (IDR)', 'Total Items Sold'];

    while ($transaction = $transactions_result->fetch_assoc()) {
        $excelData[] = [
            $transaction['OrderID'],
            $transaction['Nama_Kasir'],
            $transaction['Sub_Total'],
            $transaction['Discount_IDR'],
            $transaction['Total_Bayar_IDR'],
            $transaction['Total_Items_Sold']
        ];
        $totalTransactions++;
        $totalItemsSold += $transaction['Total_Items_Sold'];
        $totalRevenue += $transaction['Total_Bayar_IDR'];
        $totalDiscountGiven += $transaction['Discount_IDR'];
    }

    if ($totalTransactions === 0) {
    echo "No transactions found for ReportID = " . $reportID . ". Please verify the data.";
    exit();
}


    // Create new Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set column widths
    $sheet->getColumnDimension('A')->setWidth(25);
    $sheet->getColumnDimension('B')->setWidth(25);
    $sheet->getColumnDimension('C')->setWidth(39);
    $sheet->getColumnDimension('D')->setWidth(20);
    $sheet->getColumnDimension('E')->setWidth(20);
    $sheet->getColumnDimension('F')->setWidth(15);

    // Header Section
    $sheet->setCellValue('A1', 'Judul Laporan')->getStyle('A1')->getFont()->setBold(true);
    $sheet->setCellValue('B1', 'Daily Sales Report (' . $reportDate . ')');
    $sheet->setCellValue('A2', 'Tanggal')->getStyle('A2')->getFont()->setBold(true);
    $sheet->setCellValue('B2', $reportDate);
    $sheet->setCellValue('A3', 'Total Transactions Done')->getStyle('A3')->getFont()->setBold(true);
    $sheet->setCellValue('B3', $totalTransactions);
    $sheet->setCellValue('A4', 'Total Items Sold')->getStyle('A4')->getFont()->setBold(true);
    $sheet->setCellValue('B4', $totalItemsSold);
    $sheet->setCellValue('A5', 'Total Revenue')->getStyle('A5')->getFont()->setBold(true);
    $sheet->setCellValue('B5', $totalRevenue);
    $sheet->setCellValue('A6', 'Total Discount Given')->getStyle('A6')->getFont()->setBold(true);
    $sheet->setCellValue('B6', $totalDiscountGiven);

    // Data Section
    $startRow = 8;
    foreach ($excelData as $row) {
        $colLetter = 'A';
        foreach ($row as $cell) {
            $sheet->setCellValue($colLetter . $startRow, $cell);
            $colLetter++;
        }
        $startRow++;
    }

    // Set headers to download as .xlsx file
    $filename = 'Sales_Report_' . $reportDate . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Create a writer instance and save the output to php://output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

    // Close the database connection
    $stmt->close();
    $stmt_transactions->close();
    $conn->close();
    exit();
} else {
    echo "No ReportID provided.";
    exit();
}
