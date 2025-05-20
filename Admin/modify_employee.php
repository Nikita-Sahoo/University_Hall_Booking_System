<?php
include '../assets/conn.php'; // Include database connection
if (isset($_GET['employee_id'])) {
    $employee_id = $_GET['employee_id'];

    // Fetch employee data
    $sql = "SELECT * FROM employee WHERE employee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();

    if ($employee) {
        $employee_name = $employee['employee_name'];
        $email = $employee['employee_email'];
        $contact = $employee['employee_mobile'];
        $designation = $employee['designation'];
        $belongs_to = $employee['office'];
        $school_id = $employee['school_id'];
        $department_id = $employee['department_id'];
        $section_id = $employee['section_id'];
    }
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/design1.css" />
    <title>Add Hall</title>
</head>

<body>
    <?php include '../assets/header_1.php'; ?>
    <div id="main">
        <div class="main-content mt-2">
            <?php
            include('../assets/conn.php');

            // Fetch all room types
            $query = "SELECT type_id, type_name FROM hall_type";
            $result = mysqli_query($conn, $query);
            ?>
            <!-- Seminar Hall Form -->
            <form action="" id="myForm" method="post" style="margin:20px 150px; background-color:white; padding: 50px; border-radius:15px;" enctype="multipart/form-data">

                <h1 style="color:#170098; text-align:center;">Update Employee</h1>

                <!-- Hidden Employee ID Field -->
                <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($employee_id); ?>">

                <h4 class="form-section-title">Employee Details</h4>

                <div class="form-group">
                    <label class="form-label" for="employee_name">Employee Name:</label>
                    <input type="text" id="employee_name" name="employee_name" value="<?php echo htmlspecialchars($employee_name); ?>" placeholder="Employee Name">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Email">
                </div>

                <div class="form-group">
                    <label class="form-label" for="contact">Employee Contact:</label>
                    <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($contact); ?>" class="form-control" placeholder="<?php echo htmlspecialchars($contact); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Designation:</label>
                    <input type="text" class="form-control" id="designation" name="designation" value="<?php echo htmlspecialchars($designation); ?>" placeholder="Enter Designation">
                </div>

                <div class="form-group">
                    <label class="form-label mt-2" for="belongs_to">Belongs to:</label>
                    <select name="belongs_to" id="belongs_to" class="form-control" onchange="toggleBelongsTo(this.value)">
                        <option value="">Select</option>
                        <option value="Department" <?php echo ($belongs_to == "Department") ? "selected" : ""; ?>>Department</option>
                        <option value="School" <?php echo ($belongs_to == "School") ? "selected" : ""; ?>>School</option>
                        <option value="Administration" <?php echo ($belongs_to == "Administration") ? "selected" : ""; ?>>Administration</option>
                    </select>
                </div>

                <!-- Department Selection -->
                <div id="departmentFields" style="display: <?php echo ($belongs_to == "Department") ? 'block' : 'none'; ?>;">
                    <label class="form-label" style="margin-top: 8px;">Department :</label>
                    <select name="department_id" class="form-control">
                        <option value="">All Departments</option>
                        <?php
                        $sql = "SELECT department_id, department_name FROM departments";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            $selected = ($department_id == $row['department_id']) ? "selected" : "";
                            echo "<option value='{$row['department_id']}' $selected>{$row['department_name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- School Selection -->
                <div id="schoolField" style="display: <?php echo ($belongs_to == "School") ? 'block' : 'none'; ?>;">
                    <label class="form-label" style="margin-top: 8px;">School:</label>
                    <select name="school_id" class="form-control">
                        <option value="">All Schools</option>
                        <?php
                        $sql = "SELECT school_id, school_name FROM schools";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            $selected = ($school_id == $row['school_id']) ? "selected" : "";
                            echo "<option value='{$row['school_id']}' $selected>{$row['school_name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Section Selection (Administration) -->
                <div id="sectionField" style="display: <?php echo ($belongs_to == "Administration") ? 'block' : 'none'; ?>;">
                    <label class="form-label" style="margin-top: 8px;">Section:</label>
                    <select name="section_id" class="form-control">
                        <option value="">All Sections</option>
                        <?php
                        $sql = "SELECT section_id, section_name FROM section";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            $selected = ($section_id == $row['section_id']) ? "selected" : "";
                            echo "<option value='{$row['section_id']}' $selected>{$row['section_name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <br>

                <input type="submit" style="margin-left: 45%; background-color: #007bff; padding: 12px 40px;" class="btn btn-primary btn-lg" name="update_employee" value="update_employee">
                <!-- <input type="button" class="btn btn-success btn-lg" style="padding: 12px 40px;" onclick="clearForm()" name="clear" value="Clear"> -->
            </form>

        </div>
    </div>
    <!-- <footer>
        <p>&copy; 2024 University Hall Booking System | All Rights Reserved</p>
    </footer> -->


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#school_name').on('change', function() {
                var school_id = $(this).val();

                // Clear the department dropdown
                $('#department_name').html('<option value="">Select Department</option>');

                if (school_id) {
                    $.ajax({
                        type: 'POST',
                        url: 'fetch_department.php', // Corrected script name
                        data: {
                            school_id: school_id
                        },
                        success: function(response) {
                            // Append the fetched department options
                            $('#department_name').html(response);
                        },
                        error: function(xhr, status, error) {
                            // Handle errors (optional)
                            console.log("An error occurred: " + error);
                        }
                    });
                }
            });
        });
    </script>

    <!-- belongs To   -->
    <script>
        function toggleBelongsTo(value) {
            if (value === 'Department') {
                document.getElementById('departmentFields').style.display = 'block';
                document.getElementById('schoolField').style.display = 'none';
                document.getElementById('sectionField').style.display = 'none';
            } else if (value === 'School') {
                document.getElementById('schoolField').style.display = 'block';
                document.getElementById('departmentFields').style.display = 'none';
                document.getElementById('sectionField').style.display = 'none';
            } else if (value === 'Administration') {
                document.getElementById('sectionField').style.display = 'block';
                document.getElementById('departmentFields').style.display = 'none';
                document.getElementById('schoolField').style.display = 'none';
            }
        }
    </script>


    <!-- Clear form data  -->
    <script>
        function clearForm() {
            document.getElementById("myForm").reset(); // Reset all form fields
        }
    </script>

    <!-- side nav  -->
    <script>
        // Get all the dropdown buttons
        var dropdownBtns = document.querySelectorAll(".dropdown-btn");

        // Loop through the buttons to add event listeners
        dropdownBtns.forEach(function(btn) {
            btn.addEventListener("click", function() {
                // Toggle between showing and hiding the active dropdown container
                this.classList.toggle("collapsed");
                var dropdownContainer = this.nextElementSibling;
                if (dropdownContainer.style.display === "block") {
                    dropdownContainer.style.display = "none";
                } else {
                    dropdownContainer.style.display = "block";
                }
            });
        });
    </script>

</body>

</html>

<?php
include '../assets/conn.php'; // Include database connection

if (isset($_POST['update_employee'])) {
    $employee_id = $_POST['employee_id'];
    $employee_name = $_POST['employee_name'];
    $email = $_POST['email'];
    $contact = $_POST['contact']; // Preserve contact number format
    $designation = $_POST['designation'];
    $belongs_to = $_POST['belongs_to'];

    // Initialize school, department, and section variables
    $school_id = NULL;
    $department_id = NULL;
    $section_id = NULL;

    // Assign values only if they exist
    if ($belongs_to == "Department" && !empty($_POST['department_id'])) {
        $department_id = $_POST['department_id'];
        $school_id = NULL; // Reset school
        $section_id = NULL; // Reset section
    } elseif ($belongs_to == "School" && !empty($_POST['school_id'])) {
        $school_id = $_POST['school_id'];
        $department_id = NULL; // Reset department
        $section_id = NULL; // Reset section
    } elseif ($belongs_to == "Administration" && !empty($_POST['section_id'])) {
        $section_id = $_POST['section_id'];
        $school_id = NULL; // Reset school
        $department_id = NULL; // Reset department
    }

    // Prepare SQL query with dynamic fields
    $sql = "UPDATE employee SET employee_name=?, employee_email=?, employee_mobile=?, designation=?, office=?";
    $params = ["sssss", $employee_name, $email, $contact, $designation, $belongs_to];

    if ($department_id !== NULL) {
        $sql .= ", department_id=?";
        $params[0] .= "i";
        $params[] = $department_id;
    }
    if ($school_id !== NULL) {
        $sql .= ", school_id=?";
        $params[0] .= "i";
        $params[] = $school_id;
    }
    if ($section_id !== NULL) {
        $sql .= ", section_id=?";
        $params[0] .= "i";
        $params[] = $section_id;
    }

    $sql .= " WHERE employee_id=?";
    $params[0] .= "i";
    $params[] = $employee_id;

    // Prepare the statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(...$params);

    // Execute the update query
    if ($stmt->execute()) {
        echo "<script>alert('Employee updated successfully!'); window.location='view_employees.php';</script>";
    } else {
        echo "<script>alert('Error updating employee: " . $conn->error . "'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
