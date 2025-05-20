<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../assets/conn.php'; // Ensure the database connection file exists and is correct

header('Content-Type: application/json'); // Ensure JSON output

if (!isset($_POST['hall_id']) || empty($_POST['hall_id'])) {
    echo json_encode(["error" => "Invalid hall ID"]);
    exit;
}

$hall_id = $_POST['hall_id'];

$query = "SELECT wifi, ac, projector, computer, audio_system, podium, ramp, smart_board, lift, white_board, blackboard FROM hall_details WHERE hall_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(["error" => "Database query failed: " . $conn->error]);
    exit;
}

$stmt->bind_param("i", $hall_id);
$stmt->execute();
$result = $stmt->get_result();

$features = [];

if ($row = $result->fetch_assoc()) {
    foreach ($row as $feature => $value) {
        if (!empty($value)) {
            $features[] = $value; // Push only non-empty values
        }
    }
}

$stmt->close();
$conn->close();

// Send valid JSON response
echo json_encode($features);
