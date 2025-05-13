<?php
header('Content-Type: application/json');

// Include the connection file
include('../connection.php');

$response = ['refresh' => false, 'message' => 'Status not active for refreshing.'];

// Check if status query succeeds
$statusQuery = "SELECT Status FROM status WHERE StatusID = 7 LIMIT 1"; 
$statusResult = $conn->query($statusQuery);
if (!$statusResult) {
    echo json_encode(['error' => 'Status query failed']);
    exit();
}

$statusRow = $statusResult->fetch_assoc();
if ($statusRow['Status'] == 1) {
    // Status is active, so set the refresh flag
    $response['refresh'] = true;
    $response['message'] = 'Status is active, refresh required.';

    // Update the status to 0 after the page refresh
    $homereset = "UPDATE status SET Status = 0 WHERE StatusID = 7";
    $conn->query($homereset);
}
echo json_encode($response);
exit();

?>