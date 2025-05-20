<?php
require 'assets/conn.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    if ($bookingId && $reason) {
        $query = "UPDATE bookings SET status = 'cancelled', cancellation_reason = ? WHERE booking_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $reason, $bookingId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error cancelling booking.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    }

    $conn->close();
}
?>
