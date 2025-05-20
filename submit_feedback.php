<?php
include "assets/conn.php"; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $booking_id = $_POST["booking_id"];
    $overall = $_POST["overall"];
    $cleanliness = $_POST["cleanliness"];
    $seating = $_POST["seating"];
    $lighting = $_POST["lighting"];
    $audio = $_POST["audio"];
    $ac = $_POST["ac"];
    $additional_feedback = $_POST["additional_feedback"];

    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO hall_feedback (booking_id, overall_rating, cleanliness, seating, lighting, audio, ac, additional_feedback) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("isssssss", $booking_id, $overall, $cleanliness, $seating, $lighting, $audio, $ac, $additional_feedback);

    // Execute and check if successful
    if ($stmt->execute()) {
        echo "<script>alert('Feedback submitted successfully!'); window.location.href='view_modify_booking.php';</script>";
    } else {
        echo "<script>alert('Error submitting feedback.'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
