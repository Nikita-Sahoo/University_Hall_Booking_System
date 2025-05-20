<?php
include '../assets/conn.php';

if (isset($_GET['id'])) {
    $hall_id = $_GET['id'];

    // Fetch school and department data
    $sql = "SELECT d.department_id, d.department_name, d.incharge_name, d.incharge_email, d.incharge_contact_mobile, 
                   d.incharge_intercom, d.designation, d.incharge_status, s.school_name
            FROM departments d 
            JOIN schools s ON s.school_id = d.school_id
            WHERE d.school_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $hall_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch school name for the header
    $departments = $result->fetch_all(MYSQLI_ASSOC);

    $school_name = !empty($departments) ? $departments[0]['school_name'] : "Unknown School";
}
// } else {
//     die("Invalid access. No school ID provided.");
// }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/design1.css" />
    <title>View Department</title>

    <style>
        .btn-outline-success a {
            color: green;
        }

        .btn-outline-success a:hover {
            color: white;
        }

        h3 {
            font-family: 'Times New Roman', Times, serif;
        }
    </style>
</head>

<body>
    <?php include '../assets/header_1.php'; ?>
    <div id="main">
        <div class="main-content mt-3">
            <div class="table-wrapper">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <center>
                            <h1 style="color:#0e00a3;">Departments Under <?php echo htmlspecialchars($school_name); ?></h1><br>
                        </center>

                        <div class="row">
                            <div class="col-8">
                                <div style="display: flex; align-items: center; gap: 10px; margin: 20px;">
                                    <!-- Modify (Blue) -->
                                    <button onclick="modifyDepartment()" class="icon-button blue-button">
                                        <i class="fa-solid fa-pen-to-square"></i> Modify
                                    </button>


                                    <?php if ($user_role == 'admin'): ?>
                                    <!-- Achieve Selected (Red) -->
                                    <button onclick="deleteDepartment()" class="icon-button red-button">
                                        <i class="fa-solid fa-box-archive"></i> Delete
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-4">
                                <!-- Toggle Button -->
                                <div style=" display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin: 20px;">
                                    <label for="multiSelectToggle">Multiple Selection:</label>
                                    <button id="multiSelectToggle" onclick="toggleMultiSelect()" style="padding: 5px 10px; border: none; border-radius: 5px; background-color: #ccc; cursor: pointer;">
                                        Off
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-container" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-bordered" id="bookingTable">
                                <thead>
                                    <tr>
                                        <th>Select</th>
                                        <th>Department</th>
                                        <th> Name & Designation</th>
                                        <th>Email / Contacts </th>
                                        <!-- <th>Action</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // $i = 1; // Serial number counter
                                    if (!empty($departments)) {
                                        foreach ($departments as $row) {

                                            echo "<tr>";
                                            echo "<td>" . '<input type="checkbox" style="width: 20px; height: 20px; margin: 50% 30%;" class="hall-checkbox" value="' . $row['department_id'] . '" onclick="handleCheckbox(this)">' . "</td>";
                                            echo "<td>" . htmlspecialchars($row['department_name']) . "</td>";
                                            echo "<td> <b  style='color: #007bff;'>"
                                                . htmlspecialchars($row['incharge_name']) . " </b><br>";
                                                
                                                if ($row['incharge_status'] == "Incharge") {
                                                    echo htmlspecialchars($row['designation']) . " <span style='color:#dc3545'>(i/c) </span>";
                                                } else {
                                                    echo htmlspecialchars($row['designation']);
                                                } 
                                                 "</td>";
                                            echo "<td>"
                                                . htmlspecialchars($row['incharge_email']) . "<br>"
                                                . htmlspecialchars($row['incharge_intercom']) . "<br>"
                                                . htmlspecialchars($row['incharge_contact_mobile']) . "</td>";
                                            //     echo "<td style='text-align:center;'>
                                            //     <button class='btn btn-outline-success' style='padding: 5px 15px; margin-top: 12%;'>
                                            //         <a href='modify_dept.php?dept_id=" . htmlspecialchars($row['department_id']) . "' 
                                            //         style='margin: 0; padding: 0;'>
                                            //             <b>Modify</b>
                                            //         </a>
                                            //     </button>
                                            //     <button class='btn btn-outline-danger' style='padding: 5px 20px;  margin-top: 12%;' onclick='showDeleteModal(" . htmlspecialchars($row['department_id']) . ")'>
                                            //         <b>Delete</b>
                                            //     </button>
                                            // </td>";
                                            echo "</tr>";
                                            // $i++;
                                        }
                                    } else {
                                        echo '<tr><td colspan="5">No data found</td></tr>';
                                    }

                                    $stmt->close();
                                    $conn->close();
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Dropdown toggle functionality
        document.querySelectorAll(".dropdown-btn").forEach(function(btn) {
            btn.addEventListener("click", function() {
                this.classList.toggle("collapsed");
                var dropdownContainer = this.nextElementSibling;
                dropdownContainer.style.display = dropdownContainer.style.display === "block" ? "none" : "block";
            });
        });
    </script>



    <!--handle multiple & single selection at a time  -->
    <script>
        let isMultiSelectEnabled = false; // Default to single selection

        // Function to toggle between single and multiple selection
        function toggleMultiSelect() {
            const toggleButton = document.getElementById('multiSelectToggle');
            isMultiSelectEnabled = !isMultiSelectEnabled; // Toggle the state

            // Update button text and style
            if (isMultiSelectEnabled) {
                toggleButton.textContent = 'On';
                toggleButton.style.backgroundColor = '#4CAF50'; // Green for "On"
                toggleButton.style.color = '#fff';
            } else {
                toggleButton.textContent = 'Off';
                toggleButton.style.backgroundColor = '#ccc'; // Gray for "Off"
                toggleButton.style.color = '#000';
            }

            // Reset all checkboxes when toggling
            const checkboxes = document.querySelectorAll('.hall-checkbox');
            checkboxes.forEach((cb) => {
                cb.checked = false; // Uncheck all checkboxes
                cb.disabled = false; // Enable all checkboxes
            });
        }

        // Function to handle checkbox clicks
        function handleCheckbox(checkbox) {
            const checkboxes = document.querySelectorAll('.hall-checkbox');

            if (!isMultiSelectEnabled) {
                if (checkbox.checked) {
                    checkboxes.forEach((cb) => {
                        if (cb !== checkbox) {
                            cb.disabled = true; // Disable other checkboxes
                        }
                    });
                } else {
                    checkboxes.forEach((cb) => {
                        cb.disabled = false; // Re-enable all checkboxes
                    });
                }
            }
        }
    </script>
    <script>
        function modifyDepartment() {
            const selected = document.querySelectorAll('.hall-checkbox:checked');

            if (selected.length === 0) {
                alert("Please select a Department to modify.");
                return;
            }

            if (selected.length > 1) {
                alert("You can modify only one Department at a time.");
                return;
            }
            // if (!confirm('Are you sure you want to modify the selected school?')) {
            //     return;
            // }
            const hallId = selected[0].value;
            window.location.href = `modify_dept.php?id=${hallId}`;
        }



        function deleteDepartment() {
            const selected = document.querySelectorAll('.hall-checkbox:checked');

            if (selected.length === 0) {
                alert("Please select a Department to delete.");
                return;
            }

            if (!confirm('Are you sure you want to delete all the selected Departments?')) {
                return;
            }

            // Collect all selected department IDs
            const departmentIds = Array.from(selected).map(checkbox => checkbox.value);

            // Redirect to delete_dept.php with department IDs and school ID
            const schoolId = <?= json_encode($hall_id) ?>; // Pass the school ID from PHP
            window.location.href = `delete_dept.php?dept_ids=${departmentIds.join(',')}&scl_id=${schoolId}`;
        }
    </script>

</body>

</html>