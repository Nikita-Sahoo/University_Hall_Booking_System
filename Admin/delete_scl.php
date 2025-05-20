<?php
include '../assets/conn.php';

if (isset($_GET['scl_id']) && !empty($_GET['scl_id'])) {
    $scl_id = $_GET['scl_id'];

    // SQL to delete department
    $sql = "DELETE FROM schools WHERE school_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $scl_id);

    if ($stmt->execute()) {
        // Redirect back with success message
        header("Location: view_school.php?msg=School Deleted Successfully");
        exit();
    } else {
        // Redirect back with error message
        header("Location: view_school.php?msg=Error Deleting School");
        exit();
    }
} else {
    header("Location: view_school.php?msg=Invalid School ID");
    exit();
}
?>
