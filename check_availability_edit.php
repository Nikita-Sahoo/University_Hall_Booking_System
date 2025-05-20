<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
require 'assets/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hallId = isset($_POST['hall_id']) ? intval($_POST['hall_id']) : 0;
    $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : '';
    $bookingType = isset($_POST['booking_type']) ? $_POST['booking_type'] : '';
    $sessionOrSlots = isset($_POST['session_or_slots']) ? $_POST['session_or_slots'] : '';
    $bookingId = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

    if ($hallId && $startDate && $endDate && $bookingType) {
        $availabilityResult = checkHallAvailability($conn, $hallId, $startDate, $endDate, $bookingType, $sessionOrSlots, $bookingId);
        echo json_encode($availabilityResult);
    } else {
        echo json_encode(['available' => false, 'message' => 'Invalid input parameters']);
    }
} else {
    echo json_encode(['available' => false, 'message' => 'Invalid request method']);
}

function checkHallAvailability($conn, $hall_id, $start_date, $end_date, $booking_type, $session_or_slots, $booking_id) {
    $query = "SELECT slot_or_session FROM bookings 
              WHERE hall_id = ? 
              AND status IN ('approved', 'booked')
              AND booking_id != ? 
              AND (
                    (start_date <= ? AND end_date >= ?) OR
                    (start_date >= ? AND start_date <= ?) OR
                    (end_date >= ? AND end_date <= ?)
                )";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iissssss", $hall_id, $booking_id, $end_date, $start_date, $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $bookedSlots = [];
    while ($row = $result->fetch_assoc()) {
        $bookedSlots = array_merge($bookedSlots, explode(',', $row['slot_or_session']));
    }
    $bookedSlots = array_unique($bookedSlots);

    $requestedSlots = [];
    if ($booking_type === 'session') {
        $requestedSlots = getSessionSlots($session_or_slots);
    } else { // slot
        $requestedSlots = explode(',', $session_or_slots);
    }

    $conflictingSlots = array_intersect($bookedSlots, $requestedSlots);

    if (empty($conflictingSlots)) {
        return ['available' => true, 'message' => 'Hall is available for the selected date/slot.'];
    } else {
        $conflictMessage = 'Hall is not available for the selected date/slot.';
        return ['available' => false, 'message' => $conflictMessage];
    }
}

function getSessionSlots($session) {
    switch ($session) {
        case 'fn':
            return ['1', '2', '3', '4'];
        case 'an':
            return ['5', '6', '7', '8'];
        case 'both':
            return ['1', '2', '3', '4', '5', '6', '7', '8'];
        default:
            return [];
    }
}

if (isset($conn)) {
    $conn->close();
}
?>
