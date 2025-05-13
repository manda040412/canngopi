<php
session_start();

// Include connection.php
include('../connection.php');

date_default_timezone_set('Asia/Jakarta'); // Set to your desired time zone

echo "Hello World";
dd(now());
?>