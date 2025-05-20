<?php
include('assets/conn.php'); // Include your database connection
include 'assets/header.php';

$id = $_SESSION['user_id']; // Logged-in user's ID
$role = $_SESSION['role']; // User role from session
$department_id = $_SESSION['department_id']; // User department ID from session

// Fetch seminar halls (type_id = 2)
$query = "SELECT * FROM hall_details WHERE department_id = ? AND type_id = 1 ORDER BY hall_name ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result_seminar = $stmt->get_result();

// Fetch lecture halls (type_id = 3)
$query = "SELECT * FROM hall_details WHERE department_id = ? AND type_id = 3 ORDER BY hall_name ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result_lecture = $stmt->get_result();

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pondicherry University - Hall Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/design.css" />
    <style>

/* .sidenav {
    height: 100%;
    width: 0px;
    position: fixed;
    z-index: 1;
    top: 0;
    left: 0;
    background-color:rgb(0, 0, 0); 
    overflow-x: hidden;
    transition: 0.5s;
    padding-top: 150px;
}

.toggle-btn {
            position: fixed;
            top: 80px;
            left: 0px;
            background-color:rgb(0, 0, 0);
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 20px;
            cursor: pointer;
            border-radius: 0 5px 5px 0;
            z-index: 2;
            transition: 0.5s;
        }
        .toggle-btn.open {
            left: 0px;
        }
        #main {
      margin-left:0px;
      transition: margin-left .5s;
      padding: 16px;
      padding-top: 50px;
    } */

    #main {
      margin-left:250px;
      transition: margin-left .5s;
      padding: 16px;
      padding-top: 50px;
    }
        #split-view {
            display: flex;
            gap: 20px;
        }
        #cards-container {
            flex: 1;
        }
        #timetable-container {
            flex: 1;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        #timetable {
            width: 100%;
            border-collapse: collapse;
        }
        #timetable th, #timetable td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        #timetable th {
            background-color: #0e00a3;
            color: white;
        }
        #timetable td {
            background-color: white;
        }
        .card {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: scale(1.05);
        }
        .card-title {
            color: #0e00a3;
        }
        .table-wrapper {
            overflow-x: auto; /* Allow horizontal scrolling */
            max-width: 100%;
            padding: 20px;
        }

        #timetable-container {
            height:600px;
            overflow-y: auto;
}

#cards-container {
    overflow-y: auto;
            height:600px;
}
.card-body {
    padding: 15px;
}
#timetable {
        width: 100%;
        table-layout: fixed; /* Ensures equal width distribution */
    }

    #timetable th, #timetable td {
        text-align: center; /* Center align content */
        padding: 5px;
        white-space: nowrap; /* Prevent text wrapping */
    }
    #timetable td:first-child {
        font-size: 15px;

    }
    #timetable td:not(:first-child) {
    text-align: center; /* Center align horizontally */
    vertical-align: middle; /* Center align vertically */
    font-size: 10px; /* Reduce text size */
    font-weight: bold; /* Make text bold */
    text-transform: uppercase;
}

    #timetable th:first-child, 
    #timetable td:first-child {
        width: 10%; /* Small fixed width for Day/Time column */
    }

    #timetable th:not(:first-child), 
    #timetable td:not(:first-child) {
        width: auto; /* Equal width for all time slots */
    }
#message{
    text-align: center; /* Center align horizontally */
    vertical-align: middle; /* Center align vertically */
    font-size: 20px; /* Reduce text size */
    font-weight: bold; 
    color: red;
} 
.card.active {
    border: 2px solid #0e00a3; 
    box-shadow: 0px 0px 10px rgba(14, 0, 163, 0.5); 
}

</style>

</head>
<body>
<div id="main">
    <div class="table-wrapper mt-4">
    <div class="col-md-12">
        <div id="split-view" class="row">
            <!-- Left Side: Cards -->
            <div id="cards-container">
                <h5><b style="color: #0e00a3">Seminar Halls</b></h5>
                <div class="row">
                    <?php while ($row = $result_seminar->fetch_assoc()) { ?>
                        <div class="col-md-4 mb-4 d-flex">
                        <div class="card h-100 w-100" onclick="highlightCard(this); showTimetable('<?php echo $row['hall_id']; ?>', '<?php echo $row['hall_name']; ?>')">
                        <div class="card-body d-flex flex-column">
                                    <h6 class="card-title"><?php echo $row['hall_name']; ?></h6>
                                    <p class="card-text small mt-auto">Capacity: <?php echo $row['capacity']; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <br><h5><b style="color: #0e00a3">Class Halls</b></h5>
                <div class="row">
                    <?php 
                    $lhc_halls = []; // Store Lecture Hall Complex halls separately
                    while ($row = $result_lecture->fetch_assoc()) { 
                        if (strpos($row['hall_name'], 'LHC') === 0) {
                            $lhc_halls[] = $row; // Store LHC halls for later
                        } else { 
                    ?>
                            <div class="col-md-3 mb-4 d-flex">
                            <div class="card h-100 w-100" onclick="highlightCard(this); showTimetable('<?php echo $row['hall_id']; ?>', '<?php echo $row['hall_name']; ?>')">
                            <div class="card-body">
                                        <h6 class="card-title"><?php echo $row['hall_name']; ?></h6>
                                        <p class="card-text small">Capacity: <?php echo $row['capacity']; ?></p>
                                    </div>
                                </div>
                            </div>
                    <?php 
                        } 
                    } 
                    ?>
                </div>

                <?php if (!empty($lhc_halls)) { ?>
                    <br><h5><b style="color: #0e00a3">Lecture Hall Complex</b></h5>
                    <div class="row">
                        <?php foreach ($lhc_halls as $row) { ?>
                            <div class="col-md-3 mb-4 d-flex">
                            <div class="card h-100 w-100" onclick="highlightCard(this); showTimetable('<?php echo $row['hall_id']; ?>', '<?php echo $row['hall_name']; ?>')">
                            <div class="card-body">
                                        <h6 class="card-title"><?php echo $row['hall_name']; ?></h6>
                                        <p class="card-text small">Capacity: <?php echo $row['capacity']; ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>

            <!-- Right Side: Timetable (Initially Hidden) -->
           <!-- Right Side: Timetable & Form -->
           <div id="timetable-container">
           <div class="col-md-12 d-flex justify-content-between align-items-center">
    <div id="hall-details">Click the Hall to see it's timetable</div>
    <a id="view-button" href="#" class="btn btn-primary" style="display: none; padding: 0px 10px;">View</a>
</div>
    <table class="table table-bordered mt-2" id="timetable">
        <thead>
            <tr>
                <th>Day</th>
                <?php
                $times = ["1", "2", "3", "4", "5", "6", "7", "8"];
                foreach ($times as $time) {
                    echo "<th>{$time}</th>";
                }
                ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $days = ["Mon", "Tue", "Wed", "Thu", "Fri"];
            foreach ($days as $day) {
                echo "<tr>";
                echo "<td><b>{$day}</b></td>"; // First column for day
                foreach ($times as $time) {
                    echo "<td></td>"; // Empty slots to be filled dynamically
                }
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
    <div id="message" style="display:block;">Click the hall to view timetable</div>

    <div id="booking" style="display:none;">

    <!-- Booking Form -->
    <form action="save_schedule.php" method="POST">
    <?php
    $q = "SELECT * FROM semesters ORDER BY semester_id DESC LIMIT 1";
    $result1 = mysqli_query($conn, $q);
    $latestSemester = mysqli_fetch_assoc($result1);
    ?>

    <!-- Semester Start & End Date in the same row -->
    <div class="mb-3 d-flex gap-3">
    <?php
$today = date('Y-m-d'); // Get the current date
$semesterStartDate = isset($latestSemester['start_date']) && $latestSemester['start_date'] >= $today ? $latestSemester['start_date'] : $today;
?>

<div class="w-50">
    <label for="start_date" class="form-label">Semester Start Date:</label>
    <input type="date" id="start_date" name="start_date" class="form-control" value="<?= $semesterStartDate ?>" min="<?= $today ?>" onclick="this.showPicker()" required>
</div>

        <div class="w-50">
            <label for="end_date" class="form-label">Semester End Date:</label>
            <input type="date" id="end_date" name="end_date" class="form-control" value="<?= $latestSemester['end_date'] ?? '' ?>" onclick="this.showPicker()" required>
        </div>
    </div>

    <!-- Day of the Week as Radio Buttons in one line -->
    <div class="mb-3">
        <label class="form-label">Select Day:</label>
        <div class="d-flex gap-3">
            <?php
            $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
            foreach ($days as $day) {
                echo "<div class='form-check'>
                        <input class='form-check-input' type='radio' name='day_of_week' id='$day' value='$day' required>
                        <label class='form-check-label' for='$day'>$day</label>
                      </div>";
            }
            ?>
        </div>
    </div>

    <!-- Time Slots as Checkboxes (4 per row) -->
    <div class="mb-3">
    <label class="form-label">Select Time Slots:</label>
    <div class="d-flex flex-wrap justify-content-start gap-3">
        <?php
        $times = ["9:30 AM", "10:30 AM", "11:30 AM", "12:30 PM", "1:30 PM", "2:30 PM", "3:30 PM", "4:30 PM"];
        foreach ($times as $index => $time) {
            $value = $index+1;
            echo "<div class='form-check' style='width: 20%;'>
                    <input class='form-check-input' type='checkbox' name='slots[]' id='slot$index' value='$value'>
                    <label class='form-check-label' for='slot$index'>$time</label>
                  </div>";
        }
        ?>
    </div>
</div>
<div id="slot_error" style="color: red; display: none;"></div>

    </div>
    <div id="slot_error" style="color: red; display: none;"></div>

    <input type="hidden" name="hall_id" id="selected_hall">
    <input type="hidden" name="user_id" value="<?php echo isset($user_id) ? $user_id : ''; ?>">
    
    
    <div id="organiser_details" style="display: none;">

    <input type="hidden" name="purpose" value="class">
    <div class="form-label">
        <label for="event_type">Class :</label>
        <input type="text" name="event_type" id="event_type" class="form-control" required>
    </div>
    <div class="form-label">
        <label for="purpose_name">Course Code :</label>
        <input type="text" name="purpose_name" id="purpose_name" class="form-control" required>
    </div>



    <?php
// Assuming $user_id is set and you have a database connection ($conn)
$user_id = isset($user_id) ? $user_id : '';

// Step 1: Retrieve the department_id from the users table
$sql = "SELECT department_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($department_id);
$stmt->fetch();
$stmt->close();

// Step 2: Retrieve the department_name from the departments table using department_id
$department_name = '';
if ($department_id) {
    $sql = "SELECT department_name FROM departments WHERE department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $stmt->bind_result($department_name);
    $stmt->fetch();
    $stmt->close();
}
?>
<div class="mb-3 position-relative">
    <label for="organiser_department" class="form-label">Faculty's Department</label>
    <input type="text" class="form-control" id="organiser_department" name="organiser_department"
           value="<?php echo htmlspecialchars($department_name); ?>" required oninput="suggestDepartments(this.value)">
    <ul id="department_suggestions" class="list-group position-absolute w-100" style="display: none;"></ul>
</div>

<div class="mb-3">
    <label for="organiser_name" class="form-label">Faculty's Name</label>
    <input type="text" class="form-control" id="organiser_name" name="organiser_name" placeholder="Type to search..." required>
    <ul id="suggestions" class="list-group" style="display: none; "></ul>
</div>

<div class="mb-3">
    <label for="organiser_mobile" class="form-label">Faculty's Contact Number</label>
    <input type="text" class="form-control" id="organiser_mobile" name="organiser_mobile"  readonly required>
</div>

<div class="mb-3">
    <label for="organiser_email" class="form-label">Faculty's Email ID</label>
    <input type="text" class="form-control" id="organiser_email" name="organiser_email"  readonly required>
</div>



<input type="hidden" id="employee_id" name="employee_id">





    <button type="submit" class="btn btn-primary">Book Slot</button>
    </div>
</form>

</div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("#timetable tbody tr td:not(:first-child)").forEach(cell => {
        cell.addEventListener("click", function () {
            let row = this.parentElement;
            let dayShort = row.querySelector("td b").innerText.trim(); // Extract "Fri", "Mon", etc.
            
            // Map short day names to full names
            let dayMap = {
                "Mon": "Monday",
                "Tue": "Tuesday",
                "Wed": "Wednesday",
                "Thu": "Thursday",
                "Fri": "Friday"
            };

            let dayFull = dayMap[dayShort] || dayShort; // Convert to full name

            let timeIndex = Array.from(row.children).indexOf(this) - 1;

            let times = ["9:30 AM", "10:30 AM", "11:30 AM", "12:30 PM", "1:30 PM", "2:30 PM", "3:30 PM", "4:30 PM"];

            if (timeIndex >= 0 && timeIndex < times.length) {
                let dayRadio = document.querySelector(`input[name='day_of_week'][value='${dayFull}']`);
                if (dayRadio) {
                    dayRadio.checked = true;
                } else {
                    console.error("Day radio button not found for:", dayFull);
                }

                let timeCheckbox = document.querySelector(`input[name='slots[]'][value='${timeIndex + 1}']`);
                if (timeCheckbox) {
                    timeCheckbox.checked = !timeCheckbox.checked;
                } else {
                    console.error("Time slot checkbox not found for index:", timeIndex + 1);
                }
            }
            checkSlots();
        });
    });
});
</script>


<script>

document.querySelectorAll('input[name="slots[]"]').forEach(slot => {
                slot.addEventListener('click', function () {
                    autoSelectSlots(slot);
                });
            });

            function autoSelectSlots(checkbox) {
            const slots = document.querySelectorAll('input[name="slots[]"]');
            let start = null, end = null;

            slots.forEach((slot, index) => {
                if (slot.checked) {
                    if (start === null) start = index;
                    end = index;
                }
            });

            if (start !== null && end !== null) {
                for (let i = start; i <= end; i++) {
                    slots[i].checked = true;
                }
            }
            checkSlots();
        }


    document.querySelector('form').addEventListener('submit', function(e) {
    // Check if at least one checkbox is selected
    const selectedSlots = document.querySelectorAll('input[name="slots[]"]:checked');
    if (selectedSlots.length === 0) {
        // Prevent form submission
        e.preventDefault();
        
        // Show error message
        document.getElementById('slot_error').innerHTML = "Please select at least one time slot.";
        document.getElementById('slot_error').style.display = 'block';
    } else {
        // Hide error message if slots are selected
        document.getElementById('slot_error').style.display = 'none';
    }
});
document.addEventListener('DOMContentLoaded', () => {
    // Assuming your date inputs have the following IDs
    const startDateInput = document.getElementById('start_date');
    const endDateInput   = document.getElementById('end_date');

    // Attach event listeners to both date inputs
    startDateInput.addEventListener('change', updateTimetable);
    endDateInput.addEventListener('change', updateTimetable);
});

function updateTimetable() {
    // Retrieve stored hall information
    const hallId = document.getElementById("selected_hall").value;
    const hallName = document.getElementById("hall-details").textContent; // Adjust as necessary

    if (hallId) {
        showTimetable(hallId, hallName);
    }
}
    function showTimetable(hallId, hallName) {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        if (hallName) {
            document.getElementById("hall-details").innerHTML = `<b>${hallName}</b>`;
            document.getElementById("view-button").href = `view_cal.php?hall_id=${hallId}`;
            document.getElementById("view-button").style.display = "block"; // Show button
        } else {
            document.getElementById("hall-details").innerHTML = "";
            document.getElementById("view-button").style.display = "none"; // Hide button
        }

        document.getElementById("selected_hall").value = hallId; // Store hall ID for form submission
        document.getElementById('booking').style.display = 'block';
        document.getElementById('message').style.display = 'none';

        // Clear previous timetable entries
        document.querySelectorAll("#timetable tbody td:not(:first-child)").forEach(cell => cell.innerHTML = "");

        fetch(`fetch_timetable.php?hall_id=${hallId}&start_date=${startDate}&end_date=${endDate}`)
            .then(response => response.text())
            .then(data => {
                try {
                    const jsonData = JSON.parse(data);
                    if (jsonData.error) {
                        console.error('Error from server:', jsonData.error);
                        return;
                    }

                    const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
                    const timeSlots = ["9:30 AM", "10:30 AM", "11:30 AM", "12:30 PM", "1:30 PM", "2:30 PM", "3:30 PM", "4:30 PM"];

                    for (const [day, slots] of Object.entries(jsonData)) {
                        let rowIndex = days.indexOf(day) + 1;
                        if (rowIndex > 0) {
                            for (const [slot, purpose] of Object.entries(slots)) {
                                let colIndex = parseInt(slot);
                                document.querySelector(`#timetable tbody tr:nth-child(${rowIndex}) td:nth-child(${colIndex + 1})`).innerHTML = purpose;
                            }
                        }
                    }
                } catch (error) {
                    console.error("Error parsing JSON:", error);
                    console.log("Raw data:", data);
                }
            })
            .catch(error => console.error("Error fetching timetable:", error));

        checkSlots();
    }

//         function toggleNav() {
//             const sidenav = document.getElementById("mySidenav");
//             const toggleBtn = document.querySelector(".toggle-btn");

//             if (sidenav.style.width === "250px") {
       
//                 sidenav.style.width = "0px"; // Open sidebar
//         toggleBtn.style.left = "0px"; // Position button to the right of the open sidebar
//         document.getElementById("main").style.marginLeft = "0px";
//         toggleBtn.classList.remove("open");
//       } else {
//         sidenav.style.width = "250px"; // Close sidebar
//         toggleBtn.style.left = "200px"; // Move button to the left
//         document.getElementById("main").style.marginLeft= "250px";

//         toggleBtn.classList.add("open");
//     }
//         } 
   
const organiserInput = document.getElementById('organiser_name');
const suggestionsBox = document.getElementById('suggestions');
const mobileField = document.getElementById('organiser_mobile');
const emailField = document.getElementById('organiser_email');
const employeeIdField = document.getElementById('employee_id');

let currentIndex = -1; // Tracks the currently highlighted suggestion
let previousInputLength = 0; // Track the previous input length

// Event listener for when the name input changes
organiserInput.addEventListener('input', function () {
    const query = this.value.trim();
    
    // Check if the length of input is smaller than the previous length (indicating a deletion)
    if (query.length < previousInputLength) {
        // Clear the fields if a letter is deleted
        mobileField.value = '';
        emailField.value = '';
        employeeIdField.value = '';
    }

    // Update the previous input length for the next input event
    previousInputLength = query.length;

    if (query.length > 0) {
        fetch('get_employee.php?department=<?php echo urlencode($department_name); ?>&query=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                suggestionsBox.innerHTML = '';
                if (data.length > 0) {
                    suggestionsBox.style.display = 'block';
                    currentIndex = -1; // Reset the index when new suggestions are loaded
                    data.forEach((employee, index) => {
                        const suggestion = document.createElement('li');
                        suggestion.textContent = employee.employee_name;
                        suggestion.className = 'list-group-item';
                        suggestion.style.cursor = 'pointer';
                        suggestion.setAttribute('data-index', index); // Add an index for reference
                        suggestion.setAttribute('data-id', employee.employee_id); // Store the employee_id

                        // Click event for mouse interaction
                        suggestion.addEventListener('click', () => {
                            organiserInput.value = employee.employee_name;
                            suggestionsBox.style.display = 'none';
                            fetchEmployeeDetails(employee.employee_id);  // Pass employee ID to fetch details
                        });

                        suggestionsBox.appendChild(suggestion);
                    });
                } else {
                    suggestionsBox.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error fetching suggestions:', error);
            });
    } else {
        suggestionsBox.style.display = 'none';
    }
});

// Keyboard navigation for suggestions
organiserInput.addEventListener('keydown', function (e) {
    const suggestions = suggestionsBox.querySelectorAll('li');
    if (suggestions.length > 0) {
        if (e.key === 'ArrowDown') {
            // Move down
            e.preventDefault();
            if (currentIndex < suggestions.length - 1) {
                currentIndex++;
                updateHighlight(suggestions);
            }
        } else if (e.key === 'ArrowUp') {
            // Move up
            e.preventDefault();
            if (currentIndex > 0) {
                currentIndex--;
                updateHighlight(suggestions);
            }
        } else if (e.key === 'Enter') {
            // Select highlighted suggestion
            e.preventDefault();
            if (currentIndex >= 0 && currentIndex < suggestions.length) {
                organiserInput.value = suggestions[currentIndex].textContent;
                suggestionsBox.style.display = 'none';
                const employeeId = suggestions[currentIndex].getAttribute('data-id');
                fetchEmployeeDetails(employeeId);  // Pass the employee ID to fetch details
            }
        }
    }
});

// Function to update highlight
function updateHighlight(suggestions) {
    suggestions.forEach((suggestion, index) => {
        if (index === currentIndex) {
            suggestion.classList.add('active'); // Highlight the current suggestion
            suggestion.style.backgroundColor = '#007bff'; // Optional: Add a visual highlight
            suggestion.style.color = '#fff'; // Optional: Change text color
        } else {
            suggestion.classList.remove('active');
            suggestion.style.backgroundColor = ''; // Reset styles
            suggestion.style.color = ''; // Reset styles
        }
    });
}

// Close suggestions box on outside click
document.addEventListener('click', function (e) {
    if (!suggestionsBox.contains(e.target) && e.target !== organiserInput) {
        suggestionsBox.style.display = 'none';
    }
});

// Fetch employee details based on employee_id
function fetchEmployeeDetails(employeeId) {
    if (!employeeId || employeeId <= 0) {
        console.error('Invalid employee_id:', employeeId);
        return;
    }

    fetch('get_employee_details.php?employee_id=' + encodeURIComponent(employeeId))
        .then(response => response.text()) // Get raw text response
        .then(data => {
            try {
                const jsonData = JSON.parse(data); // Parse the JSON
                if (jsonData && !jsonData.error) {
                    // Autofill the mobile, email, and employee_id fields
                    mobileField.value = jsonData.employee_mobile || '';
                    emailField.value = jsonData.employee_email || '';
                    employeeIdField.value = jsonData.employee_id || ''; // Autofill the employee_id
                    triggerDuplicateCheck();
                    // Automatically jump to the next field after autofilling
                    if (mobileField.value) {
                        emailField.focus();
                    } else if (emailField.value) {
                        document.getElementById('start_date').focus();
                    }
                    
                } else {
                    console.error('Error fetching employee details:', jsonData.error || 'Unknown error');
                }
            } catch (error) {
                console.error('Error parsing JSON response:', error);
            }
        })
        .catch(error => {
            console.error('Error fetching employee details:', error);
        });
}
function highlightCard(selectedCard) {
    document.querySelectorAll('.card').forEach(card => card.classList.remove('active'));
    selectedCard.classList.add('active');
}





document.addEventListener("DOMContentLoaded", function () {
    // Get elements
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const hallSelect = document.getElementById('selected_hall');

    // Add event listeners for date inputs
    if (startDate) startDate.addEventListener('change', checkSlots);
    if (endDate) endDate.addEventListener('change', checkSlots);
    if (hallSelect) hallSelect.addEventListener('change', checkSlots);

    // Use event delegation for dynamically loaded radio buttons and checkboxes
    document.addEventListener('change', function (event) {
        if (
            event.target.matches('input[name="day_of_week"]') || 
            event.target.matches('input[name="slots[]"]')
        ) {
            checkSlots();
        }
    });
});
function checkSlots() {
    const day = document.querySelector('input[name="day_of_week"]:checked');
    const selectedSlots = Array.from(document.querySelectorAll('input[name="slots[]"]:checked')).map(checkbox => checkbox.value);
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const hallId = document.getElementById('selected_hall').value;

    // Ensure all required fields are selected
    if (!day || selectedSlots.length === 0 || !startDate || !endDate || !hallId) {
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'check_slot_booking.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    const params = new URLSearchParams();
    params.append('day_of_week', day.value);
    params.append('slots', selectedSlots.join(','));
    params.append('start_date', startDate);
    params.append('end_date', endDate);
    params.append('hall_id', hallId);

    xhr.send(params.toString());

    xhr.onload = function () {
    if (xhr.status === 200) {
        try {
            const response = JSON.parse(xhr.responseText);

            // Ensure response.error is an array (in case it's not)
            if (Array.isArray(response.error) && response.error.length > 0) {
                document.getElementById('slot_error').innerHTML = response.error.join("<br>");
                document.getElementById('slot_error').style.display = 'block';
                document.getElementById('organiser_details').style.display = 'none';
            } else if (response.error && typeof response.error === 'string') {
                // If error is a string, display it directly
                document.getElementById('slot_error').innerHTML = response.error;
                document.getElementById('slot_error').style.display = 'block';
                document.getElementById('organiser_details').style.display = 'none';
            } else {
                // No error, show organiser details
                document.getElementById('slot_error').style.display = 'none';
                document.getElementById('organiser_details').style.display = 'block';
            }
        } catch (e) {
            console.error("Failed to parse JSON response:", e);
        }
    }
};
}

const today = new Date().toISOString().split('T')[0];

// Retrieve the DOM elements, NOT their values
const startDateInput = document.getElementById('start_date');
const endDateInput = document.getElementById('end_date');

// Ensure both inputs have a minimum value of today
startDateInput.setAttribute('min', today);
endDateInput.setAttribute('min', today);

startDateInput.addEventListener("input", function() {
  // Get the selected start date
  const startDate = new Date(startDateInput.value);

  // If the end date is empty or set to a date before the start date, update it to match the start date.
  if (!endDateInput.value || new Date(endDateInput.value) < startDate) {
      endDateInput.value = startDateInput.value;
  }

  // Update the min attribute of the end date to ensure it cannot be set to a day before the start date.
  endDateInput.setAttribute('min', startDateInput.value);
});

endDateInput.addEventListener("input", function() {
  // If there's no start date, fill the start date with the value of the end date.
  if (!startDateInput.value) {
      startDateInput.value = endDateInput.value;
  }
  // If the selected end date is before the start date, reset it to the start date.
  else if (new Date(endDateInput.value) < new Date(startDateInput.value)) {
      endDateInput.value = startDateInput.value;
  }
});

</script>
<script>
function suggestDepartments(query) {
    const suggestionsList = document.getElementById("department_suggestions");

    if (query.trim().length === 0) {
        suggestionsList.style.display = "none";
        return;
    }

    fetch('get_departments.php?query=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
            suggestionsList.innerHTML = '';

            if (data.length > 0) {
                data.forEach(department => {
                    const listItem = document.createElement('li');
                    listItem.textContent = department;
                    listItem.className = 'list-group-item list-group-item-action';
                    listItem.onclick = function() {
                        document.getElementById('organiser_department').value = department;
                        suggestionsList.style.display = 'none';
                    };
                    suggestionsList.appendChild(listItem);
                });
                suggestionsList.style.display = 'block';
            } else {
                suggestionsList.style.display = 'none';
            }
        })
        .catch(error => console.error('Error fetching department suggestions:', error));
}
</script>