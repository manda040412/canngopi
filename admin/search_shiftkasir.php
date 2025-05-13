<?php
session_start();
// Include the connection.php file to connect to the database
include('../connection.php');

if (!isset($_SESSION['UserID']) || empty($_SESSION['UserID'])) {
    // Redirect to index.php if no session is found
    header("Location: index.php");
    exit();
}

// Get search parameters from the AJAX request
$search = $conn->real_escape_string($_GET['search'] ?? '');
$start_date = $conn->real_escape_string($_GET['start_date'] ?? '');
$end_date = $conn->real_escape_string($_GET['end_date'] ?? '');
$shift = $conn->real_escape_string($_GET['shift'] ?? '');

// Start building the SQL query
$sql = "
    SELECT 
        sk.ShiftKasirID, 
        u.nama as nama_kasir, 
        s.Shift, 
        s.Waktu_start, 
        s.Waktu_end, 
        hs.created_at
    FROM 
        historyshift hs
    INNER JOIN 
        shiftkasir sk ON hs.ShiftKasirID = sk.ShiftKasirID
    INNER JOIN 
        shift s ON sk.ShiftID = s.ShiftID
    INNER JOIN 
        user u ON sk.UserID = u.UserID
    WHERE 
        sk.deleted_at IS NULL
        AND u.nama LIKE '%$search%'
";

// Add date filtering only if both start_date and end_date are provided
if (!empty($start_date) && !empty($end_date)) {
    $start_date = $start_date . ' 00:00:00'; // Include the start of the day
    $end_date = $end_date . ' 23:59:59'; // Include the end of the day
    $sql .= " AND hs.created_at BETWEEN '$start_date' AND '$end_date'";
}

// Add shift filtering if a specific shift is selected
if (!empty($shift)) {
    $sql .= " AND s.Shift = '$shift'";
}

$sql .= " ORDER BY sk.created_at DESC";

// Execute the query
$result = $conn->query($sql);

$response = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $response[] = [
            'nama_kasir' => $row['nama_kasir'],
            'created_at' => date("d/m/Y", strtotime($row['created_at'])),
            'shift' => $row['Shift'],
            'waktu' => $row['Waktu_start'] . ' - ' . $row['Waktu_end']
        ];
    }
} else {
    // Optional: include debugging info if needed
    // $response['error'] = $conn->error;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>
