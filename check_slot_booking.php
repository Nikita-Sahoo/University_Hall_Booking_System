<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
include('assets/conn.php');

if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Get POST data
$day_of_week = $_POST['day_of_week'] ?? '';
$slots = isset($_POST['slots']) ? explode(',', $_POST['slots']) : []; // Convert CSV slots into an array
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$hall_id = $_POST['hall_id'] ?? '';

if (empty($day_of_week) || empty($slots) || empty($start_date) || empty($end_date) || empty($hall_id)) {
    echo json_encode(["error" => "All fields are required."]);
    exit;
}

$error = [];
$uniqueBookings = [];

$query = "SELECT booking_id_gen, purpose_name, slot_or_session FROM bookings 
          WHERE hall_id = ? 
          AND day_of_week = ? 
          AND status = 'pending'
          AND (
              (start_date <= ? AND end_date >= ?) OR 
              (start_date >= ? AND start_date <= ?)
          )";

if ($stmt = $conn->prepare($query)) {
    // Bind parameters
    $stmt->bind_param("isssss", $hall_id, $day_of_week, $end_date, $start_date, $start_date, $end_date);
    if (!$stmt->execute()) {
        error_log("Query execution failed: " . $stmt->error);
        echo json_encode(["error" => "Error executing query."]);
        exit;
    }

    $result = $stmt->get_result();

    // Process conflicts
    while ($row = $result->fetch_assoc()) {
        $booking_id_gen = $row['booking_id_gen'];
        $booked_slots = explode(',', $row['slot_or_session']); // Convert stored slots to array

        // Check if any booked slot matches the requested slots
        foreach ($slots as $slot) {
            if (in_array($slot, $booked_slots)) {
                $uniqueBookings["$booking_id_gen-$slot"] = "Class <b>{$row['purpose_name']}</b> is already approved for slot <b>{$slot}</b>";
            }
        }
    }

    $stmt->close();
} else {
    error_log("Query preparation failed: " . $conn->error);
    echo json_encode(["error" => "Error preparing the query."]);
    exit;
}

// Format error message if conflicts exist
if (!empty($uniqueBookings)) {
    $error = implode("<br>", array_values($uniqueBookings));
}

echo json_encode(["error" => $error]);
?>