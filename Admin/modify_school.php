<?php
include '../assets/conn.php'; // Database connection file
$scl_id = intval($_GET['id']);

$sql = "SELECT * FROM schools where school_id = $scl_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Output data of each row
    while ($row = $result->fetch_assoc()) {
        // Store the data in variables
        $schoolName = $row["school_name"];
        $deanName = $row["incharge_name"];
        $designation = $row["designation"];
        $deanContact = $row["incharge_contact_number"];
        $deanEmail = $row["incharge_email"];
        $deanIntercom = $row["incharge_intercom"];
        $deanStatus = $row["incharge_status"];
    }
} else {
    echo "0 results";
}

// $conn->close();

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
    <link rel="stylesheet" href="../assets/design.css" />

    <title>Add Hall</title>
    <style>
        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }
    </style>
</head>

<body>

    <?php include '../assets/header_1.php'; ?>

    <div id="main">
        <div class="container mt-5">
            <!-- <h2 style="text-align: center;">Add School or Department</h2> -->

            <!-- Radio buttons to choose between School or Department -->
            <form id="myForm" style="margin:20px 150px; background-color:white; padding: 50px; border-radius:15px;" method="POST">
                <h2 style="text-align: center; color:blue">Modify School </h2>

                <div id="school-form" class="form-section active mt-3">
                    <div class="form-label">
                        <label class="form-label" for="school">School Name:</label>
                        <input type="text" class="form-control" id="school" name="school" value="<?php echo $schoolName; ?>" placeholder="Enter School Name">
                    </div>

                    <div class="form-label">
                        <label class="form-label" for="dean-name">Dean Name:</label>
                        <input type="text" class="form-control" id="dean-name" name="dean-name" value="<?php echo $deanName; ?>" placeholder="Enter Dean Name">

                        <!-- <label class="form-label" for="designation">Designation:</label>
                        <input type="text" class="form-control" id="designation" name="designation" value="<?php echo $designation; ?>" readonly> -->
                    </div>
                    <label class="form-label" for="dean-contact">Dean Contact Number:</label>
                    <input type="text" class="form-control" id="dean-contact" name="dean-contact" value="<?php echo $deanContact; ?>" placeholder="Enter Dean Contact Number">

                    <div class="form-label">
                        <label class="form-label" for="dean-email">Dean Email:</label>
                        <input type="email" class="form-control" id="dean-email" name="dean-email" value="<?php echo $deanEmail; ?>" placeholder="Enter Dean Email">
                    </div>

                    <div class="form-label">
                        <label class="form-label" for="dean-intercom">Dean Intercom:</label>
                        <input type="text" class="form-control" id="dean-intercom" name="dean-intercom" value="<?php echo $deanIntercom; ?>" placeholder="Enter Dean Intercom">
                    </div>

                    <div class="form-label">
                        <label class="form-label" for="dean-status">Dean Status:</label>
                        <select id="dean-status" name="dean-status">
                            <!-- <option value="?php echo $deanStatus; ?>">?php echo $deanStatus; ?></option> -->
                            <option value="<?php echo $deanStatus; ?>">All Dean Status</option>
                            <option value="Permanent">Permanent</option>
                            <option value="Incharge">Incharge</option>
                        </select>
                        <span id="dean-status-error" style="color: red;"></span>
                    </div>
                    <!-- <label class="form-label" for="dean-status">Dean Status:</label>
                    <input type="text" class="form-control" id="dean-status" name="dean-status" value="<?php echo $deanStatus; ?>" placeholder="Enter Dean Status"> -->
                </div>

                <input type="submit" style="margin-left: 35%;" class="btn btn-success btn-lg" name="updateschool" value="Update">
                <!-- <input type="submit" class="btn btn-success btn-lg" onclick="clearForm()" name="clear" value="Clear"> -->
            </form>

        </div>
        <!-- <footer>
        <p>&copy; 2024 University Hall Booking System | All Rights Reserved</p>
    </footer> -->


        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['updateschool'])) {
        // Capture form data
        $schoolName = $_POST['school'];
        $deanName = $_POST['dean-name'];
        $designation = $_POST['designation'];
        $deanContact = $_POST['dean-contact'];
        $deanEmail = $_POST['dean-email'];
        $deanIntercom = $_POST['dean-intercom'];
        $deanStatus = $_POST['dean-status'];

        // Prepare and execute update query
        $sql = "UPDATE schools 
                SET school_name = ?, incharge_name = ?, designation = ?, incharge_contact_number = ?, incharge_email = ?, incharge_intercom = ?, incharge_status = ? 
                WHERE school_name = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssss', $schoolName, $deanName, $designation, $deanContact, $deanEmail, $deanIntercom, $deanStatus, $schoolName);

        if ($stmt->execute()) {
            // Success
            echo "<script>alert('School details updated successfully!');</script>";
            echo "<script>window.location.href='view_school.php';</script>";
        } else {
            // Failure
            echo "<script>alert('Error updating school details!');</script>";
        }

        // Close the statement
        $stmt->close();
    }
}
?>