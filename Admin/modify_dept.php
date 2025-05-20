<?php
include '../assets/conn.php';

// Check if department ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Invalid access. No department ID provided.');</script>";
    exit;
}

$id = intval($_GET['id']); // Ensure ID is an integer

// Fetch department details
$stmt = $conn->prepare("
    SELECT d.school_id, d.department_name, d.incharge_name, d.incharge_email, 
           d.incharge_contact_mobile, d.incharge_intercom, d.designation, s.school_name
    FROM departments d
    JOIN schools s ON s.school_id = d.school_id
    WHERE d.department_id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $dept = $result->fetch_assoc();
    $current_school_id = htmlspecialchars($dept['school_id']);
    $current_school_name = htmlspecialchars($dept['school_name']);
    $department_name = htmlspecialchars($dept['department_name']);
    $hod_name = htmlspecialchars($dept['incharge_name']);
    $hod_contact = htmlspecialchars($dept['incharge_contact_mobile']);
    $designation = htmlspecialchars($dept['designation']);
    $hod_email = htmlspecialchars($dept['incharge_email']);
    $hod_intercom = htmlspecialchars($dept['incharge_intercom']);
} else {
    echo "<script>alert('Department not found!');</script>";
    exit;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Department</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/design.css" />
</head>
<body>
    <?php include '../assets/header_1.php'; ?>
    <div id="main">
    <div class="row justify-content-center">
        <div class="col-md-8 mt-5">
            <div class="col-12 pt-5">
                <form id="myForm" method="POST" class="p-5 bg-white rounded shadow">
                    <h2 class="text-center text-primary mb-4">Update Department</h2>

                    <!-- School Dropdown -->
                    <div class="mb-3">
                        <label for="school_name" class="form-label">School Name:</label>
                        <select name="school_name" id="school_name" class="form-select" required>
                            <option value="">Select School</option>
                            <?php
                            $sql = "SELECT school_id, school_name FROM schools";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while ($school = $result->fetch_assoc()) {
                                    $selected = ($school['school_id'] == $current_school_id) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($school['school_id']) . '" ' . $selected . '>' . htmlspecialchars($school['school_name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Department Name -->
                    <div class="mb-3">
                        <label for="department-name" class="form-label">Department Name:</label>
                        <input type="text" id="department-name" name="department-name" class="form-control" 
                               value="<?php echo $department_name; ?>" placeholder="Enter Department Name" required>
                    </div>

                    <!-- HOD Name -->
                    <div class="mb-3">
                        <label for="hod-name" class="form-label">HOD Name:</label>
                        <input type="text" id="hod-name" name="hod-name" class="form-control" 
                               value="<?php echo $hod_name; ?>" placeholder="Enter HOD Name" required>
                    </div>

                    <!-- HOD Contact -->
                    <div class="mb-3">
                        <label for="hod-contact" class="form-label">HOD Contact Number:</label>
                        <input type="text" id="hod-contact" name="hod-contact" class="form-control" 
                               value="<?php echo $hod_contact; ?>" placeholder="Enter HOD Contact Number" >
                    </div>

                    <!-- Designation -->
                    <div class="mb-3">
                        <label for="designation" class="form-label">Designation:</label>
                        <input type="text" id="designation" name="designation" class="form-control" 
                               value="<?php echo $designation; ?>" readonly>
                    </div>

                    <!-- HOD Email -->
                    <div class="mb-3">
                        <label for="hod-email" class="form-label">HOD Email:</label>
                        <input type="email" id="hod-email" name="hod-email" class="form-control" 
                               value="<?php echo $hod_email; ?>" placeholder="Enter HOD Email" required>
                    </div>

                    <!-- HOD Intercom -->
                    <div class="mb-3">
                        <label for="hod-intercom" class="form-label">HOD Intercom:</label>
                        <input type="text" id="hod-intercom" name="hod-intercom" class="form-control" 
                               value="<?php echo $hod_intercom; ?>" placeholder="Enter HOD Intercom">
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-center mt-4">
                        <button type="submit" name="updatedept" class="btn btn-success me-2">Update</button>
                        <!-- <button type="button" onclick="document.getElementById('myForm').reset();" class="btn btn-secondary">Clear</button> -->
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        function toggleForm() {
            const selectedType = document.querySelector('input[name="type"]:checked').value;
            document.getElementById('school-form').classList.remove('active');
            document.getElementById('department-form').classList.remove('active');

            if (selectedType === 'school') {
                document.getElementById('school-form').classList.add('active');
            } else {
                document.getElementById('department-form').classList.add('active');
            }
        }

        window.onload = function() {
            toggleForm(); // Set the default view on page load
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

    <!-- Clear form data  -->
    <!-- <script>
        function clearForm() {
            document.getElementById("myForm").reset(); // Reset all form fields
        }
    </script> -->

</body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['updatedept'])) {
    $scl_id = !empty($_POST['school_name']) ? intval($_POST['school_name']) : $current_school_id;
    $dept_name = trim($_POST['department-name']);
    $hod_name = trim($_POST['hod-name']);
    $hod_cnt = trim($_POST['hod-contact']);
    $designation = trim($_POST['designation']);
    $hod_email = trim($_POST['hod-email']);
    $hod_intercom = trim($_POST['hod-intercom']);

    // Validation
    if (!$dept_name || !$hod_name || !$hod_email || !$scl_id) {
        echo "<script>alert('All required fields must be filled!');</script>";
        exit;
    }

    // Update query
    $update_sql = "UPDATE departments SET 
                    school_id = ?, 
                    department_name = ?, 
                    incharge_name = ?, 
                    incharge_contact_mobile = ?, 
                    designation = ?, 
                    incharge_email = ?, 
                    incharge_intercom = ?
                   WHERE department_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param(
        'issssssi',
        $scl_id,
        $dept_name,
        $hod_name,
        $hod_cnt,
        $designation,
        $hod_email,
        $hod_intercom,
        $id
    );

    if ($update_stmt->execute()) {
        echo "<script>alert('Department updated successfully!');</script>";
        echo "<script>window.location.href='view_dept.php?id=" . htmlspecialchars($scl_id) . "';</script>";
    } else {
        echo "<script>alert('Error updating department: " . htmlspecialchars($conn->error) . "');</script>";
    }
    $update_stmt->close();
}
?>
