<?php
include '../assets/conn.php'; 

if (isset($_POST['school_id'])) {
    $school_id = $_POST['school_id'];

    $sql = "SELECT department_id, department_name FROM departments WHERE school_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo '<option value=""> All Option</option>';
        while ($row = $result->fetch_assoc()) {
            
            echo '<option value="' . $row['department_id'] . '">' . $row['department_name'] . '</option>';
        }
    } else {
        echo '<option value="">No departments available</option>';
    }

    $stmt->close();
}
?>
