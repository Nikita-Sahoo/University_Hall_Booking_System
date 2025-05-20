<?php
include('assets/conn.php'); // Database connection

// Check if required fields are set
if (!isset($_POST['booking_ids']) || !isset($_POST['reason'])) {
    die("Missing required fields.");
}

$booking_ids = $_POST['booking_ids']; // Expecting an array of booking IDs
$cancellation_reason = trim($_POST['reason']); // Get reason

if (!is_array($booking_ids) || count($booking_ids) === 0) {
    die("Invalid booking selection.");
}

// Use prepared statement for security
$stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled', cancellation_reason = ? WHERE booking_id = ?");

foreach ($booking_ids as $booking_id) {
    $stmt->bind_param("si", $cancellation_reason, $booking_id);
    $stmt->execute();
}

// Check if any rows were affected
if ($stmt->affected_rows > 0) {
    echo "success";
} else {
    echo "Error updating record: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
