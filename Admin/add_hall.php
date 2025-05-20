<?php
include '../assets/conn.php';
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
            <form action="insert.php" id="myForm" method="post" style="margin:20px 150px; background-color:white; padding: 50px; border-radius:15px;" enctype="multipart/form-data">
            

                <h1 style="color:#170098; text-align:center;">Add Hall</h1>

                <h4 class="form-section-title">Hall Details</h4>

                <div class="form-group ">
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

                                echo '<input type="radio" class="btn-check" name="room_type" id="' . $type_name . '" value="' . $type_id . '"onclick="fetchRoomType(' . $type_id . ', \'' . $type_name . '\')" required>';
                                echo '<label class="btn btn-outline-primary" for="' . $type_name . '">' . $type_name . '</label>';
                            }
                        } else {
                            echo "No Hall types found.";
                        }
                        $conn->close();
                        ?>
                    </div>

                </div>

                <div class="form-group ">
                    <!-- Hidden input fields for storing the selected type ID and name -->
                    <input type="hidden" id="hall_id" name="hall_id" value="">
                    <input type="hidden" id="type_name" name="type_name" value="">
                </div>
                <div class="form-group ">
                    <label class="form-label" for="room_name"> Name:</label>
                    <input type="text" id="room_name" name="room_name" placeholder="Hall Name">
                </div>
                <div class="form-group ">
                    <div id="additionalFieldContainer"></div>
                </div>
                <div class="form-group ">
                    <label class="form-label" for="capacity">Capacity:</label>
                    <input type="number" id="apacity" name="capacity" placeholder="Capacity">
                </div>

                <div class="form-group ">
                    <label class="form-label">Features:</label>
                    <div class="row ">

                        <!-- <!- AC - -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature">
                                    <input type="checkbox" class="prefer" name="ac" value="AC" onchange="toggleInput(this, 'ac_num')"> AC
                                    <!-- <input type="number" class="features_num" name="ac_num" id="ac_num" style="display:none; padding:4px; width: 80px; margin-left: 20px;" placeholder="num "> -->
                                </label>
                            </div>
                        </div>

                        <!-- <!- Projector - -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature">
                                    <input type="checkbox" class="prefer" name="preference[]" value="Projector" onchange="toggleInput(this, 'projector_num')"> Projector
                                    <!-- <input type="number" class="features_num" name="projector_num" id="projector_num" style="display:none; padding:4px; width: 80px; margin-left: 20px;" placeholder="num"> -->
                                </label>
                            </div>
                        </div>

                        <!-- wifi  -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature">
                                    <input type="checkbox" class="prefer" name="preference[]" value="WIFI" onchange="toggleInput(this, 'wifi_num')"> WIFI
                                    <!-- <input type="number" class="features_num" name="wifi_num" id="wifi_num" style="display:none; padding:4px; width: 80px; margin-left: 20px;" placeholder="num"> -->
                                </label>
                            </div>
                        </div>

                        <!-- Smart Board -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature">
                                    <input type="checkbox" class="prefer" name="preference[]" value="Smartboard" onchange="toggleInput(this, 'smartboard_num')"> Smart Board
                                    <!-- <input type="number" class="features_num" name="smartboard_num" id="smartboard_num" style="display:none; padding:4px; width: 80px; margin-left: 20px;" placeholder="num"> -->
                                </label>
                            </div>
                        </div>

                        <!-- Computer -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature">
                                    <input type="checkbox" class="prefer" name="preference[]" value="Computer" onchange="toggleInput(this, 'computer_num')"> Computer
                                    <!-- <input type="number" class="features_num" name="computer_num" id="computer_num" style="display:none; padding:4px; width: 80px; margin-left: 20px;" placeholder="num"> -->
                                </label>
                            </div>
                        </div>

                        <!-- <!- Audio System -- -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature d-flex align-items-center">
                                    <input type="checkbox" class="prefer" name="preference[]" value="AudioSystem" onchange="toggleInput(this, 'audio_num')"> Audio System
                                    <!-- <input type="number" class="features_num" name="audio_num" id="audio_num" style="display:none; padding:4px; width: 80px; margin-left: 20px;" placeholder="num"> -->
                                </label>
                            </div>
                        </div>

                        <!--  Podium -- -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature">
                                    <input type="checkbox" class="prefer" name="preference[]" value="Podium" onchange="toggleInput(this, 'podium_num')"> Podium
                                    <!-- <input type="number" class="features_num" name="podium_num" id="podium_num" style="display:none; padding:4px; width: 80px; margin-left: 20px;" placeholder="num"> -->
                                </label>
                            </div>
                        </div>

                        <!-- White Board -->
                        <div class="col-md-4 ">
                            <div class="form-check">
                                <label class="feature">
                                    <input type="checkbox" class="prefer" name="preference[]" value="Whiteboard" onchange="toggleInput(this, 'whiteboard_num')"> White Board
                                    <!-- <input type="number" class="features_num" name="whiteboard_num" id="whiteboard_num" style="display:none; padding:4px; width: 80px; margin-left: 20px;" placeholder="num"> -->
                                </label>
                            </div>
                        </div>

                        <!-- Black Board -->
                        <div class="col-md-4">
                            <div class="form-check">
                                <label class="feature">
                                    <input type="checkbox" class="prefer" name="preference[]" value="Blackboard" onchange="toggleInput(this, 'blackboard_num')"> Black Board
                                    <!-- <input type="number" class="features_num" name="blackboard_num" id="blackboard_num" style="display:none; padding:4px; width: 80px; margin-left: 20px;" placeholder="num"> -->
                                </label>
                            </div>
                        </div>

                        <!-- Lift -->
                        <div class="col-md-4 ">
                            <div class="form-check">
                                <label class="feature">
                                    <input type="checkbox" class="prefer" name="preference[]" value="lift" onchange="toggleInput(this, 'lift_num')"> Lift
                                    <!-- <input type="number" class="features_num" name="lift_num" id="lift_num" style="display:none; padding:4px; width: 80px; margin-left: 20px;" placeholder="num"> -->
                                </label>
                            </div>
                        </div>

                        <!-- Ramp -->
                        <div class="col-md-4 ">
                            <div class="form-check">
                                <label class="feature">
                                    <input type="checkbox" class="prefer" name="preference[]" value="Ramp" onchange="toggleInput(this, 'ramp_num')"> Ramp
                                    <!-- <input type="number" class="features_num" name="ramp_num" id="ramp_num" style="display:none; padding:4px; width: 80px; margin-left: 20px;" placeholder="num"> -->
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group ">

                    <label class="form-label">Floor name :</label> <br>
                    <input type="radio" style=" margin-right: 2px; margin-left:25px;" name="floor" value="Ground Floor"
                        required>Ground Floor
                    <input type="radio" style=" margin-left:15px;" name="floor" value="First Floor"
                        required> First Floor
                    <input type="radio" style=" margin-left:15px;" name="floor" value="Second Floor"
                        required> Second Floor <br>
                </div>

                <div class="form-group ">
                    <label class="form-label">Zone :</label><br>
                    <input type="radio" style=" margin-left:25px; margin-right:3px;" name="zone" value="East"
                        required>East
                    <input type="radio" style=" margin-left:15px; " name="zone" value="West"
                        required> West
                    <input type="radio" style=" margin-left:15px;" name="zone" value="North"
                        required> North
                    <input type="radio" style=" margin-left:15px;" name="zone" value="South"
                        required> South <br>

                    <div id="cost_field" style="display:none;">
                        <label class="form-label" for="cost">Cost:</label>
                        <input type="number" id="cost" name="cost" class="form-control" placeholder="Enter cost">
                    </div>
                </div>

                <div class="form-group ">
                    <label class="form-label">Image:</label>
                    <input type="file" class="form-control" id="imageUpload" name="file">
                </div>

                <!-- <div class="form-group ">
                    <label class="form-label mt-2" for="status">Status:</label>
                    <select id="status" class="status-input" name="status">
                        <option value="">Select Section</option>
                        <option value="Active">Active </option>
                        <option value="Archived">Archived</option>

                    </select>
                </div> -->

                <div class="form-group ">
                    <label class="form-label">Hall Availability:</label>
                    <div class="mb-2">
                        <input type="radio" style=" margin-left:25px;" id="yes-option" name="availability" value="Yes">
                        <label for="yes-option" style="margin-right: 20px;">Yes</label>
                        <input type="radio" id="no-option" name="availability" value="no">
                        <label for="no-option">No</label>
                        <!-- <input type="text" class="reason-input"  placeholder="reason for unavailability" > -->
                        <select style="padding: 7px;" id="message" class="reason-input" name="reason-input">
                            <option value="">All Reasons</option>
                            <option value="Awaiting Inauguration ">Awaiting Inauguration </option>
                            <option value="Under Construction ">Under Construction </option>

                        </select>
                    </div>
                </div>

                <div class="form-group ">
                    <!-- Radio buttons for Belongs To -->
                    <h4 class="form-section-title">Belongs to</h4>
                    <div>
                        <input type="radio" style=" margin-left:25px;" name="belongs_to" value="Department"
                            onclick="toggleBelongsTo('Department')" required> Department
                        <input type="radio" style=" margin-left:15px;" name="belongs_to" value="School"
                            onclick="toggleBelongsTo('School')" required> School
                        <input type="radio" style=" margin-left:15px;" name="belongs_to" value="Administration"
                            onclick="toggleBelongsTo('Administration')" required> Administration
                    </div>

                    <!-- Dropdowns for Department -->

                    <div id="departmentFields" style="display: none;">

                        <label class="form-label" style="margin-top: 8px;">School Name:</label>
                        <select style="padding: 7px;" name="school_name" id="school_name">
                            <option value="">All School</option>

                            <?php
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

                        <label class="form-label" style="margin-top: 8px;">Department :</label>
                        <select style="padding: 7px;" name="department_name" id="department_name">
                            <option value="">All Department</option>
                        </select>
                    </div>

                    <!-- Dropdown for School -->
                    <div id="schoolField" style="display: none;">
                        <label class="form-label" style="margin-top: 8px;">School:</label>
                        <select style="padding: 7px;" name="school_name_school" id="school_name_school">
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
                        <select style="padding: 7px;" name="section">
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
                    <input type="submit" class="btn btn-success btn-lg" style="padding: 12px 40px;" onclick="clearForm()" name="clear" value="Clear">
                </div>
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

    <!-- For select hall type with id and name -->
    <script>
        function fetchRoomType(typeId, typeName) {
            // Update the hidden input fields with the selected room type ID and name
            document.getElementById('hall_id').value = typeId;
            document.getElementById('type_name').value = typeName;

            //  hide fields in text fields 
            document.getElementById('hall_id').type = 'hidden';
            document.getElementById('type_name').type = 'hidden';

            let container = document.getElementById('additionalFieldContainer');

            // Remove existing additional field if present
            container.innerHTML = "";

            // If the hall ID is 3, create a new input field
            if (typeId == 3) {
                document.getElementById('room_name').style.display = 'none';

                let newInput = document.createElement("input");
                newInput.type = "text";
                newInput.id = "room_name";
                newInput.name = "room_name";
                newInput.placeholder = "LHC1 001 /SH 308";
                newInput.className = "form-control mt-2";

                container.appendChild(newInput);
            } else {
                document.getElementById('room_name').style.display = 'block';

            }
        }
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

    <!-- availability radio button  -->
    <script>
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
    </script>

    <!-- Writing Board  -->
    <script>
        document.querySelectorAll('input[name="board"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                let reasonInput = document.querySelector('.board-input');
                if (this.value === 'yes') {
                    reasonInput.style.display = 'block';
                } else {
                    reasonInput.style.display = 'none';
                }
            });
        });
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