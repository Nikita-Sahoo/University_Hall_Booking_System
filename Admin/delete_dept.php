<?php
include '../assets/conn.php';

if (isset($_GET['dept_ids']) && !empty($_GET['dept_ids']) && isset($_GET['scl_id']) && !empty($_GET['scl_id'])) {
    $departmentIds = explode(',', $_GET['dept_ids']); // Convert comma-separated IDs to an array
    $schoolId = $_GET['scl_id'];

    // Prepare the SQL statement to delete multiple departments
    $sql = "DELETE FROM departments WHERE department_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $success = true;
        foreach ($departmentIds as $deptId) {
            $stmt->bind_param("i", $deptId);
            if (!$stmt->execute()) {
                $success = false;
                break;
            }
        }

        if ($success) {
            // Redirect back with success message
            header("Location: view_dept.php?id=$schoolId&msg=Departments Deleted Successfully");
            exit();
        } else {
            // Redirect back with error message
            header("Location: view_dept.php?id=$schoolId&msg=Error Deleting Departments");
            exit();
        }
    } else {
        // Redirect back with error message
        header("Location: view_dept.php?id=$schoolId&msg=Database Error");
        exit();
    }
} else {
    // Redirect back with error message
    header("Location: view_dept.php?id=$schoolId&msg=Invalid Department IDs");
    exit();
}
?>