<?php
include('assets/conn.php'); // Include your database connection

if (isset($_GET['department']) && isset($_GET['query'])) {
    $departmentName = $_GET['department'];
    $query = $_GET['query'];

    // Fetch department_id based on department_name
    $sql = "SELECT department_id FROM departments WHERE department_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $departmentName);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $departmentId = $row['department_id'];

            // Fetch employees based on department_id and query (search for employee_name)
            $sql = "SELECT employee_id, employee_name, employee_email, employee_mobile FROM employee WHERE department_id = ? AND employee_name LIKE ?";
            $stmt2 = $conn->prepare($sql);
            $likeQuery = '%' . $query . '%';
            $stmt2->bind_param("is", $departmentId, $likeQuery);

            if ($stmt2->execute()) {
                $result2 = $stmt2->get_result();
                $employees = [];
                while ($row2 = $result2->fetch_assoc()) {
                    $employees[] = [
                        'employee_id' => $row2['employee_id'],
                        'employee_email' => $row2['employee_email'],
                        'employee_mobile' => $row2['employee_mobile'],
                        'employee_name' => $row2['employee_name']
                    ];
                }

                // Return the list of employees as JSON
                echo json_encode($employees);
            } else {
                echo json_encode(['error' => 'Error fetching employees.']);
            }
        } else {
            echo json_encode(['error' => 'Department not found.']);
        }
    } else {
        echo json_encode(['error' => 'Error executing department query.']);
    }

    $stmt->close();
    $conn->close();
}
?>
