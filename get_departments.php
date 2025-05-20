<?php
include('assets/conn.php'); // Include your database connection

if (isset($_GET['query'])) {
    $query = $_GET['query']; // Get the input from the user

    // Prepare the SQL query to search for departments that match the input
    $sql = "SELECT department_name FROM departments WHERE department_name LIKE ?";
    $stmt = $conn->prepare($sql);
    $param = "%" . $query . "%"; // Use wildcard for partial matches
    $stmt->bind_param("s", $param);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $departments = [];

        // Fetch the results and store in an array
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row['department_name'];
        }

        // Return the departments as JSON
        echo json_encode($departments);
    } else {
        echo json_encode([]);
    }
    
    $stmt->close();
    $conn->close();
}
?>
