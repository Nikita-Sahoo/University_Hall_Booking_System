<?php
// Include database connection
if (!file_exists('../assets/conn.php')) {
    die("Database connection file is missing.");
}
include '../assets/conn.php';
include '../assets/header_1.php';
// Fetch all school data
if (isset($conn)) {
    // $sql = "SELECT * From employee"; 

    $sql = "SELECT employee.*, 
               schools.school_name, 
               departments.department_name, 
               section.section_name
        FROM employee
        LEFT JOIN schools ON employee.school_id = schools.school_id
        LEFT JOIN departments ON employee.department_id = departments.department_id
        LEFT JOIN section ON employee.section_id = section.section_id;";

    $result = mysqli_query($conn, $sql);
    // print_r($result);
    //         exit;
} else {
    die("Database connection is not established.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/design1.css" />
    <style>
        .modal-header {
            background-color: #007bff;
            color: white;
        }

        .table-wrapper {
            overflow-x: auto;
            max-width: 100%;
        }

        thead th {
            text-align: center;
            background-color: #f4f4f4;
            padding: 10px;
        }

        .container1 {
            width: calc(100%);
            max-width: none;
            justify-content: space-between;
            align-items: center;
            padding: 0 2%;
        }

        .card {
            border: none;
        }

        /* icon button  */
        .icon-button {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            background-color: transparent;
            border: 2px solid;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .icon-button i {
            font-size: 16px;
        }

        /* Red Button */
        .red-button {
            color: #dc3545;
            border-color: #dc3545;
        }

        .red-button:hover {
            background-color: #dc3545;
            color: white;
        }

        /* Yellow Button */
        .yellow-button {
            color: orange;
            border-color: orange;
        }

        .yellow-button:hover {
            background-color: orange;
            color: white;
        }

        /* Blue Button */
        .blue-button {
            color: #007bff;
            border-color: #007bff;
        }

        .blue-button:hover {
            background-color: #007bff;
            color: white;
        }

        /* Green Button */
        .green-button {
            color: green;
            border-color: green;
        }

        .green-button:hover {
            background-color: green;
            color: white;
        }
    </style>
    <title>Admin Home</title>
</head>

<body>
    <div id="main">
        <div class="container1 mt-3">
            <div class="table-wrapper">
                <div class="card">
                    <div class="card-body">
                        <center>
                            <h1 style="color:#170098;">Employee Details</h1>
                        </center>

                        <div style="display: flex; align-items: center; gap: 10px; margin: 20px;">
                            <!-- Modify (Blue) -->
                            <button onclick="modifySelected()" class="icon-button blue-button">
                                <i class="fa-solid fa-pen-to-square"></i> Modify
                            </button>
                            <button onclick="deleteEmployee()" class="icon-button red-button">
                                <i class="fa-solid fa-box-archive"></i> Delete
                            </button>
                            <button id="multiSelectToggle" onclick="toggleMultiSelect()" style="padding: 5px 10px; border: none; border-radius: 5px; background-color: #ccc; cursor: pointer;">
                                Off
                            </button>
                        </div>

                        <!-- Table -->
                        <div class="table-container" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-bordered" id="bookingTable">

                                <!-- Fixed thead -->
                                <thead style="position: sticky; top: 0; background-color: white; z-index: 1;">
                                    <tr>
                                        <th>Select</th>
                                        <th>Employee Details</th>
                                        <th>Designation</th>
                                        <th>Office</th>
                                        <th>Status</th>
                                        <!-- <th style="width:20%;">Actions</th> -->
                                    </tr>
                                </thead>


                                <!-- Scrollable tbody -->

                                <tbody>
                                    <?php if (mysqli_num_rows($result) > 0):
                                        // $i = 1;
                                        while ($employee = mysqli_fetch_assoc($result)): ?>
                                            <tr>

                                                <td style="width:12%;">
                                                    <input type="checkbox" style="width: 20px; height: 20px; margin: 30% 30%;" class="hall-checkbox" value="<?php echo $employee['employee_id']; ?>" onclick="handleCheckbox(this)">
                                                </td>
                                                <td style="width:18%; "><b style="color: #007bff;"><?php echo htmlspecialchars($employee['employee_name']); ?></b><br>
                                                    <?php echo htmlspecialchars($employee['employee_email']); ?><br>
                                                    <?php echo htmlspecialchars($employee['employee_mobile']); ?> </td>
                                                <td style="width:12%;"><?php echo htmlspecialchars($employee['designation']); ?></td>
                                                <td style="width:24%;">
                                                    <!-- <b style="color: #007bff;"> -->
                                                    <?php
                                                    $officeDetails = [];

                                                    if (!empty($employee['department_id'])) {
                                                        $officeDetails[] = "<b style='color: #007bff;'>Department</b> " . "<br>" . htmlspecialchars($employee['department_name']);
                                                    }
                                                    if (!empty($employee['school_id']) && !empty($employee['department_id'])) {
                                                        $officeDetails[] = "<b style='color: #007bff;'>School</b> " . "<br>" . htmlspecialchars($employee['school_name']);
                                                    }
                                                    if (!empty($employee['school_id']) && empty($employee['department_id'])) {
                                                        $officeDetails[] = "<b style='color: #007bff;'>School</b> " . "<br>" . htmlspecialchars($employee['school_name']);
                                                    }
                                                    if (!empty($employee['section_id'])) {
                                                        $officeDetails[] = "<b style='color: #007bff;'>Section</b> " . "<br>" . htmlspecialchars($employee['section_name']);
                                                    }

                                                    echo implode("<br>", $officeDetails);
                                                    ?>
                                                    <!-- </b> -->
                                                </td>

                                                <td style="width:17%;"><?php echo htmlspecialchars($employee['status']); ?></td>
                                                <!-- <td style="width:19%; padding-left: 30px;">
                                                        <a href="view_dept.php?school_id=?php echo $school['school_id']; ?>" class="btn btn-outline-secondary btn-sm mb-2" style="padding: 5px 15px; font-size:medium;">Departments</a>
                                                        <a href="modify_school.php?school_id=?php echo $school['school_id']; ?>" class="btn btn-outline-primary btn-sm" style="padding: 5px 15px; font-size:medium;">Update</a>
                                                        <a href="delete_scl.php?school_id=?php echo $school['school_id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this school?');" style="padding: 5px 15px; font-size:medium ;">Delete</a>
                                                    </td> -->
                                            </tr>
                                        <?php
                                        endwhile;
                                        ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No schools available</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../assets/footer.php'; ?>

</body>
<script>
    function modifySelected() {
        const selected = document.querySelectorAll('.hall-checkbox:checked');

        if (selected.length === 0) {
            alert("Please select a school to modify.");
            return;
        }

        if (selected.length > 1) {
            alert("You can modify only one school at a time.");
            return;
        }
        // if (!confirm('This page not in working now.')) {
        //     return;
        // }
        const hallId = selected[0].value;
        // window.location.href = `view_employees.php`;

        window.location.href = `modify_employee.php?employee_id=${hallId}`;
    }

    let isMultiSelectEnabled = false;

    function toggleMultiSelect() {
        const toggleButton = document.getElementById('multiSelectToggle');
        isMultiSelectEnabled = !isMultiSelectEnabled;

        if (isMultiSelectEnabled) {
            toggleButton.textContent = 'On';
            toggleButton.style.backgroundColor = '#4CAF50';
            toggleButton.style.color = '#fff';
        } else {
            toggleButton.textContent = 'Off';
            toggleButton.style.backgroundColor = '#ccc';
            toggleButton.style.color = '#000';
        }

        const checkboxes = document.querySelectorAll('.hall-checkbox');
        checkboxes.forEach((cb) => {
            cb.checked = false;
            cb.disabled = false;
        });
    }

    // Function to handle checkbox clicks
    function handleCheckbox(checkbox) {
        const checkboxes = document.querySelectorAll('.hall-checkbox');

        if (!isMultiSelectEnabled) {
            if (checkbox.checked) {
                checkboxes.forEach((cb) => {
                    if (cb !== checkbox) {
                        cb.disabled = true;
                    }
                });
            } else {
                checkboxes.forEach((cb) => {
                    cb.disabled = false;
                });
            }
        }
    }

    function deleteEmployee() {
        const selected = document.querySelectorAll('.hall-checkbox:checked');

        if (selected.length === 0) {
            alert("Please select an Employee to delete.");
            return;
        }

        // if (selected.length > 1) {
        //     alert("You can modify only one school at a time.");
        //     return;
        // }
        if (!confirm('Are you sure you want to delete all the selected Employees?')) {
            return;
        }
        const hallId = selected[0].value;
        window.location.href = `delete_emp.php?emp_id=${hallId}`;
    }
</script>


</html>