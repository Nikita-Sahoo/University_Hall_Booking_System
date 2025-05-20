<?php
include '../assets/conn.php'; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve data from AJAX request


    // print_r($_POST);
    // exit;

    $hall_id = isset($_POST['hall_id']) ? intval($_POST['hall_id']) : 0;
    $belongs_to = isset($_POST['belongs_to']) ? trim($_POST['belongs_to']) : "";

    // Remove spaces from POST keys
    $school_id = isset($_POST['school_id']) && $_POST['school_id'] !== "" ? intval($_POST['school_id']) : null;
    $department_id = isset($_POST['department_id']) && $_POST['department_id'] !== "" ? intval($_POST['department_id']) : null;
    $section_id = isset($_POST['section_id']) && $_POST['section_id'] !== "" ? intval($_POST['section_id']) : null;

    if ($hall_id == 0 || empty($belongs_to)) {
        echo "Invalid Hall ID or ownership type.";
        exit;
    }

    
    // print_r($_POST);
    // exit;

    // Define SQL query
    $sql = "";
    $stmt = null;

    if ($belongs_to === "Department" && $school_id && $department_id) {
        $sql = "UPDATE hall_details SET belongs_to = ?, school_id = ?, department_id = ?, section_id = NULL WHERE hall_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siii", $belongs_to, $school_id, $department_id, $hall_id);
    } elseif ($belongs_to === "School" && $school_id) {
        $sql = "UPDATE hall_details SET belongs_to = ?, school_id = ?, department_id = NULL, section_id = NULL WHERE hall_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $belongs_to, $school_id, $hall_id);
    } elseif ($belongs_to === "Administration" && $section_id) {
        $sql = "UPDATE hall_details SET belongs_to = ?, school_id = NULL, department_id = NULL, section_id = ? WHERE hall_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $belongs_to, $section_id, $hall_id);
    } else {
        echo "Invalid ownership type or missing data.";
        exit;
    }

    // Execute the query
    if ($stmt && $stmt->execute()) {
        echo "Ownership transferred successfully.";
    } else {
        echo "Error updating ownership: " . $conn->error;
    }

    // Close connection
    if ($stmt) {
        $stmt->close();
    }
    $conn->close();
} else {
    echo "Invalid request method.";
}


?>
