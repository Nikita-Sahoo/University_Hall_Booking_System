<?php
// Include database connection
if (!file_exists('../assets/conn.php')) {
    die("Database connection file is missing.");
}
include '../assets/conn.php';
include '../assets/header_1.php';

// Fetch all school data
if (isset($conn)) {
    $sql = "SELECT * FROM schools ORDER BY school_id DESC";
    $result = mysqli_query($conn, $sql);
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
                            <h1 style="color:#170098;">School Details</h1>
                        </center>
                        <div class="row">
                            <div class="col-8">
                                <div style="display: flex; align-items: center; gap: 10px; margin: 20px;">
                                    <!-- Modify (Blue) -->
                                    <?php if ($user_role == 'admin'): ?>
                                    <button onclick="modifySelected()" class="icon-button blue-button">
                                        <i class="fa-solid fa-pen-to-square"></i> Modify
                                    </button>
                                    <?php endif; ?>
                                    <!-- Departments (Green) -->
                                    <button onclick="departmentPopup()" class="icon-button green-button">
                                        <i class="fa-solid fa-building"></i>Departments
                                    </button>
                                    <?php if ($user_role == 'admin'): ?>
                                    <!-- Achieve Selected (Red) -->
                                    <button onclick="deleteSchool()" class="icon-button red-button">
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

                        <!-- Table -->
                        <div class="table-container" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-bordered" id="bookingTable">
                                <!-- Fixed thead -->
                                <thead style="position: sticky; top: 0; background-color: white; z-index: 1;">
                                    <tr>
                                        <th>Select</th>
                                        <th>School Name</th>
                                        <th>Dean name</th>
                                        <th>Dean Contacts</th>
                                        <!-- <th>Status</th> -->
                                        <!-- <th style="width:20%;">Actions</th> -->
                                    </tr>
                                </thead>

                                <!-- Scrollable tbody -->

                                <tbody>
                                    <?php if (mysqli_num_rows($result) > 0):
                                        // $i = 1;
                                        while ($school = mysqli_fetch_assoc($result)): ?>
                                            <tr>

                                            <td> <input type="checkbox" style="width: 20px; height: 20px; margin: 50% 30%;" class="hall-checkbox" value="<?php echo $school['school_id']; ?>" onclick="handleCheckbox(this)"></td>
                                            <td><?php echo htmlspecialchars($school['school_name']); ?></td>
                                                <td><b  style="color: #007bff;"><?php echo htmlspecialchars($school['incharge_name']); ?></b><br>
                                            
                                                <?php if($school['incharge_status'] == "Incharge"){
                                                     echo htmlspecialchars($school['designation']);?> <span style="color:#dc3545">(i/c)</span>
                                                     <?php
                                                      }
                                                      else {
                                                        echo htmlspecialchars($school['designation']);
                                                      } ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($school['incharge_email']); ?><br>
                                                    <!-- ?php echo htmlspecialchars($school['incharge_contact_number']); ?> <br> -->
                                                    <?php echo htmlspecialchars($school['incharge_intercom']); ?></td>
                                                <!-- <td><?php echo htmlspecialchars($school['incharge_status']); ?></td> -->
                                                <!-- <td style="width:19%; padding-left: 30px;">
                                                        <a href="view_dept.php?school_id=<?php echo $school['school_id']; ?>" class="btn btn-outline-secondary btn-sm mb-2" style="padding: 5px 15px; font-size:medium;">Departments</a>
                                                        <a href="modify_school.php?school_id=<?php echo $school['school_id']; ?>" class="btn btn-outline-primary btn-sm" style="padding: 5px 15px; font-size:medium;">Update</a>
                                                        <a href="delete_scl.php?school_id=<?php echo $school['school_id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this school?');" style="padding: 5px 15px; font-size:medium ;">Delete</a>
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
        // if (!confirm('Are you sure you want to modify the selected school?')) {
        //     return;
        // }
        const hallId = selected[0].value;
        window.location.href = `modify_school.php?id=${hallId}`;
    }

    function departmentPopup() {
        const selected = document.querySelectorAll('.hall-checkbox:checked');

        if (selected.length === 0) {
            alert("Please select a school.");
            return;
        }

        if (selected.length > 1) {
            alert("Select only one school at a time.");
            return;
        }
        // if (!confirm('Are you sure you want to modify the selected school?')) {
        //     return;
        // }
        const hallId = selected[0].value;
        window.location.href = `view_dept.php?id=${hallId}`;
    }

    function deleteSchool() {
        const selected = document.querySelectorAll('.hall-checkbox:checked');

        if (selected.length === 0) {
            alert("Please select a school to delete.");
            return;
        }

        // if (selected.length > 1) {
        //     alert("You can modify only one school at a time.");
        //     return;
        // }
        if (!confirm('Are you sure you want to delete all the selected school?')) {
            return;
        }
        const hallId = selected[0].value;
        window.location.href = `delete_scl.php?scl_id=${hallId}`;
    }
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



</html>