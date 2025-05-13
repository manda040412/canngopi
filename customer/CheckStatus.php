<?php
header('Content-Type: application/json');

// Include the connection file
include('../connection.php');

// Initialize response
$response = [
    'status' => null,    // StatusID = 1
    'redirect' => false, // Redirect URL
    'refresh' => false   // Refresh trigger for StatusID = 7 or 8
];

// Check StatusID = 1
$statusQuery1 = "SELECT Status FROM status WHERE StatusID = 1 LIMIT 1";
$result1 = $conn->query($statusQuery1);
if ($result1 && $result1->num_rows > 0) {
    $statusRow1 = $result1->fetch_assoc();
    $response['status'] = (int)$statusRow1['Status'];
} else {
    $response['error'] = 'No status found for StatusID = 1';
}

// Check StatusID = 2
$statusQuery2 = "SELECT Status FROM status WHERE StatusID = 2 LIMIT 1";
$result2 = $conn->query($statusQuery2);
if ($result2 && $result2->num_rows > 0) {
    $statusRow2 = $result2->fetch_assoc();
    if ((int)$statusRow2['Status'] === 1) {
        // Redirect to Waiting.php if StatusID = 2 is active
        $response['redirect'] = 'Waiting.php?reset=true';
    }
}

// Check StatusID = 3
$statusQuery3 = "SELECT Status FROM status WHERE StatusID = 3 LIMIT 1";
$result3 = $conn->query($statusQuery3);
if ($result3 && $result3->num_rows > 0) {
    $statusRow3 = $result3->fetch_assoc();
    if ((int)$statusRow3['Status'] === 1) {
        // Redirect to OrderSuccessful.php if StatusID = 3 is active
        $response['redirect'] = 'OrderSuccesful.php?reset=true';
    }
}

// Check StatusID = 4
$statusQuery4 = "SELECT Status FROM status WHERE StatusID = 4 LIMIT 1";
$result4 = $conn->query($statusQuery4);
if ($result4 && $result4->num_rows > 0) {
    $statusRow4 = $result4->fetch_assoc();
    if ((int)$statusRow4['Status'] === 1) {
        // Redirect to Rating.php if StatusID = 4 is active
        $response['redirect'] = 'Rating.php?reset=true';
    }
}

// Check StatusID = 6
$statusQuery6 = "SELECT Status FROM status WHERE StatusID = 6 LIMIT 1";
$result6 = $conn->query($statusQuery6);
if ($result6 && $result6->num_rows > 0) {
    $statusRow6 = $result6->fetch_assoc();
    if ((int)$statusRow6['Status'] === 1) {
        // Redirect to index.php if StatusID = 6 is active
        $response['redirect'] = 'index.php?reset=true';
    }
}

// Check StatusID = 7 for refresh trigger
$statusQuery7 = "SELECT Status FROM status WHERE StatusID = 7 LIMIT 1";
$result7 = $conn->query($statusQuery7);
if ($result7 && $result7->num_rows > 0) {
    $statusRow7 = $result7->fetch_assoc();
    if ((int)$statusRow7['Status'] === 1) {
        // Trigger refresh and reset StatusID = 7 to 0
        $response['refresh'] = true;
        $conn->query("UPDATE status SET Status = 0 WHERE StatusID = 7");
    }
}

// Check StatusID = 8 for refresh trigger
$statusQuery8 = "SELECT Status FROM status WHERE StatusID = 8 LIMIT 1";
$result8 = $conn->query($statusQuery8);
if ($result8 && $result8->num_rows > 0) {
    $statusRow8 = $result8->fetch_assoc();
    if ((int)$statusRow8['Status'] === 1) {
        // Trigger refresh and reset StatusID = 8 to 0
        $response['refresh'] = true;
        $conn->query("UPDATE status SET Status = 0 WHERE StatusID = 8");
    }
}

// Output the JSON response
echo json_encode($response);

$conn->close();
?>
