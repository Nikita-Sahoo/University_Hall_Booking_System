<?php
require 'assets/conn.php'; // Include your DB connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Validate dates
    if ($start_date >= $end_date) {
        die("Error: Start date must be before the end date.");
    }

    // Prepare and execute the SQL query
    $sql = "INSERT INTO semesters (start_date, end_date) VALUES ( ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);

    if ($stmt->execute()) {
        header("Location: home.php");
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close connection
    $stmt->close();
    $conn->close();
}
?>
