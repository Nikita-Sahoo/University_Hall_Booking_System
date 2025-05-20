<?php
include '../assets/conn.php';

if (isset($_GET['emp_id']) && !empty($_GET['emp_id'])) {
    $scl_id = $_GET['emp_id'];

    // SQL to delete department
    $sql = "DELETE FROM employee WHERE employee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $scl_id);

    if ($stmt->execute()) {
        // Redirect back with success message
        echo "<script>alert('Employee deleted successfully!'); window.location='view_employees.php';</script>";

        // header("Location: view_	employees.php");
        exit();
    } else {
        // Redirect back with error message
        header("Location: view_employees.php?msg=Error Deleting School");
        exit();
    }
} else {
    header("Location: view_employees.php?msg=Invalid School ID");
    exit();
}
?>
