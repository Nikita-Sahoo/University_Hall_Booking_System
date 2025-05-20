<?php 
include('assets/conn.php');

$features = ['Wifi', 'AC', 'Projector', 'Audio_system', 'Podium', 'Ramp', 'Smart_board', 'Lift'];

// Fetch all schools
$schools_query = "SELECT school_id, school_name FROM schools";
$schools_result = mysqli_query($conn, $schools_query);

// Fetch all departments
$departments_query = "SELECT department_id, department_name FROM departments";
$departments_result = mysqli_query($conn, $departments_query);

// Fetch all room types
$room_types_query = "SELECT type_id, type_name FROM hall_type";
$room_types_result = mysqli_query($conn, $room_types_query);

// Initialize filter variables
$school_id = isset($_GET['school_id']) ? $_GET['school_id'] : '';
$department_id = isset($_GET['department_id']) ? $_GET['department_id'] : '';
$type_id = isset($_GET['type_id']) ? $_GET['type_id'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$booking_type = isset($_GET['booking_type']) ? $_GET['booking_type'] : 'session';
$session_choice = isset($_GET['session_choice']) ? $_GET['session_choice'] : '';
$slots = isset($_GET['slots']) ? $_GET['slots'] : [];

// Function to check if a room is available
function isRoomAvailable($conn, $hall_id, $date, $booking_type, $session_choice, $slots) {
    // Implement your availability check logic here
    // This is a placeholder function
    return true;
}

// Fetch available rooms based on filters
$rooms_query = "SELECT h.hall_id, h.hall_name, s.school_name, d.department_name, rt.type_name 
                FROM hall_details h
                JOIN schools s ON h.school_id = s.school_id
                JOIN departments d ON h.department_id = d.department_id
                JOIN hall_type rt ON h.type_id = rt.type_id
                WHERE 1=1";

if ($school_id) {
    $rooms_query .= " AND h.school_id = $school_id";
}
if ($department_id) {
    $rooms_query .= " AND h.department_id = $department_id";
}
if ($type_id) {
    $rooms_query .= " AND h.type_id = $type_id";
}

$rooms_result = mysqli_query($conn, $rooms_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Halls</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="assets/design.css" />
    <style>
        BODY{
            background-color: #ffffff;
        }
        .filter-section {
            background-color: #ffffff;
            border-radius: 5px;
        }
        .room-card {
            margin-bottom: 20px;
        }
        .main-content {
            margin-left: 270px;
            margin-right: 20px;
            padding-top: 20px;
            overflow: hidden;
        }
        .card-body{
            padding:5px;
            background-color: aliceblue;
        }
        /* For radio buttons */
        input[type="radio"], 
        input[type="checkbox"] {
            border: 2px solid #000; /* Bold border */
            border-radius: 50%; /* For radio buttons, make them circular */
            padding: 5px; /* Add some padding */
            outline: none; /* Remove the default outline */
        }

        /* For checkboxes (square outline) */
        input[type="checkbox"] {
            border-radius: 4px; /* Square corners for checkboxes */
        }
        input[type="radio"]:hover, 
        input[type="checkbox"]:hover,
        input[type="radio"]:focus, 
        input[type="checkbox"]:focus {
            border-color: #007BFF; /* Change border color on hover/focus */
        }
        .my-light-div {
            padding: 20px;            /* Add some padding for better appearance */
            border-radius: 5px;       /* Optional: Rounded corners */
            background-color:rgb(248, 248, 248);
            /* border: 0.5px solid black; */
            border-radius: 8px;
            box-shadow: 2px 4px 10px rgba(0, 0, 0, 0.2);        }
            .btn-outline-primary {
        min-width: 80px;
    }
    .btn-primary {
        margin:1px;
    }
/* Add base styling for the card */
.room-card .card {
    transition: transform 0.3s ease, box-shadow 0.3s ease; /* Smooth transform and shadow change */
}

/* Define the hover effect */
.room-card .card:hover {
    transform: scale(1.05); /* Slightly enlarge the card */
    box-shadow: 4px 6px 15px rgba(0, 0, 0, 0.3); /* Increase shadow depth */
}
</style>

</head>
<body>
<?php include 'assets/header.php' ?>

<div id="main">            <div class="col-md-12 mt-5">
          
                <!-- <div class="card shadow-lg"> -->
            <div class="filter-section">
                <form id="filterForm" method="GET">
                <div class="row">
                <div class="col-md-7 border-end">
<center><h3 style="color:#0e00a3">Browse & Book Hall</h3><br></center>

    <!-- Left Div -->
    <div class="row mb-1 align-items-center">
        <label for="room_type" class="col-md-2 mt-1 form-label">Hall Type:</label>
        <div class="col-md-10 me-0 d-flex">
            <div class="btn-group me-3"  role="group">
                <input type="radio" name="type_id" id="type1" class="btn-check" value="1" <?php echo ($type_id == 1) ? 'checked' : 'checked'; ?>>
                <label class="btn btn-outline-primary" for="type1">Seminar Hall</label>
                <input type="radio" name="type_id" id="type2" class="btn-check" value="2" <?php echo ($type_id == 2) ? 'checked' : ''; ?>>
                <label class="btn btn-outline-primary" for="type2">Auditorium</label>
                <input type="radio" name="type_id" id="type3" class="btn-check" value="3" <?php echo ($type_id == 3) ? 'checked' : ''; ?>>
                <label class="btn btn-outline-primary" for="type3">Lecture Hall</label>
                <input type="radio" name="type_id" id="type4" class="btn-check" value="4" <?php echo ($type_id == 4) ? 'checked' : ''; ?>>
                <label class="btn btn-outline-primary" for="type4">Conference Hall</label>
        </div>
        </div>
    </div>

    <div class="row align-items-center">
        <label for="school" class="col-md-2 mt-2 form-label">School:</label>
        <div class="col-md-9">
            <select name="school_id" id="school" class="form-select mb-0">
                <option value="">All Schools</option>
                <?php 
                while ($school = mysqli_fetch_assoc($schools_result)): 
                    $selected = (isset($_SESSION["school_id"]) && $_SESSION["school_id"] == $school['school_id']) ? 'selected' : '';
                ?>
                    <option value="<?php echo $school['school_id']; ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($school['school_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>

    <div class="row align-items-center">
        <label for="department" class="col-md-2 form-label">Department:</label>
        <div class="col-md-9">
            <select name="department_id" id="department" class="form-select">
                <option value="">All Departments</option>
            </select>
        </div>
    </div>
   
</div>

<div class="col-md-5">
    <!-- Right Div -->
    <div class="row">
    <div class="col-md-6 d-flex align-items-start">
    <label for="from-date" class="form-label mt-1 me-2">From:</label>
    <input type="date" name="from_date" id="from-date" class="form-control" onclick="this.showPicker()">
</div>
<div class="col-md-6 d-flex align-items-start">
    <label for="to-date" class="form-label mt-1 me-2">To:</label>
    <input type="date" name="to_date" id="to-date" class="form-control" onclick="this.showPicker()">
</div>

<!-- Session Options -->
<div class="row mt-3" id="session_options">
    <div class="col-md-12">
        <div class="btn-group" role="group">
            <input type="checkbox" class="btn-check" name="session_choice" id="fn" value="fn">
            <label class="btn btn-outline-primary " for="fn">Forenoon</label>
            <input type="checkbox" class="btn-check" name="session_choice" id="an" value="an">
            <label class="btn btn-outline-primary " for="an">Afternoon</label>
            </div>
    </div>
</div>

<!-- Slot Options -->
<div class="row mt-3" id="slot_options_first">
    <div class="col-md-10">
        <?php
        $slot_times = ['09:30am', '10:30am', '11:30am', '12:30pm', '01:30pm', '02:30pm', '03:30pm', '04:30pm'];
        $slot_value = 1;

        foreach ($slot_times as $index => $time) {
            echo '<input type="checkbox" class="btn-check slot-checkbox" name="slots[]" id="slot' . ($index + 1) . '" value="' . ($index + 1) . '" ' . (in_array($index + 1, $slots) ? 'checked' : '') . '>';
            echo '<label class="btn btn-outline-primary me-2 mb-2" for="slot' . ($index + 1) . '">' . $time . '</label>';
            if ($index == 3) {
                echo '<br>'; // Separate morning and afternoon slots
            }
        }
        
        ?>
    </div>
    <div class="col-md-2 d-flex align-items-center justify-content-end mb-2">
        <button id="clear_button" class="btn btn-outline-secondary">Clear Selection</button>
    </div>
</div>


        </div>
    </div>
</div>
</form>


    <div class="row mt-3">
        <!-- Filter Section -->
        <div class="col-md-2">
            <form id="hallForm">
            <div class="d-flex justify-content-start align-items-center mt-4">
    <h5 style="color:#0e00a3; font-weight:600;" class="mb-0">Capacity</h5>
    <span class="btn btn-outline-primary ms-3" style="padding: 2px 10px; min-width: 50px;" id="clearFilters">Clear</span>
</div>
                <div class="form-check mt-2">
                    <input type="radio" name="capacity" value="50" id="capacity50" class="form-check-input">
                    <label for="capacity50" class="form-check-label">Less than 50</label>
                </div>
                <div class="form-check">
                    <input type="radio" name="capacity" value="100" id="capacity100" class="form-check-input">
                    <label for="capacity100" class="form-check-label">50 to 100</label>
                </div>
                <div class="form-check">
                    <input type="radio" name="capacity" value="101" id="capacity101" class="form-check-input">
                    <label for="capacity101" class="form-check-label">More than 100</label>
                </div>
                <div class="d-flex justify-content-start align-items-center mt-4">
    <h5 style="color:#0e00a3; font-weight:600;" class="mb-0">Features</h5>
    <span class="btn btn-outline-primary ms-3" style="padding: 2px 10px; min-width: 50px;" id="clearFeatures">Clear</span>
</div>

                <div class="form-check mt-2">
                    <input type="checkbox" name="features[]" value="projector" id="projector" class="form-check-input">
                    <label for="projector" class="form-check-label">Projector</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="features[]" value="smart_board" id="smartboard" class="form-check-input">
                    <label for="smartboard" class="form-check-label">Smartboard</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="features[]" value="ac" id="ac" class="form-check-input">
                    <label for="ac" class="form-check-label">AC</label>
                </div>
               
                <div class="form-check">
                    <input type="checkbox" name="features[]" value="computer" id="computer" class="form-check-input">
                    <label for="computer" class="form-check-label">Computer</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="features[]" value="audio_system" id="audio_system" class="form-check-input">
                    <label for="audio_system" class="form-check-label">Audio System</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="features[]" value="podium" id="podium" class="form-check-input">
                    <label for="podium" class="form-check-label">Podium</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="features[]" value="ramp" id="ramp" class="form-check-input">
                    <label for="ramp" class="form-check-label">Ramp</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="features[]" value="white_board" id="white_board" class="form-check-input">
                    <label for="white_board" class="form-check-label">White Board</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="features[]" value="blackboard" id="blackboard" class="form-check-input">
                    <label for="blackboard" class="form-check-label">Black Board</label>
                </div>
                <!-- Add other features -->
                 <!-- Add other features -->
                 

            </form>
        </div>

        <!-- Rooms Section -->
        <div class="col-md-10 my-light-div">
        <div class="col-md-12 mb-2" style="display: flex; justify-content: flex-end; align-items: center; width: 100%;">
    <label for="view_choice" style="margin-right: 10px; margin-bottom:10px; font-weight: bold; color:#0e00a3">View :</label>
    <select name="view_choice" id="view_choice" class="form-select" style="width: auto; display: flex; align-items: center;">
        <option value="with_image"> With Image</option>
        <option value="no_image" selected> Without Image</option>
        <option value="tbl"> Table</option>
    </select>
</div>
<div class="row" id="roomsContainer">

    </div>
</div>
</div>
<?php include 'assets/footer.php' ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const slotCheckboxes = document.querySelectorAll('.slot-checkbox');
    const sessionCheckboxes = document.querySelectorAll('input[name="session_choice"]');
    const clearButton = document.querySelector('#clear_button');

    // Function to update session checkboxes based on selected slots
    function updateSessionCheckboxes() {
        const selectedSlots = Array.from(slotCheckboxes).filter(checkbox => checkbox.checked).map(checkbox => parseInt(checkbox.value));
        const forenoonSelected = selectedSlots.every(slot => slot >= 1 && slot <= 4) && selectedSlots.length === 4;
        const afternoonSelected = selectedSlots.every(slot => slot >= 5 && slot <= 8) && selectedSlots.length === 4;

        // Update 'Forenoon' checkbox
        const fnCheckbox = document.querySelector('#fn');
        fnCheckbox.checked = forenoonSelected;

        // Update 'Afternoon' checkbox
        const anCheckbox = document.querySelector('#an');
        anCheckbox.checked = afternoonSelected;

        // Update both checkboxes if all slots are selected
        if (selectedSlots.length === 8) {
            fnCheckbox.checked = true;
            anCheckbox.checked = true;
        }
    }

    // Function to update slots based on session checkboxes
    function updateSlotsBasedOnSession() {
        const fnSelected = document.querySelector('#fn').checked;
        const anSelected = document.querySelector('#an').checked;

        slotCheckboxes.forEach(checkbox => {
            const slotValue = parseInt(checkbox.value);
            if (fnSelected && slotValue >= 1 && slotValue <= 4) {
                checkbox.checked = true;
            }
            if (anSelected && slotValue >= 5 && slotValue <= 8) {
                checkbox.checked = true;
            }
            if (!fnSelected && slotValue >= 1 && slotValue <= 4) {
                checkbox.checked = false;
            }
            if (!anSelected && slotValue >= 5 && slotValue <= 8) {
                checkbox.checked = false;
            }
        });
    }

    // Add event listeners to slot checkboxes
    slotCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSessionCheckboxes);
    });

    // Add event listeners to session checkboxes
    sessionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSlotsBasedOnSession);
    });

    // Add event listener to clear button
    clearButton.addEventListener('click', () => {
        slotCheckboxes.forEach(checkbox => checkbox.checked = false);
        sessionCheckboxes.forEach(checkbox => checkbox.checked = false);
    });

    // Initialize state on page load
    updateSessionCheckboxes();
});
</script>




<script>
$(document).ready(function() {

    // Trigger department loading if school is preselected
    var preselectedSchool = $('#school').val();
    var type_id = $('input[name="type_id"]:checked').val();

    if (preselectedSchool && type_id) {
        loadDepartments(preselectedSchool, type_id);
    }

    // Function to load departments based on school and hall type
    function loadDepartments(school_id, type_id) {
        $('#department').html('<option value="">Loading...</option>');
        $('#room').html('<option value="">Select Room</option>');

        $.ajax({
            type: 'POST',
            url: 'fetch_departments.php',
            data: { school_id: school_id, type_id: type_id },
            success: function (response) {
                $('#department').html(response);

                // If a department is already selected, trigger the change event
                var preselectedDepartment = $('#department').val();
                if (preselectedDepartment) {
                    loadRooms(preselectedDepartment, type_id);
                }
            },
            error: function (xhr, status, error) {
                console.error("Error fetching departments:", error);
                alert("Failed to fetch departments. Please try again.");
            }
        });
    }

    // Function to load rooms based on department and hall type
    function loadRooms(department_id, type_id) {
        $('#room').html('<option value="">Loading...</option>');

        $.ajax({
            type: 'POST',
            url: 'fetch_rooms.php',
            data: { department_id: department_id, type_id: type_id },
            success: function (response) {
                $('#room').html(response);
            },
            error: function (xhr, status, error) {
                console.error("Error fetching rooms:", error);
                alert("Failed to fetch rooms. Please try again.");
            }
        });
    }

    // Initialize date picker
    flatpickr("#date", {
        minDate: "today",
        dateFormat: "Y-m-d"
    });
    let today = new Date().toISOString().split('T')[0];
    $('#from-date').attr('min', today);

    // Ensure "To" date cannot be before "From" date
    $('#from-date').change(function () {
        let fromDate = $(this).val();
        $('#to-date').attr('min', fromDate);
        if ($('#to-date').val() < fromDate) {
            $('#to-date').val(fromDate);
        }
    });

    // Ensure "From" date cannot be after "To" date
    $('#to-date').change(function () {
        let toDate = $(this).val();
        let fromDate = $('#from-date').val();
        if (fromDate && fromDate > toDate) {
            $('#from-date').val(toDate);
        }
    });

    // Load departments based on selected school and hall type
    $('#school').change(function() {
        var school_id = $(this).val();
        var type_id = $('input[name="type_id"]:checked').val();

        $('#department').html('<option value="">All Departments</option>');
        $('#room').html('<option value="">Select Room</option>');

        if (school_id && type_id) {
            loadDepartments(school_id, type_id);
        }
    });

    // Load rooms based on selected department and hall type
    $('#department').change(function() {
        var department_id = $(this).val();
        var type_id = $('input[name="type_id"]:checked').val();

        if (department_id && type_id) {
            loadRooms(department_id, type_id);
        }
    });

    // Handle form submission

    $('#filterForm, #hallForm').on('submit', function(event) {           
        event.preventDefault();
        filterRooms();
    });

    $('#filterForm input, #filterForm select, #hallForm input').on('change', function () {
        filterRooms();
    });

    function filterRooms() {
    // Get selected view choice
    let viewChoice = $('#view_choice').val();
    // Serialize other form data
    let filterData = $('#filterForm').serialize();
    let hallData = $('#hallForm').serialize();

    // Combine all data, including view choice
    let formData = filterData + '&' + hallData + '&view_choice=' + viewChoice;

    // AJAX request to filter rooms
    $.ajax({
        url: 'filter_rooms.php',
        method: 'GET',
        data: formData,
        success: function (response) {
            $('#roomsContainer').html(response);
        },
        error: function () {
            alert('Failed to fetch room data. Please try again.');
        }
    });
}

$('#view_choice').change(function() {
    filterRooms();  // Call the filterRooms function to apply the new filter when dropdown is changed
});
    // Initial load of rooms
    filterRooms();

    // Clear Filters functionality
    $('#clearFilters').on('click', function() {
        // Reset hallForm
        $('#hallForm input[type="radio"]').prop('checked', false);
        filterRooms();
    });

    $('#clearFeatures').on('click', function() {
        // Reset hallForm
        $('#hallForm input[type="checkbox"]').prop('checked', false);
        filterRooms();
    });

});
// Slot selection logic
document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('.slot-checkbox');

    checkboxes.forEach((checkbox, index) => {
        checkbox.addEventListener('change', () => {
            const checkedIndices = Array.from(checkboxes)
                .map((cb, idx) => (cb.checked ? idx : -1))
                .filter(idx => idx !== -1);

            if (checkedIndices.length > 1) {
                const minIndex = Math.min(...checkedIndices);
                const maxIndex = Math.max(...checkedIndices);

                for (let i = minIndex; i <= maxIndex; i++) {
                    checkboxes[i].checked = true;
                }
            }
        });
    });
});

</script>
