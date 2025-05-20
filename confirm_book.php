<?php
include('assets/conn.php'); // Include database connection file
$error_message = '';
$success_message = '';

// Retrieve form data
$user_id = $_POST['user_id'];
$user_role = $_POST['user_role'];
$hall_id = $_POST['hall_id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$slot_or_session = implode(',', $_POST['slots']);
$purpose = $_POST['purpose'];
if (isset($_POST['event_type'])) {
    $event_type = $_POST['event_type'];
}
$purpose_name = $_POST['purpose_name'];
$students_count = $_POST['students_count'];
$organiser_name = $_POST['organiser_name'];
$organiser_id = $_POST['employee_id'];
$organiser_department = $_POST['organiser_department'];
$organiser_mobile = $_POST['organiser_mobile'];
$organiser_email = $_POST['organiser_email'];
$booking_date = date('Y-m-d'); // Current date for booking_date
// Handle file upload
if (isset($_FILES['event_invitation'])) {
    $file = $_FILES['event_invitation'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf']; // Added PDF support
    $max_size = 1048576; // 1MB
    $target_dir = 'image/event/';

        $file_name = basename($file['name']);
        $target_file = $target_dir . $file_name;

        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true); // Create directory if it doesn't exist
        }

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            // Insert file path into the database or other logic here
            $event_image = $target_file;
        } 
    }

    if (strtolower($purpose) == 'class') {
        $event_type = null; // or you can explicitly set it to NULL if needed
    }
// Check if the hall is already booked for the selected time slots
$checkQuery = "SELECT * FROM bookings WHERE hall_id = ? AND start_date = ? AND slot_or_session = ? AND status IN ('approved', 'pending')";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("iss", $hall_id, $start_date, $slot_or_session);
$checkStmt->execute();
$result = $checkStmt->get_result();


// Map type_id to letters
$typeMapping = [
    1 => 'S', // Seminar Hall
    2 => 'A', // Auditorium
    3 => 'L', // Lecture Hall
    4 => 'C'  // Complex
];
$userQuery = "SELECT department_id FROM users WHERE user_id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user_department_id = ($userResult->num_rows > 0) ? $userResult->fetch_assoc()['department_id'] : null;

// Fetch hall details
$hallDetailsQuery = "SELECT department_id, school_id, section_id, type_id FROM hall_details WHERE hall_id = ?";
$hallStmt = $conn->prepare($hallDetailsQuery);
$hallStmt->bind_param("i", $hall_id);
$hallStmt->execute();
$hallResult = $hallStmt->get_result();
$hallData = ($hallResult->num_rows > 0) ? $hallResult->fetch_assoc() : die("Error: Hall details not found.");

// Determine booking status
// Determine booking status
if ($user_role == 'hod' || $user_role == 'dean') {
    $status = 'pending';
} else {
    $status = ($user_department_id == $hallData['department_id']) ? 'pending' : 'allow';
}


// Generate identifier
$identifier = $hallData['department_id'] ?? $hallData['school_id'] ?? $hallData['section_id'] ?? '00';

    // Get type letter
    $type_id = $hallData['type_id'];
    $type_letter = $typeMapping[$type_id] ?? 'X'; // Default to 'X' if type_id is invalid


// Get current year and month
$currentYearMonth = date("ym");

// Fetch the latest booking for the same type and month
$latestBookingQuery = "SELECT booking_id_gen FROM bookings WHERE booking_id_gen LIKE CONCAT(?, '%') ORDER BY booking_id_gen DESC LIMIT 1";
$latestBookingStmt = $conn->prepare($latestBookingQuery);
$bindingParam = $currentYearMonth . $type_letter; // Concatenate values for binding
$latestBookingStmt->bind_param("s", $bindingParam);
$latestBookingStmt->execute();
$latestBookingResult = $latestBookingStmt->get_result();

if ($latestBookingResult->num_rows > 0) {
    $latestBooking = $latestBookingResult->fetch_assoc();
    $latestNumber = (int)substr($latestBooking['booking_id_gen'], -3);
    $newNumber = $latestNumber + 1;
} else {
    $newNumber = 1;
}

// Generate booking ID
$booking_id_gen = sprintf("%s%s%03d", $currentYearMonth, $type_letter, $newNumber);
$insertQuery = "INSERT INTO bookings (
    booking_id_gen, user_id, hall_id, start_date, end_date, purpose, event_type, purpose_name, 
    students_count, organiser_id, organiser_name, organiser_department, 
    organiser_mobile, organiser_email, slot_or_session, booking_date, status, event_image
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bind_param(
    "siisssssiissssssss",
    $booking_id_gen,
    $user_id,
    $hall_id,
    $start_date,
    $end_date,
    $purpose,
    $event_type,
    $purpose_name,
    $students_count,
    $organiser_id,
    $organiser_name,
    $organiser_department,
    $organiser_mobile,
    $organiser_email,
    $slot_or_session,
    $booking_date,
    $status,
    $event_image
);
if ($insertStmt->execute()) {
    $queryString = http_build_query([
        'booking_id_gen' => $booking_id_gen,
        'user_id' => $user_id,
        'hall_id' => $hall_id,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'purpose' => $purpose,
        'event_type' => $event_type,
        'purpose_name' => $purpose_name,
        'students_count' => $students_count,
        'organiser_id' => $organiser_id,
        'organiser_name' => $organiser_name,
        'organiser_department' => $organiser_department,
        'organiser_mobile' => $organiser_mobile,
        'organiser_email' => $organiser_email,
        'slot_or_session' => $slot_or_session,
        'booking_date' => $booking_date,
        'status' => $status
    ]);

    // Display the alert with the required details
    echo "<script>
        window.location.href = 'test.php?$queryString';
        alert('Your booking has been successfully submitted!\\nBooking Date: $start_date - $end_date\\nOrganiser: $organiser_name');
    </script>";
exit();} else {
    echo "Error: " . $insertStmt->error;
}

// Close statements and connection
$checkStmt->close();
$insertStmt->close();
$conn->close();
?>
