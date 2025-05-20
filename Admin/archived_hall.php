
<?php
header('Content-Type: application/json'); // ✅ Ensure JSON response
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../assets/conn.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON input"]);
    exit;
}

$hall_id = $data['hall_id'] ?? '';
$status = $data['status'] ?? '';
$reason = $data['reason'] ?? '';

if (empty($hall_id) || empty($status)) {
    echo json_encode(["success" => false, "message" => "Hall ID and Status are required"]);
    exit;
}

// ✅ Check if `hall_id` is an array and process correctly
if (is_array($hall_id)) {
    $hall_id = implode(",", array_map('intval', $hall_id)); // Convert array to comma-separated values
}

// ✅ Debugging: Remove print_r() or var_dump()
if ($status !== "Active" && $reason !== "") {
    $query = "UPDATE hall_details SET status = ?, availability = ? WHERE hall_id IN ($hall_id)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $status, $reason);
    $result = $stmt->execute();

    if ($result) {
        echo json_encode(["success" => true, "message" => "Halls archived successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update"]);
    }
} else {
    echo "Status is active, no update performed.";
}



$stmt->close();
$conn->close();
?>
