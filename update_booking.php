<?php
include('assets/conn.php');

// Debugging POST data
// var_dump($_POST); exit;

// Get the posted form data
$booking_id = $_POST['booking_id'];
$hall_id = $_POST['hall_id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$purpose = $_POST['purpose'];
$purpose_name = $_POST['purpose_name'];
$event_type = $_POST['event_type'];
$students_count = $_POST['students_count'];
$organiser_name = $_POST['organiser_name'];
$organiser_department = $_POST['organiser_department'];
$organiser_mobile = $_POST['organiser_mobile'];
$organiser_email = $_POST['organiser_email'];
$booking_date = $_POST['booking_date'];
$status = 'pending';

// Determine the value of slot_or_session based on the form input
if (isset($_POST['booking_type']) && $_POST['booking_type'] == 'session') {
    if (isset($_POST['session_choice'])) {
        $session_choice = $_POST['session_choice'];
        switch ($session_choice) {
            case 'fn':
                $slot_or_session = '1,2,3,4'; // Forenoon slots
                break;
            case 'an':
                $slot_or_session = '5,6,7,8'; // Afternoon slots
                break;
            case 'both':
                $slot_or_session = '1,2,3,4,5,6,7,8'; // Both sessions
                break;
            default:
                $slot_or_session = '';
        }
    } else {
        $slot_or_session = ''; // No session selected
    }
} elseif (isset($_POST['booking_type']) && $_POST['booking_type'] == 'slot') {
    if (isset($_POST['slots'])) {
        $slots = $_POST['slots']; // An array of selected slots
        $slot_or_session = implode(',', $slots); // Convert array to a comma-separated string
    } else {
        $slot_or_session = ''; // No slots selected
    }
} else {
    $slot_or_session = ''; // Default value if no booking type is selected
}

// Function to check availability
function isHallAvailable($conn, $hall_id, $start_date, $end_date, $booking_id) {
    $query = "SELECT COUNT(*) AS is_booked 
    FROM bookings 
    WHERE hall_id = ? 
    AND booking_id != ? 
    AND status IN ('approved', 'booked') 
    AND (
          (start_date <= ? AND end_date >= ?) OR  
          (start_date >= ? AND start_date <= ?) OR
          (end_date >= ? AND end_date <= ?)
        )";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssss", $hall_id, $booking_id, $end_date, $start_date, $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['is_booked'] == 0; // true if available (count == 0)
}

// Check if the hall is available
if (isHallAvailable($conn, $hall_id, $start_date, $end_date, $booking_id)) {
    // Hall is available, proceed with the update
    $sql = "UPDATE bookings SET 
                hall_id = ?, 
                start_date = ?, 
                end_date = ?, 
                purpose = ?, 
                purpose_name = ?,
                event_type = ?,
                students_count = ?, 
                organiser_name = ?, 
                organiser_department = ?, 
                organiser_mobile = ?, 
                organiser_email = ?, 
                slot_or_session = ?, 
                booking_date = ?, 
                status = ?
            WHERE booking_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssss", 
        $hall_id, 
        $start_date, 
        $end_date, 
        $purpose, 
        $purpose_name,
        $event_type,
        $students_count, 
        $organiser_name, 
        $organiser_department, 
        $organiser_mobile, 
        $organiser_email, 
        $slot_or_session, 
        $booking_date, 
        $status, 
        $booking_id);

    if ($stmt->execute()) {
        // Redirect to the view_modify_bookings.php page
        header("Location: view_modify_booking.php");
        exit();
    } else {
        // Handle errors
        echo "Error updating booking: " . $stmt->error;
    }
} else {
    // Hall is not available; handle the error
    echo "<script>
            alert('Hall is not available for the selected dates. Please choose another date or time.');
            window.history.back();
         </script>";
}

$conn->close();
?>
