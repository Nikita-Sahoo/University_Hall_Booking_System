<?php
include('assets/conn.php');

if (isset($_POST['school_id']) && isset($_POST['type_id'])) {
    $school_id = $_POST['school_id'];
    $type_id = $_POST['type_id'];

    // Fetch departments based on the selected school and hall type
    $query = "SELECT department_id, department_name FROM departments WHERE school_id = '$school_id'";
    $result = mysqli_query($conn, $query);

    echo "<option value=''>All Departments</option>";
    while ($department = mysqli_fetch_assoc($result)) {
        echo "<option value='{$department['department_id']}'>{$department['department_name']}</option>";
    }
}

if (isset($_GET['school_id'])) {
    $school_id = intval($_GET['school_id']); // Ensure it's an integer to prevent SQL injection
    
    $sql = "SELECT department_id, department_name FROM departments WHERE school_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
    
    echo json_encode($departments); // Return JSON response
}
?>
