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

    <title>Admin Home</title>
    <style>

    </style>
</head>

<body>
    <?php include '../assets/header_1.php'; ?>

    <?php

    include '../assets/conn.php';
    if (isset($_GET['id'])) {
        $hall_id = $_GET['id'];  // Or any other method to get the hall ID

        // SQL query to fetch hall details along with school, department, and section names
        $query = "SELECT hd.*, s.school_name, d.department_name, sec.section_name 
                    FROM hall_details hd
                    LEFT JOIN schools s ON hd.school_id = s.school_id
                    LEFT JOIN departments d ON hd.department_id = d.department_id
                    LEFT JOIN section sec ON hd.section_id = sec.section_id
                    WHERE hd.hall_id = ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $hall_id);  // Assuming hall_id is an integer
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $room = $result->fetch_assoc();  // Fetch the data into the $room array
        } else {
            echo "No data found for the specified hall.";
        }
    }

    $user_role = $_SESSION['role'];
    $is_admin = ($user_role === 'admin');
    ?>

    <div id="main">
        <div class="main-content mt-3">
            <!-- Seminar Hall Form -->
            <form method="post" style="margin:0px 100px; background-color:white; padding: 50px; border-radius:15px;" enctype="multipart/form-data">


                <h1 class="mb-4" style="color:#4c37dc; text-align:center;">Modify Hall</h1>

                <h4 class="form-section-title">Hall Details</h4>
                <div class="form-group">

                    <label class="form-label">Hall Type:</label><br>
                    <div class="btn-group" role="group" aria-label="Room Type">
                        <?php
                        // database connection
                        include '../assets/conn.php';

                        // Query the database for room types
                        $query = "SELECT * FROM hall_type";
                        $result = $conn->query($query);

                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $type_id = $row['type_id'];
                                $type_name = $row['type_name'];

                                echo '<input type="radio" class="btn-check" name="room_type" id="' . $type_name . '" ' .
                                    ((int)$room['type_id'] === (int)$type_id ? 'checked' : '') .
                                    ' ' . ($is_admin ? '' : 'disabled') .
                                    ' value="' . $type_id . '" onclick="fetchRoomType(' . $type_id . ', \'' . addslashes($type_name) . '\')" required>';
                                echo '<label class="btn btn-outline-primary" for="' . $type_name . '">' . $type_name . '</label>';
                            }
                        } else {
                            echo "No room types found.";
                        }
                        $conn->close();
                        ?>
                    </div>
                    <input type="hidden" name="old_id" value="<?php echo $room['type_id'] ?>">

                </div>
                <!-- Hidden input fields for storing the selected type ID and name -->
                <div class="form-group">
                    <input type="hidden" id="type_id" name="type_id" value="">
                    <input type="hidden" id="type_name" name="type_name" value="">
                </div>

                <div class="form-group">
                    <label class="form-label" for="room_name"> Name:</label>
                    <input type="text" id="room_name" name="room_name" value="<?php echo $room['hall_name'] ?>" placeholder="Room Name.." <?php echo $is_admin ? '' : 'disabled'; ?>>
                </div>

                <div class="form-group">
                    <label class="form-label" for="capacity">Capacity:</label>
                    <input type="number" id="apacity" name="capacity" value="<?php echo $room['capacity'] ?>" placeholder="capacity.." <?php echo $is_admin ? '' : 'disabled'; ?>>
                </div>

                <div class="form-group">
                    <label class="form-label">Features:</label>
                    <div class="row">


                        <!-- <!- AC - -->
                        <div class="col-md-4 ">
                            <div class="form-check">
                                <label class="feature"> <!-- Change label color if AC is 'No' -->
                                    <input type="checkbox" class="prefer" name="preference[]" value="ac"
                                        <?php if ($room['ac'] === 'AC') {
                                            echo 'checked';
                                        } ?>

                                        onchange="toggleInput(this, 'ac_num')"> AC

                                </label>
                            </div>
                        </div>

                        <!-- <!- Projector - -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature">
                                    <input type="checkbox" class="prefer" name="preference[]" value="projector"
                                        <?php if ($room['projector'] === 'Projector') {
                                            echo 'checked';
                                        } ?>
                                        onchange="toggleInput(this, 'projector_num')"> Projector

                                </label>
                            </div>
                        </div>

                        <!-- wifi  -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature">
                                    <!-- Always allow checking the checkbox, but change its appearance if wifi is 'No' -->
                                    <input type="checkbox" class="prefer" name="preference[]" value="wifi"
                                        <?php if ($room['wifi'] === 'WiFi') {
                                            echo 'checked';
                                        } ?>
                                        onchange="toggleInput(this, 'wifi_num')"> WiFi

                                </label>
                            </div>
                        </div>

                        <!-- Smart Board -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature"> <!-- Change label color if smartboard is 'no' -->
                                    <input type="checkbox" class="prefer" name="preference[]" value="smartboard"
                                        <?php if ($room['smart_board'] === 'Smartboard') {
                                            echo 'checked';
                                        } ?>

                                        onchange="toggleInput(this, 'smartboard_num')"> Smart Board

                                </label>
                            </div>
                        </div>

                        <!-- Computer -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature">
                                    <input type="checkbox" class="prefer" name="preference[]" value="computer"
                                        <?php if ($room['computer'] === 'Computer') {
                                            echo 'checked';
                                        } ?>

                                        onchange="toggleInput(this, 'computer_num')">Computer

                                </label>
                            </div>
                        </div>

                        <!-- <!- Audio System -- -->
                        <div class="col-md-4 ">
                            <div class="form-check">
                                <label class="feature">
                                    <input type="checkbox" class="prefer" name="preference[]" value="audio"
                                        <?php if ($room['audio_system'] === 'AudioSystem') {
                                            echo 'checked';
                                        } ?>

                                        onchange="toggleInput(this, 'audio_num')"> Audio System

                                </label>
                            </div>
                        </div>


                        <!--  Podium -- -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature">
                                    <input type="checkbox" class="prefer" name="preference[]" value="podium"
                                        <?php if ($room['podium'] === 'Podium') {
                                            echo 'checked';
                                        } ?>
                                        onchange="toggleInput(this, 'podium_num')"> Podium

                                </label>
                            </div>
                        </div>

                        <!-- White Board -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature"> <!-- Change label color if lift is 'no' -->
                                    <input type="checkbox" class="prefer" name="preference[]" value="white_board"
                                        <?php if ($room['white_board'] === 'Whiteboard') {
                                            echo 'checked';
                                        } ?>

                                        onchange="toggleInput(this, 'whiteboard_num')"> White Board

                                </label>
                            </div>
                        </div>

                        <!-- Black Board -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature"> <!-- Change label color if lift is 'no' -->
                                    <input type="checkbox" class="prefer" name="preference[]" value="blackboard"
                                        <?php if ($room['blackboard'] === 'Blackboard') {
                                            echo 'checked';
                                        } ?>

                                        onchange="toggleInput(this, 'blackboard_num')"> Black Board

                                </label>
                            </div>
                        </div>



                        <!-- Lift -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature"> <!-- Change label color if lift is 'no' -->
                                    <input type="checkbox" class="prefer" name="preference[]" value="lift"
                                        <?php if ($room['lift'] === 'Lift') {
                                            echo 'checked';
                                        } ?>

                                        onchange="toggleInput(this, 'lift_num')"> Lift

                                </label>
                            </div>
                        </div>

                        <!-- Ramp -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature">
                                    <input type="checkbox" class="prefer" name="preference[]" value="ramp"
                                        <?php if ($room['ramp'] === 'Ramp') {
                                            echo 'checked';
                                        } ?>

                                        onchange="toggleInput(this, 'ramp_num')"> Ramp

                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Floor name :</label> <br>
                    <input type="radio" style="width: 40px; margin-left:15px;" name="floor" value="Ground Floor"
                        <?php if ($room['floor'] === 'Ground Floor') {
                            echo 'checked';
                        } ?>
                        required <?php echo $is_admin ? '' : 'disabled'; ?>> Ground Floor
                    <input type="radio" style="width: 40px;" name="floor" value="First Floor"
                        <?php if ($room['floor'] === 'First Floor') {
                            echo 'checked';
                        } ?>
                        required <?php echo $is_admin ? '' : 'disabled'; ?>> First Floor
                    <input type="radio" style="width: 40px;" name="floor" value="Second Floor"
                        <?php if ($room['floor'] === 'Second Floor') {
                            echo 'checked';
                        } ?>
                        required <?php echo $is_admin ? '' : 'disabled'; ?>> Second Floor <br>
                </div>

                <div class="form-group">
                    <label class="form-label">Zone :</label><br>
                    <input type="radio" style="width: 40px; margin-left:15px;" name="zone" value="East"
                        <?php if ($room['zone'] === 'East') {
                            echo 'checked';
                        } ?>
                        required <?php echo $is_admin ? '' : 'disabled'; ?>> East
                    <input type="radio" style="width: 40px;" name="zone" value="West"
                        <?php if ($room['zone'] === 'West') {
                            echo 'checked';
                        } ?>
                        required <?php echo $is_admin ? '' : 'disabled'; ?>> West
                    <input type="radio" style="width: 40px;" name="zone" value="North"
                        <?php if ($room['zone'] === 'North') {
                            echo 'checked';
                        } ?>
                        required <?php echo $is_admin ? '' : 'disabled'; ?>> North
                    <input type="radio" style="width: 40px;" name="zone" value="South"
                        <?php if ($room['zone'] === 'South') {
                            echo 'checked';
                        } ?>
                        required <?php echo $is_admin ? '' : 'disabled'; ?>> South <br>
                </div>

                <div class="form-group" style="display:none;">
                    <label class="form-label"  for="cost">Cost:</label>
                    <input type="number" id="cost" name="cost" class="form-control" placeholder="Enter cost">
                </div>

                <div class="form-group">
                    <label class="form-label">Image:</label>
                    <input type="file" class="form-control" id="imageUpload" name="file"><br>
                </div>
                <!-- <label class="form-label">Room Availability:</label>
                <div class="mt-2 mb-1"> -->
                <!-- Radio for 'Yes' -->
                <!-- <input type="radio" style="width: 40px; margin-left:15px;" id="yes-option" name="availability" value="Available"
                        <?php if ($room['availability'] === 'Available') echo 'checked'; ?>>
                    <label for="yes-option" style="width: 20px;">Yes</label> -->

                <!-- Radio for 'No' -->
                <!-- <input type="radio" style="width: 40px;" id="no-option" name="availability" value="no"
                        <?php if ($room['availability'] !== 'Available') echo 'checked'; ?>>
                    <label for="no-option" style="width: 20px;">No</label> -->

                <!-- Dropdown for reason, visible only when availability is 'No' -->
                <!-- <select
                        style="padding: 7px; display: <?php echo ($room['availability'] !== 'Available') ? 'block' : 'none'; ?>;"
                        id="reason-input"
                        class="reason-input"
                        name="reason-input"
                        ?php echo ($room['availability'] !== 'Available') ? '' : 'disabled'; ?>>
                        <option value="Awaiting Inauguration" <?php if ($room['availability'] === 'Awaiting Inauguration') echo 'selected'; ?>>Awaiting Inauguration</option>
                        <option value="Under Construction" <?php if ($room['availability'] === 'Under Construction') echo 'selected'; ?>>Under Construction</option>
                        <option value="Temporarily Unavailable" <?php if ($room['availability'] === 'Temporarily Unavailable') echo 'selected'; ?>>Temporarily Unavailable</option>
                        <option value="Closed for Renovation" <?php if ($room['availability'] === 'Closed for Renovation') echo 'selected'; ?>>Closed for Renovation</option>
                        <option value="Under Maintenance" <?php if ($room['availability'] === 'Under Maintenance') echo 'selected'; ?>>Under Maintenance</option>
                        <option value="Removed" <?php if ($room['availability'] === 'Removed') echo 'selected'; ?>>Removed</option>
                    </select>

                </div> -->



                <!-- <h4 class="form-section-title mt-3">Belongs to</h4>
                <div>
                    <input type="radio" style="width: 40px; margin-left:15px;" name="belongs_to" value="Department"
                        onclick="toggleBelongsTo('Department')" <?php if ($room['belongs_to'] === 'Department') echo 'checked'; ?>
                        <?php if ($user_role !== 'admin') echo 'disabled'; ?>> Department
                    <input type="radio" style="width: 40px;" name="belongs_to" value="School"
                        onclick="toggleBelongsTo('School')" <?php if ($room['belongs_to'] === 'School') echo 'checked'; ?>
                        <?php if ($user_role !== 'admin') echo 'disabled'; ?>> School
                    <input type="radio" style="width: 40px;" name="belongs_to" value="Administration"
                        onclick="toggleBelongsTo('Administration')" <?php if ($room['belongs_to'] === 'Administration') echo 'checked'; ?>
                        <?php if ($user_role !== 'admin') echo 'disabled'; ?>> Administration
                </div> -->

                <!-- Dropdowns for Department -->
                <!-- <div id="departmentFields" style="display: <?php echo $room['belongs_to'] === 'Department' ? 'block' : 'none'; ?>;">
                    <label class="form-label" style="margin-top: 8px;">School Name:</label>
                    <select style="padding: 7px;" name="school_name" id="school_name" <?php if ($user_role !== 'admin') echo 'disabled'; ?>>
                        <option value="<?php echo $room['school_id']; ?>"><?php echo $room['school_name']; ?></option>
                        ?php
                        include 'assets/conn.php';
                        $sql = "SELECT DISTINCT school_name, school_id FROM schools";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . $row['school_id'] . '">' . $row['school_name'] . '</option>';
                            }
                        }
                        ?>
                    </select>

                    <label class="form-label" style="margin-top: 8px;">Department:</label>
                    <select style="padding: 7px;" name="department_name" id="department_name" <?php if ($user_role !== 'admin') echo 'disabled'; ?>>
                        <option value="<?php echo $room['department_id']; ?>"><?php echo $room['department_name']; ?></option>
                        ?php
                        $sql = "SELECT DISTINCT department_name, department_id FROM departments";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . $row['department_id'] . '">' . $row['department_name'] . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div> -->

                <!-- Dropdown for School -->
                <!-- <div id="schoolField" style="display: <?php echo ($room['belongs_to'] === 'School') ? 'block' : 'none'; ?>;">
                    <label class="form-label" style="margin-top: 8px;">School:</label>
                    <select style="padding: 7px;" name="school_name_school" id="school_name_school" <?php if ($user_role !== 'admin') echo 'disabled'; ?>>
                        <option value="?php echo $room['school_id']; ?>"><?php echo $room['school_name']; ?></option>
                        ?php
                        include 'assets/conn.php';
                        $sql = "SELECT DISTINCT school_name, school_id FROM schools";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($school = $result->fetch_assoc()) {
                                echo '<option value="' . $school['school_id'] . '">' . $school['school_name'] . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div> -->

                <!-- Dropdown for Section (Administration) -->
                <!-- <div id="sectionField" style="display:?php echo ($room['belongs_to'] === 'Administration') ? 'block' : 'none'; ?>;">
                    <label class="form-label" style="margin-top: 8px;">Section:</label>
                    <select style="padding: 7px;" name="section" ?php if ($user_role !== 'admin') echo 'disabled'; ?>>
                        <option value="?php echo $room['section_id']; ?>">?php echo $room['section_name']; ?></option>
                        ?php
                        $sql = "SELECT section_name, section_id FROM section";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . $row['section_id'] . '">' . $row['section_name'] . '</option>';
                            }
                        }
                        ?>
                    </select> -->
                <!-- </div> -->
                <!-- <h4 class="form-section-title mt-3">Incharge Details</h4>

                <label class="form-label mt-2">Incharge Name</label>
                <input type="text" name="incharge_name" value="?php echo $room['incharge_name'] ?>" placeholder="Incharge name.." <?php if ($user_role !== 'admin') echo 'disabled'; ?>>

                <label class="form-label">Designation</label>
                <select style="padding: 7px;" name="designation" ?php if ($user_role !== 'admin') echo 'disabled'; ?>>
                    <option value="?php echo $room['designation'] ?>">?php echo $room['designation'] ?></option>
                    <option value="Faculty / Staff">Faculty / Staff</option>
                    <option value="HoD">HoD</option>
                    <option value="Dean">Dean</option>
                    <option value="Section Officer">Section Officer</option>
                    <option value="Assistant Registrar">Assistant Registrar</option>
                    <option value="Deputy Registrar">Deputy Registrar</option>
                </select>

                <label class="form-label mt-2">Incharge Email</label>
                <input type="email" name="incharge_email" value="<?php echo $room['incharge_email'] ?>" placeholder="Incharge email.." <?php if ($user_role !== 'admin') echo 'disabled'; ?>>

                <label class="form-label">Incharge Phone Number</label>
                <input type="text" name="phone" value="<?php echo $room['phone'] ?>" placeholder="Incharge Phone Number.." <?php if ($user_role !== 'admin') echo 'disabled'; ?>>-->

                <input type="submit" style="display: block; margin: 0 auto;" name="updateroom" value="Modify">


            </form>
        </div>
    </div>
    </div>
    <!-- <footer>
        <p>&copy; 2024 University Hall Booking System | All Rights Reserved</p>
    </footer> -->


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // JavaScript to fetch departments based on selected school

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

    <script>
        function fetchHallType(typeId, typeName) {
            // console.log(typeId,typeName);
            // Update the hidden input fields with the selected room type ID and name
            document.getElementById('type_id').value = typeId;
            document.getElementById('type_name').value = typeName;

            // If you want to display the hidden fields in text fields (for debugging or showing to the user)
            document.getElementById('type_id').type = 'hidden'; // Change type from 'hidden' to 'text'
            document.getElementById('type_name').type = 'hidden'; // Change type from 'hidden' to 'text'
        }
    </script>

    <script>
        function toggleInput(checkbox, inputId) {
            var inputField = document.getElementById(inputId);
            if (checkbox.checked) {
                inputField.style.display = 'block'; // Show the input field if checked
            } else {
                inputField.style.display = 'none'; // Hide the input field if unchecked
            }
        }
    </script>


    <!-- belongs To   -->
    <!-- <script>
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
    </script> -->


    <!-- features sj -->
    <script>
        function toggleInput(checkbox, inputId) {
            var inputField = document.getElementById(inputId);
            if (checkbox.checked) {
                inputField.style.display = 'block';
            } else {
                inputField.style.display = 'none';
                inputField.value = ''; // Clear the value if unchecked
            }
        }
    </script>

    <!-- radio button  -->
    <!-- <script>
        document.querySelectorAll('input[name="availability"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                let reasonInput = document.querySelector('.reason-input');
                if (this.value === 'no') {
                    reasonInput.style.display = 'block';
                } else {
                    reasonInput.style.display = 'none';
                }
            });
        });
    </script> -->

    <!-- features sj -->
    <script>
        function toggleInput(checkbox, inputId) {
            var inputField = document.getElementById(inputId);
            if (checkbox.checked) {
                inputField.style.display = 'block';
            } else {
                inputField.style.display = 'none';
                inputField.value = ''; // Clear the value if unchecked
            }
        }
    </script>


    <!-- Cost Display  -->
    <script>
        // Function to show/hide the cost field based on room type selection
        function fetchRoomType(roomType) {
            var costField = document.getElementById("cost_field");

            // Show cost field only if room type is "Auditorium" (value 3)
            if (roomType == 3) {
                costField.style.display = "block";
            } else {
                costField.style.display = "none";
            }
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
include '../assets/conn.php';

if (isset($_POST['updateroom']) && $_SERVER["REQUEST_METHOD"] == "POST") {
    $hall_id = $_GET['id'];
    // Capture all posted data
    if ($_POST['type_id']) {
        $type_id = $_POST['type_id'];
    } else {
        $type_id = $_POST['old_id'];
    }
    $room_name = $_POST['room_name'];
    $capacity = $_POST['capacity'];

    // Check availability
    //     $availability = ($_POST['availability'] == "Yes") ? "Yes" : $_POST['reason-input'];

    // if ($availability == "Yes") {
    //     $startDate = "";
    //     $endDate = "";
    // }
    $floor = $_POST['floor'];
    $zone = $_POST['zone'];

    // Handle 'belongs_to' logic
    // if ($_POST['belongs_to'] == "Department") {
    //     $belongs_to = 'Department';
    //     $school = isset($_POST['school_name']) ? $_POST['school_name'] : null;
    //     $department = isset($_POST['department_name']) ? $_POST['department_name'] : null;
    //     $section = null; // Ensure this is null
    // } elseif ($_POST['belongs_to'] == "School") {
    //     $belongs_to = 'School';
    //     $school = isset($_POST['school_name_school']) ? $_POST['school_name_school'] : null;
    //     $department = null; // Ensure this is null
    //     $section = null; // Ensure this is null
    // } elseif ($_POST['belongs_to'] == "Administration") {
    //     $belongs_to = "Administration";
    //     $department = null; // Ensure this is null
    //     $school = null; // Ensure this is null
    //     $section = isset($_POST['section']) ? $_POST['section'] : null;
    // }

    // $incharge_name = $_POST['incharge_name'];
    // $designation = $_POST['designation'];
    // $incharge_email = $_POST['incharge_email'];
    // $phone = $_POST['phone'];
    $cost = isset($_POST['cost']) ? $_POST['cost'] : null;
    // $hall_id = $_POST['hall_id']; // Ensure this is being captured correctly

    // Preferences (AC, Projector, Wi-Fi, etc.)
    $ac = in_array('ac', $_POST['preference']) ? 'AC' : 'No';
    $projector = in_array('projector', $_POST['preference']) ? 'Projector' : 'No';
    $wifi = in_array('wifi', $_POST['preference']) ? 'WiFi' : 'No';
    $smartboard = in_array('smartboard', $_POST['preference']) ? 'Smartboard' : 'No';
    $computer = in_array('computer', $_POST['preference']) ? 'Computer' : 'No';
    $audio = in_array('audio', $_POST['preference']) ? 'AudioSystem' : 'No';
    $podium = in_array('podium', $_POST['preference']) ? 'Podium' : 'No';
    $whiteboard = in_array('white_board', $_POST['preference']) ? 'Whiteboard' : 'No';
    $blackboard = in_array('blackboard', $_POST['preference']) ? 'Blackboard' : 'No';
    $lift = in_array('lift', $_POST['preference']) ? 'Lift' : 'No';
    $ramp = in_array('ramp', $_POST['preference']) ? 'Ramp' : 'No';

    // Check if the type_id exists
    // $type_check_sql = "SELECT COUNT(*) as count FROM hall_type WHERE type_id = ?";
    // $stmt = $conn->prepare($type_check_sql);
    // $stmt->bind_param("i", $type_id);
    // $stmt->execute();
    // $result = $stmt->get_result();
    // $row = $result->fetch_assoc();

    // if ($row['count'] > 0) {
    // Update query
    $sql = "UPDATE hall_details SET 
                    type_id = ?, 
                    hall_name = ?, 
                    capacity = ?, 
                    wifi = ?, 
                    ac = ?, 
                    computer = ?,
                    projector = ?, 
                    audio_system = ?, 
                    podium = ?, 
                    ramp = ?, 
                    smart_board = ?, 
                    lift = ?, 
                    white_board = ?,
                    blackboard = ?,
                    floor = ?, 
                    zone = ?, 
                    cost = ?
                    -- availability = ?, 
                    -- start_date = ?,
                    -- to_date = ?,
                    -- belongs_to = ?,
                    -- department_id = ?,
                    -- school_id = ?,
                    -- section_id = ?
                     
                WHERE hall_id = ?";

    $stmt = $conn->prepare($sql);
    // Ensure correct binding according to your data types
    $stmt->bind_param(
        "issssssssssssssssi",
        $type_id,
        $room_name,
        $capacity,
        $wifi,
        $ac,
        $computer,
        $projector,
        $audio,
        $podium,
        $ramp,
        $smartboard,
        $lift,
        $whiteboard,
        $blackboard,
        $floor,
        $zone,
        $cost,
        // $availability,
        // $startDate,
        // $endDate,
        // $belongs_to,
        // $department,
        // $school,
        // $section,

        $hall_id // Ensure hall_id is passed
    );

    // Execute the query
    if ($stmt->execute()) {
        echo "<script>window.location.assign('view_modify_hall.php')</script>";
    } else {
        echo "Error updating record: " . $stmt->error;
    }
    // } else {
    //     echo "Error: type_id does not exist in hall_type table.";
    // }

    // Close statement and connection
    $stmt->close();
    $conn->close();
}
?>