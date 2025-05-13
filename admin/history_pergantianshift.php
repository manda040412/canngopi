<?php
session_start();
include('../connection.php');

if (!isset($_SESSION['UserID']) || empty($_SESSION['UserID'])) {
    // Redirect to index.php if no session is found
    header("Location: index.php");
    exit();
}

// Ambil parameter dari URL
$search = isset($_GET['search']) ? $_GET['search'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$shift = isset($_GET['shift']) ? $_GET['shift'] : '';

// Buat query dasar
$query = "SELECT * FROM shiftkasir WHERE 1";

// Menambahkan kondisi berdasarkan parameter filter
if (!empty($search)) {
    $query .= " AND nama_kasir LIKE '%" . $conn->real_escape_string($search) . "%'";
}
if (!empty($start_date)) {
    $query .= " AND created_at >= '" . $conn->real_escape_string($start_date) . "'";
}
if (!empty($end_date)) {
    $query .= " AND created_at <= '" . $conn->real_escape_string($end_date) . "'";
}
if (!empty($shift)) {
    $query .= " AND shift = '" . $conn->real_escape_string($shift) . "'";
}

// Eksekusi query
$result = $conn->query($query);

// Ambil hasilnya dalam bentuk array
$shift_data = array();
while ($row = $result->fetch_assoc()) {
    $shift_data[] = $row;
}

// Menutup koneksi
$conn->close();

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Pergantian Shift Kasir</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Poppins, sans-serif;
            background: linear-gradient(to bottom right, #D90101, #990000);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .report-container {
            padding:30px;          
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            position: fixed; 
            top: 30px; 
            left: 30px; 
            right: 30px; 
            bottom: 30px; 
            overflow-y: auto; 
            z-index: 900;
        }

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .header i {
            font-size: 28px;
            margin-right: 15px;
            cursor: pointer;
        }

        h2 {
            font-size: 24px;
            margin: 0;
        }

        h3 {
            font-size: 20px;
            margin: 0;
            padding-bottom: 10px;
        }

        .search-bar {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .search-bar .search-wrapper {
            position: relative;
            width: 30%;
        }

        .search-bar .search-wrapper input[type="text"] {
            width: 300px;
            padding: 10px 20px 10px 40px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        .search-bar .search-wrapper i {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            font-size: 18px;
            color: #777;
        }

        .search-bar select,
        .search-bar input[type="date"] {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
            width: 300px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            text-align: center;
        }
        th {
            background-color: #C8C8C8;
        }
        tr:nth-child(even) {
            background-color: #F2F2F2;
        }
        tr:nth-child(odd) {
            background-color: #E4E4E4;
        }
        tr:first-child th:first-child {
            border-top-left-radius: 5px;
        }
        tr:first-child th:last-child {
            border-top-right-radius: 5px;
        }
        tr:last-child td:first-child {
            border-bottom-left-radius: 5px;
        }
        tr:last-child td:last-child {
            border-bottom-right-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="header">
            <i class='bx bx-left-arrow-alt' onclick="goBack()"></i>
            <h2>History Pergantian Shift Kasir</h2>
        </div>
        <div class="search-bar">
            <div>
                <h3>Cari</h3>
                <div class="search-wrapper">
                    <i class='bx bx-search-alt-2'></i>
                    <input type="text" id="search" placeholder="Cari di sini">
                </div>
            </div>
            <div>
                <h3>Mulai</h3>
                <input type="date" id="start_date">
            </div>
            <div>
                <h3>Sampai</h3>
                <input type="date" id="end_date">
            </div>
            <div>
                <h3>Shift</h3>
                <select id="shift">
                    <option value="">Semua</option>
                    <option value="Pagi">Pagi</option>
                    <option value="Siang">Siang</option>
                </select>
            </div>
        </div>

        <div class="table">
            <table id="shift-table">
                <tr>
                    <th>Nama</th>
                    <th>Tanggal</th>
                    <th>Shift</th>
                    <th>Waktu</th>
                </tr>
            </table>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            // Trigger search on input or filter change
            $('#search, #start_date, #end_date, #shift').on('input change', function () {
                performSearch();
            });

            function performSearch() {
                const search = $('#search').val();
                const start_date = $('#start_date').val();
                const end_date = $('#end_date').val();
                const shift = $('#shift').val();

                $.ajax({
                    url: 'search_shiftkasir.php',
                    type: 'GET',
                    data: {
                        search: search,
                        start_date: start_date,
                        end_date: end_date,
                        shift: shift
                    },
                    dataType: 'json',
                    success: function (data) {
                        const table = $('#shift-table');
                        table.find('tr:gt(0)').remove(); // Clear existing rows

                        if (data.length > 0) {
                            data.forEach(function (row) {
                                const rowHtml = `
                                    <tr>
                                        <td>${row.nama_kasir}</td>
                                        <td>${row.created_at}</td>
                                        <td>${row.shift}</td>
                                        <td>${row.waktu}</td>
                                    </tr>
                                `;
                                table.append(rowHtml);
                            });
                        } else {
                            table.append('<tr><td colspan="4">No records found</td></tr>');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error fetching data:', status, error);
                    }
                });
            }

            // Initial search to populate table
            performSearch();
        });

        function goBack() {
            window.location.href = 'monitoring.php';
        }
    </script>
</body>
</html>
