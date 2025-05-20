<?php
require 'assets/conn.php'; // Include your database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $hall_id = $_POST['hall_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $day_of_week = $_POST['day_of_week'];
    $slots = implode(',', $_POST['slots']);
    $purpose = $_POST['purpose'];
    $event_type = $_POST['event_type'];
    $purpose_name = $_POST['purpose_name'];
    $organiser_name = $_POST['organiser_name'];
    $organiser_id = $_POST['employee_id'];
    $organiser_department = $_POST['organiser_department'];
    $organiser_mobile = $_POST['organiser_mobile'];
    $organiser_email = $_POST['organiser_email'];
    $booking_date = date('Y-m-d');
    $status = 'approved'; // Default status is approved

    $typeMapping = [
        1 => 'S', // Seminar Hall
        2 => 'A', // Auditorium
        3 => 'L', // Lecture Hall
        4 => 'C'  // Complex
    ];

    // Fetch hall details
    $hallDetailsQuery = "SELECT department_id, school_id, section_id, type_id FROM hall_details WHERE hall_id = ?";
    $hallStmt = $conn->prepare($hallDetailsQuery);
    $hallStmt->bind_param("i", $hall_id);
    $hallStmt->execute();
    $hallResult = $hallStmt->get_result();

    if ($hallResult->num_rows > 0) {
        $hallData = $hallResult->fetch_assoc();

        // Determine identifier
        $identifier = $hallData['department_id'] ?? $hallData['school_id'] ?? $hallData['section_id'] ?? '00';

        // Get type letter
        $type_id = $hallData['type_id'];
        $type_letter = $typeMapping[$type_id] ?? 'X'; // Default to 'X' if type_id is invalid
    } else {
        die("Error: Hall details not found.");
    }

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

    // Convert start and end dates to DateTime objects
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    $conflictingDates = [];
    $approvedDates = [];
    
    while ($start <= $end) {
        if ($start->format('l') == $day_of_week) { // Match selected weekday
            $formatted_date = $start->format('Y-m-d');
    
            // Convert user-selected slots into an array
            $selected_slots = explode(',', $slots);
            $placeholders = implode(',', array_fill(0, count($selected_slots), '?'));
    
            // Prepare the conflict query
            $conflictQuery = "SELECT * FROM bookings 
                              WHERE hall_id = ? 
                              AND start_date = ? 
                              AND day_of_week = ? 
                              AND status = 'approved' 
                              AND FIND_IN_SET(slot_or_session, ?)";
            
            if (!$conflictStmt = $conn->prepare($conflictQuery)) {
                die("SQL Error: " . $conn->error);
            }
    
            // Bind parameters dynamically
            $types = "isss";  
            $conflictStmt->bind_param($types, $hall_id, $formatted_date, $day_of_week, $slots);
            $conflictStmt->execute();
            $conflictResult = $conflictStmt->get_result();
    
            if ($conflictResult->num_rows > 0) {
                $conflictingDates[] = $formatted_date;
            } else {
                $approvedDates[] = $formatted_date;
            }
        }
        $start->modify('+1 day');
    }
    
    // Insert bookings for approved dates
    foreach ($approvedDates as $date) {
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, hall_id, start_date, end_date, day_of_week, slot_or_session, purpose, event_type, purpose_name, organiser_name, organiser_id, organiser_department, organiser_mobile, organiser_email, booking_date, status, booking_id_gen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        // $status = 'approved'; // Approved by default
        $status = 'pending'; 
        
        $stmt->bind_param("iissssssssissssss", $user_id, $hall_id, $date, $date, $day_of_week, $slots, $purpose, $event_type, $purpose_name, $organiser_name, $organiser_id, $organiser_department, $organiser_mobile, $organiser_email, $booking_date, $status, $booking_id_gen);
        $stmt->execute();
    }

    // Handle conflicts
    if (!empty($conflictingDates)) {
        $conflictMessage = "Warning: Some of your selected dates (" . implode(', ', $conflictingDates) . ") are unavailable due to existing bookings. These dates have been marked as 'rejected'. Would you like to proceed with the other dates?";
        $escapedMessage = addslashes($conflictMessage);
        echo "<script>alert('$escapedMessage'); window.location.href='sem_booking.php';</script>";
    } else {
        echo "<script>alert('Booking successful!'); window.location.href='sem_booking.php';</script>";
        
    }
} else {
    echo "Invalid request!";
}
?>
