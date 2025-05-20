<?php
// Check if the 'id' parameter is set in the URL
if (isset($_GET['id'])) {
    // Include database connection
    include 'assets/conn.php'; // Adjust the path if necessary

    // Get the booking ID from the query string
    $booking_id = $_GET['id'];

    // SQL to delete the booking
    $sql = "DELETE FROM bookings WHERE booking_id = ?";

    // Prepare the statement
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $booking_id);
        
        // Execute the query
        if ($stmt->execute()) {
            echo "Booking deleted successfully!";
            // Optionally, redirect back to the bookings list or confirmation page
            header("Location: view_modify_booking.php"); // Change this to your bookings list page
            exit;
        } else {
            echo "Error deleting booking: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }

    $conn->close();
} else {
    echo "No booking ID provided.";
}
?>
