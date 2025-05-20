<?php
include '../assets/conn.php'; // Database connection

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['hall_ids']) || !is_array($data['hall_ids'])) {
        echo json_encode(["success" => false, "message" => "Invalid request"]);
        exit;
    }

    $hall_ids = array_map('intval', $data['hall_ids']);  // Sanitize IDs

    // Prepared statement to update only "Active" halls
    $sql = "UPDATE hall_details SET status = 'Active', availability = 'Yes', start_date = '', to_date = '' WHERE hall_id IN (" . implode(",", $hall_ids) . ") AND status = 'Archived'";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . mysqli_error($conn)]);
    }
}
?>
