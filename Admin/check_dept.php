<?php
include '../assets/conn.php'; // Include your database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['school_id']) && isset($_POST['department'])) {
    $school_id = trim($_POST['school_id']);
    $department = trim($_POST['department']);

    $query = "SELECT COUNT(*) FROM departments WHERE school_id = ? AND department_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $school_id, $department);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "exists";
    } else {
        echo "available";
    }
}
?>
