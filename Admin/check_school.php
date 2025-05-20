<?php
include '../assets/conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // print_r($_POST);
    // exit;
    $school = trim($_POST["school"]);

    $stmt = $conn->prepare("SELECT * FROM schools WHERE school_name = ?");
    $stmt->bind_param("s", $school);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "exists"; // School already exists
    } else {
        echo "not_exists"; // School is unique
    }
}
?>
