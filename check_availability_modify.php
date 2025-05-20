<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require 'assets/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hallId = isset($_POST['hall_id']) ? intval($_POST['hall_id']) : 0;
    $organiserId = isset($_POST['organiser_id']) ? intval($_POST['organiser_id']) : 0;
    $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : '';
    $bookingType = isset($_POST['booking_type']) ? $_POST['booking_type'] : '';
    $sessionOrSlots = isset($_POST['session_or_slots']) ? $_POST['session_or_slots'] : '';

    if ($hallId && $organiserId && $startDate && $endDate && $bookingType) {
        $availabilityResult = checkHallAvailability($conn, $hallId, $organiserId, $startDate, $endDate, $bookingType, $sessionOrSlots);
        echo json_encode($availabilityResult);
    } else {
        echo json_encode(['available' => false, 'message' => 'Invalid input parameters']);
    }
} else {
    echo json_encode(['available' => false, 'message' => 'Invalid request method']);
}
function checkHallAvailability($conn, $hall_id, $organiser_id, $start_date, $end_date, $booking_type, $session_or_slots) {
    // Query to check availability (only approved bookings)
    $query = "SELECT slot_or_session FROM bookings 
              WHERE hall_id = ? 
              AND status IN ('approved', 'booked')
              AND (
                  (start_date <= ? AND end_date >= ?) OR
                  (start_date >= ? AND start_date <= ?) OR
                  (end_date >= ? AND end_date <= ?)
              )";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("issssss", $hall_id, $end_date, $start_date, $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $bookedSlots = [];
    while ($row = $result->fetch_assoc()) {
        $bookedSlots = array_merge($bookedSlots, explode(',', $row['slot_or_session']));
    }
    $bookedSlots = array_unique($bookedSlots);

    // Determine requested slots based on the booking type
    $requestedSlots = ($booking_type === 'session') ? getSessionSlots($session_or_slots) : explode(',', $session_or_slots);

    // Compare booked slots with requested slots
    $conflictingSlots = array_intersect($bookedSlots, $requestedSlots);

    if (!empty($conflictingSlots)) {
        // Query for conflicting bookings
        $conflictingBookings = [];
        foreach ($conflictingSlots as $slot) {
            // Find conflicting bookings and generate messages
            $conflictingBookings[] = 'Conflicting slot: ' . $slot;
        }

        return [
            'available' => false,
            'message' => 'Hall is not available for the selected date/slot. <br>' . implode('; ', $conflictingBookings),
            'conflicting_slots' => array_values($conflictingSlots)
        ];
    }

    // Duplicate booking check (same organiser)
    $duplicateQuery = "SELECT booking_id_gen, booking_id, hall_id, slot_or_session, start_date, end_date 
    FROM bookings 
    WHERE organiser_id = ? 
    AND status IN ('approved', 'allow', 'pending')";

$stmt = $conn->prepare($duplicateQuery);
$stmt->bind_param("i", $organiser_id);
$stmt->execute();
$duplicateResult = $stmt->get_result();

$duplicateBookings = [];
while ($row = $duplicateResult->fetch_assoc()) {
// Check for overlapping dates
$bookingStartDate = $row['start_date'];
$bookingEndDate = $row['end_date'];

// Check if the booking date range overlaps with the new booking
if (
($start_date <= $bookingEndDate && $end_date >= $bookingStartDate) || 
($bookingStartDate <= $end_date && $bookingEndDate >= $start_date)
) {
// Check if the booking slots overlap
$bookedSlotsInRow = explode(',', $row['slot_or_session']);
$conflictingSlots = array_intersect($bookedSlotsInRow, explode(',', $session_or_slots));
function getHallName($conn, $hall_id) {
    $query = "SELECT hall_name FROM hall_details WHERE hall_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $hall_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['hall_name'] : 'Unknown Hall';
}

// If there are overlapping slots, mark as a conflict
if (!empty($conflictingSlots)) {
    $duplicateBookings[] = [
        'booking_id' => $row['booking_id'],
        'booking_id_gen' => $row['booking_id_gen'],
        'date' => $row['start_date'] . ' to ' . $row['end_date'],
        'slot' => $row['slot_or_session'],
        'hall_name' => getHallName($conn, $row['hall_id']) 
       ];
       
}
}
}

// If there are duplicate bookings, return the message
if (!empty($duplicateBookings)) {
    $duplicateBookingsMessage = array_map(function($booking) {
        return '<br><b>' . $booking['hall_name'] . '</b> (' . $booking['date'] . ') Slot: <b>' . $booking['slot'] .'</b>';
    }, $duplicateBookings);
    

    return [
        'available' => false,
        'message' => 'Duplicate booking detected in:' . implode('; ', $duplicateBookingsMessage) . '
            <button id="cancel_booking_btn"  class="btn btn-sm btn-outline-danger ms-2" data-booking-id="' . $duplicateBookings[0]['booking_id'] . '">Cancel</button>',
        'duplicate_bookings' => $duplicateBookings
    ];
    

}

return ['available' => true, 'message' => 'Hall is available for the selected date/slot.'];
}

if (isset($conn)) {
    $conn->close();
}
?>
