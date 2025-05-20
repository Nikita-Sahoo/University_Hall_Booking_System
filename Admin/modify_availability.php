<?php
include '../assets/conn.php'; // Ensure you have a connection file

if (isset($_POST['availability_submit']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $hall_id = $_POST['hall_id'];
    $availability = $_POST['available'];
    $reason = isset($_POST['reason-input']) ? $_POST['reason-input'] : '';
    $start_date = isset($_POST['availabilityStart']) ? $_POST['availabilityStart'] : '';
    $to_date = isset($_POST['availabilityEnd']) ? $_POST['availabilityEnd'] : '';

    // If availability is 'Available', set start_date, to_date, and reason to empty strings
    if ($availability === 'Yes') {
        $start_date = '';
        $to_date = '';
        $reason = 'Yes'; // No reason needed when available
    }

    $updateQuery = "UPDATE hall_details SET availability = ?, start_date = ?, to_date = ? WHERE hall_id = ?";

    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('sssi', $reason, $start_date, $to_date, $hall_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "<script>
            alert('Record updated successfully');
            window.location.href = 'view_modify_hall.php';
        </script>";
    } else {
        echo "<script>
            alert('Error updating record: " . $conn->error . "');
            window.location.href = 'view_modify_hall.php';
        </script>";
    }

    $stmt->close();
    $conn->close();
}
?>
