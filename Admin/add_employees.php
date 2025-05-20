<?php
include '../assets/conn.php'; // Include database connection

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submitroom"])) {
    // Retrieve form data
    $employee_name = mysqli_real_escape_string($conn, $_POST['employee_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $designation = mysqli_real_escape_string($conn, $_POST['designation']);
    $belongs_to = mysqli_real_escape_string($conn, $_POST['belongs_to']);
    
    // Handling belongs_to values
    // $school_id = !empty($_POST['school_name']) ? mysqli_real_escape_string($conn, $_POST['school_name']) : NULL;
    // $department_id = !empty($_POST['department_name']) ? mysqli_real_escape_string($conn, $_POST['department_name']) : NULL;
    // $section_id = !empty($_POST['section']) ? mysqli_real_escape_string($conn, $_POST['section']) : NULL;

     // Initialize variables
     $school = null;
     $department = null;
     $section = null;
 
     // Handle 'belongs_to' logic
     if ($_POST['belongs_to'] == "Department") {
         $belongs_to = 'Department';
        //  $school = isset($_POST['school_name']) ? $_POST['school_name'] : null;
         $department = isset($_POST['department_name']) ? $_POST['department_name'] : null;
     } else if ($_POST['belongs_to'] == "School") {
         $belongs_to = 'School';
         $school = isset($_POST['school_name_school']) ? $_POST['school_name_school'] : null;
     } else if ($_POST['belongs_to'] == "Administration") {
         $belongs_to = "Administration";
         $section = isset($_POST['section']) ? $_POST['section'] : null;
     }
 

    // Insert query
    $query = "INSERT INTO employee (employee_name, employee_email, employee_mobile, designation, department_id, school_id, section_id, office, status) 
          VALUES ('$employee_name', '$email', '$contact', '$designation', 
                   " . ($department ? "'$department'" : "NULL") . ", 
                " . ($school ? "'$school'" : "NULL") . ", 
                " . ($section ? "'$section'" : "NULL") . ", '$belongs_to', 'Active')"; 


    // Execute query
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Employee added successfully!');</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
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


                <h1 style="color:#170098; text-align:center;">Add Employee</h1>

                <h4 class="form-section-title">Emloyee Details</h4>



                <div class="form-group ">
                    <label class="form-label" for="employee_name">Employee Name:</label>
                    <input type="text" id="employee_name" name="employee_name" placeholder="Employee Name">
                </div>

                <div class="form-group ">
                    <label class="form-label" for="email">Email:</label>
                    <input type="email" id="email" name="email" placeholder="Email">
                </div>

                <div class="form-group ">
                    <div id="contact">
                        <label class="form-label" for="contact">Employee Contact:</label>
                        <input type="number" id="contact" name="contact" class="form-control" placeholder="Enter Contact">
                    </div>
                </div>

                <div class="form-group ">
                    <label class="form-label">Designation:</label>
                    <input type="text" class="form-control" id="designation" name="designation" placeholder="Enter Designation">
                </div>

                <div class="form-group">
                    <!-- Dropdown for Belongs To -->
                    <label class="form-label mt-2" for="belongs_to">Belongs to:</label>
                    <select name="belongs_to" style="padding: 11px; color:#595c7e;" id="belongs_to" class="form-control" onchange="toggleBelongsTo(this.value)" required>
                        <option value="">Select</option>
                        <option value="Department">Department</option>
                        <option value="School">School</option>
                        <option value="Administration">Administration</option>
                    </select>

                    <!-- Dropdowns for Department -->
                    <div id="departmentFields" style="display: none;">
                        <label class="form-label" style="margin-top: 8px;">School Name:</label>
                        <select style="padding: 11px; color:#595c7e;" name="school_name" id="school_name">
                            <option  value="">All School</option>
                            <?php
                            include '../assets/conn.php';
                            $sql = "SELECT DISTINCT school_name, school_id FROM schools";
                            $result = $conn->query($sql);
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="' . $row['school_id'] . '">' . $row['school_name'] . '</option>';
                                }
                            }
                            ?>
                        </select>

                        <label class="form-label" style="margin-top: 8px;">Department :</label>
                        <select style="padding: 11px; color:#595c7e;" name="department_name" id="department_name">
                            <option value="">All Department</option>
                        </select>
                    </div>

                    <!-- Dropdown for School -->
                    <div id="schoolField" style="display: none;">
                        <label class="form-label" style="margin-top: 8px;">School:</label>
                        <select style="padding: 11px; color:#595c7e;" name="school_name_school" id="school_name_school">
                            <option value="">All School</option>
                            <?php
                            include '../assets/conn.php';
                            $sql = "SELECT DISTINCT school_name, school_id FROM schools";
                            $result = $conn->query($sql);
                            if ($result->num_rows > 0) {
                                while ($school = $result->fetch_assoc()) {
                                    echo '<option value="' . $school['school_id'] . '">' . $school['school_name'] . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Dropdown for Section (Administration) -->
                    <div id="sectionField" style="display: none;">
                        <label class="form-label" style="margin-top: 8px;">Section:</label>
                        <select style="padding: 11px; color:#595c7e;" name="section">
                            <option value="">All Section</option>
                            <?php
                            include '../assets/conn.php';
                            $sql = "SELECT section_name, section_id FROM section";
                            $result = $conn->query($sql);
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="' . $row['section_id'] . '">' . $row['section_name'] . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div><br>

                    <input type="submit" style="margin-left: 35%; background-color: #007bff; padding: 12px 40px;" class="btn btn-primary btn-lg" name="submitroom" value="Submit">
                    <input type="button" class="btn btn-success btn-lg" style="padding: 12px 40px;" onclick="clearForm()" name="clear" value="Clear">
                </div>

                <script>
                    function toggleBelongsTo(value) {
                        document.getElementById('departmentFields').style.display = (value === 'Department') ? 'block' : 'none';
                        document.getElementById('schoolField').style.display = (value === 'School') ? 'block' : 'none';
                        document.getElementById('sectionField').style.display = (value === 'Administration') ? 'block' : 'none';
                    }

                    function clearForm() {
                        document.getElementById('belongs_to').value = "";
                        toggleBelongsTo("");
                    }
                </script>

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