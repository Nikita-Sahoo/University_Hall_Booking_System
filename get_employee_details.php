<?php
include('assets/conn.php'); // Include your connection file

header('Content-Type: application/json');

$employeeId = $_GET['employee_id'];

// Prepare and execute the query to fetch employee details
try {
    $query = "SELECT * FROM employee WHERE employee_id = ?";
    
    // Prepare the SQL statement
    $stmt = $conn->prepare($query);
    
    // Bind the employeeId parameter to the query
    $stmt->bind_param("i", $employeeId);  // "i" indicates an integer type
    
    // Execute the query
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();
    
    // Check if the employee exists
    if ($result->num_rows > 0) {
        // Fetch the employee data
        $employee = $result->fetch_assoc();
        echo json_encode($employee);
    } else {
        echo json_encode(['error' => 'Employee not found']);
    }

} catch (Exception $e) {
    // Catch any exception and send an error message
    echo json_encode(['error' => 'Error fetching employee details']);
}
?>
