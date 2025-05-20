<?php
include('assets/conn.php');
include 'assets/header.php';

$hall_id = $_GET['hall_id'] ?? '';
$school_name = $_GET['school_name'] ?? '';
$department_name = $_GET['department_name'] ?? '';
$hall_name = $_GET['hall_name'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$booking_type = trim($_GET['booking_type'] ?? '');
$session_choice = trim($_GET['session_choice'] ?? $_POST['session_choice'] ?? '');
$selectedSlots = $_GET['slots'] ?? $_POST['slots'] ?? []; // Handle slots as an array

// Convert slots to an array if it's a comma-separated string (GET method)
if (is_string($selectedSlots)) {
    $selectedSlots = explode(',', $selectedSlots);
}

$sql = "SELECT h.*, s.school_name, d.department_name
FROM hall_details h
LEFT JOIN schools s ON h.school_id = s.school_id
LEFT JOIN departments d ON h.department_id = d.department_id
WHERE h.hall_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo '<p>Database error: ' . htmlspecialchars($conn->error) . '</p>';
    exit;
}
$stmt->bind_param("i", $hall_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $hall = $result->fetch_assoc();
} else {
    echo '<p>Hall not found.</p>';
    exit;
}
function getDaysInMonth($year, $month)
{
    return date('t', mktime(0, 0, 0, $month, 1, $year));
}

function getBookedSlots($conn, $hall_id, $date)
{
    $query = "SELECT slot_or_session, status, booking_id_gen,booking_date,students_count, organiser_name, organiser_email, organiser_department, organiser_mobile, organiser_department, purpose, event_type, purpose_name 
              FROM bookings 
              WHERE hall_id = ? 
              AND ? BETWEEN start_date AND end_date
              AND status IN ('approved', 'pending')";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $hall_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $bookedSlots = [];
    while ($row = $result->fetch_assoc()) {
        $slots = explode(',', $row['slot_or_session']);
        foreach ($slots as $slot) {
            $organiserDetails = [
                'id_gen' => htmlspecialchars($row['booking_id_gen']),
                'date' => htmlspecialchars($row['booking_date']),
                'name' => htmlspecialchars($row['organiser_name']),
                'department' => htmlspecialchars($row['organiser_department']),
                'mobile' => htmlspecialchars($row['organiser_mobile']),
                'email' => htmlspecialchars($row['organiser_email']),
                'dept' => htmlspecialchars($row['organiser_department']),
                'capacity' => htmlspecialchars($row['students_count']),
                'purpose' => htmlspecialchars($row['purpose']),
                'event_type' => htmlspecialchars($row['event_type']),
                'purpose_name' => htmlspecialchars($row['purpose_name'])
            ];
            $bookedSlots[] = [
                'slot' => intval($slot),
                'status' => $row['status'],
                'organiserDetails' => $organiserDetails
            ];
        }
    }
    return $bookedSlots;
}

$calendar = [];
$currentYear = date('Y');
$currentMonth = date('n'); // Get the current month as an integer (1-12)
$monthsToView = 3; // Number of months to display (including the current month)

// Loop through the months
for ($month = $currentMonth; $month < $currentMonth + $monthsToView; $month++) {
    // If the month exceeds 12, move to the next year
    $adjustedMonth = $month > 12 ? $month - 12 : $month;
    $adjustedYear = $month > 12 ? $currentYear + 1 : $currentYear;

    $daysInMonth = getDaysInMonth($adjustedYear, $adjustedMonth);

    $monthCalendar = [
        'year' => $adjustedYear,
        'month' => $adjustedMonth,
        'days' => []
    ];

    // Loop through the days in the current month
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf("%04d-%02d-%02d", $adjustedYear, $adjustedMonth, $day);
        $bookedSlots = getBookedSlots($conn, $hall_id, $date);
        $monthCalendar['days'][] = [
            'date' => $date,
            'bookedSlots' => $bookedSlots
        ];
    }

    $calendar[] = $monthCalendar;
}

$calendarJson = json_encode($calendar, JSON_PRETTY_PRINT);
$currentDateTime = date('Y-m-d H:i:s');

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall Booking - Check Availability</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/design.css" />
    <style>
        h3,
        h4,
        h5 {
            font-family: "Lato", sans-serif;

        }

        .calendar-time-column {
            width: 80px;
            /* Set fixed width for the time column */
            text-align: left;
            padding: 2px;
            font-size: 0.8rem;
        }

        .calendar-cell {
            width: 20px;
            height: 20px;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            margin: 2px;
            font-size: 0.9rem;
        }

        .calendar-time-row {
            display: flex;
            flex-direction: row;
        }

        /* Available cells (green) */
        .calendar-cell.available {
            background-color: rgb(85, 255, 122);
            /* Available */
            border: 1px solid black;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s ease-in-out;
        }

        .calendar-cell.available:hover {
            background-color: rgb(20, 255, 71);
            /* Hover color for available */
            border: 1px solid black;
            transform: scale(1.05);
            /* Slight scale effect */
            box-shadow: 0 2px 5px rgba(0, 180, 39, 0.6);
            /* Subtle shadow effect */
            color: white;
            /* Change text color on hover */
        }

        /* Pending cells (yellow) */
        .calendar-cell.pending {
            background-color: rgb(244, 255, 91);
            /* Pending */
            border: 1px solid black;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s ease-in-out;
        }

        .calendar-cell.pending:hover {
            background-color: rgb(242, 255, 0);
            /* Hover color for pending */
            border: 1px solid black;
            transform: scale(1.05);
            /* Slight scale effect */
            box-shadow: 0 2px 5px rgba(243, 255, 6, 0.6);
            /* Subtle shadow effect */
            color: black;
            /* Change text color on hover */
        }

        /* Approved cells (red) */
        .calendar-cell.approved {
            background-color: rgb(255, 103, 115);
            /* Booked */
            border: 1px solid black;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s ease-in-out;
        }

        .calendar-cell.approved:hover {
            background-color: rgb(255, 40, 58);
            /* Hover color for approved */
            border: 1px solid black;
            transform: scale(1.05);
            /* Slight scale effect */
            box-shadow: 0 2px 5px rgba(255, 40, 58, 0.6);
            /* Subtle shadow effect */
            color: white;
            /* Change text color on hover */
        }



        /* Past cells */
        .calendar-cell.past {
            background-color: rgb(162, 162, 162);
            /* Past */
            border: 1px solid black;
            cursor: not-allowed;
            /* Disable active state */
        }

        /* White cells */
        .white-cell {
            background-color: white;
            /* border: 1px solid #ddd; */
            cursor: none;

        }

        .calendar-cell.whitish-cell {
            background-color: rgba(255, 255, 200, 0.3);
            /* Light yellow overlay */
            border: 1px solid #f0e68c;
            /* Optional border */
        }

        /* Apply whitish-cell class to the statuses as well */

        .calendar-cell.approved.whitish-cell {
            background-color: rgba(255, 0, 0, 0.58);
            /* Approved with weekend overlay */
        }

        .calendar-cell.pending.whitish-cell {
            background-color: rgba(255, 251, 0, 0.48);
            /* Pending with weekend overlay */
        }

        .calendar-cell.available.whitish-cell {
            background-color: rgba(144, 238, 144, 0.64);
            /* Available with weekend overlay */
        }

        /* Past cells with light color */
        .calendar-cell.past.whitish-cell {
            background-color: rgba(162, 162, 162, 0.5);
            /* Past with weekend overlay */
            cursor: not-allowed;
        }

        .feature-icon {
            font-size: 1.2em;
            color: #007bff;
        }

        .day-name {
            font-size: 0.6em;
        }

        .calendar-cell.weekend-cell {
            /* background-color:rgb(212, 212, 212);  */
            color: #333;
            /* Optional: Adjust text color for better contrast */
        }

        .calendar-cell.white-cell {
            background-color: #ffffff;
        }

        .weekend-cell {
            /* background-color: #f0f0f0;  */
            color: red;
            /* Change the text color for weekends */
        }

        .day-number.weekend-cell,
        .day-name.weekend-cell {
            color: red;
            /* Apply same color to both day and name */
        }


        /* Make the time column's width fixed */
        .time-slot-row {
            display: flex;
            justify-content: flex-start;
            align-items: center;
        }

        .time-slot-row:first-of-type {
            margin-bottom: 20px;
        }

        /* For calendar cells, use flex to ensure layout alignment */
        .time-slot-container {
            display: flex;
            flex-direction: column;
        }


        /* Status indicator for approved and pending */
        .status-indicator {
            display: none;
            /* Hide the status indicator */
        }

        /* Container for the month header */
        .month-header {
            display: flex;
            justify-content: space-between;
            /* Space between elements (buttons and month name) */
            align-items: center;
            /* Align items vertically */
            width: 100%;
            /* Make the header take up full width */
            max-width: 300px;
            /* Optional: You can set a max-width if you want a limit */
            margin: 0 auto;
            /* This centers the container horizontally */
        }

        /* Style for the buttons */
        .month-header button {
            font-size: 1.5rem;
            padding: 5px 10px;
            cursor: pointer;
            border: 0px;
            background-color: transparent;
        }

        /* Style for the disabled button */
        .month-header button:disabled {
            cursor: not-allowed;
        }


        #calendar-container {

            display: flex;
            justify-content: center;
            align-items: center;
        }


        #organizer-details-heading {
            display: block;
            /* Keep heading visible */
        }

        .details-container {
            width: 100%;
            /* Set width to 80% */
            padding: 0;
            /* Remove any padding */
        }

        #organizer-details-table {
            width: 100%;
            /* Set width to 80% */
            padding: 0;
            /* Remove any padding */
        }

        #organizer-details-table th,
        #organizer-details-table td {
            margin: 0;
            /* Remove any margin */
            text-align: center;
            /* Center-align text */
            padding: 5px;
            font-size: 0.9rem;

            white-space: normal;
            /* Allow text to wrap */
            word-wrap: break-word;
            /* Break long words if necessary */
            overflow: visible;
            /* Ensure all content is shown */
        }

        #main {
            margin-left: 250px;
            transition: margin-left .5s;
            padding: 16px;
            padding-top: 50px;
        }

        .calendar-legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            /* Centers the legend items horizontally */
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-right: 20px;
        }

        .legend-color {
            width: 15px;
            height: 15px;
            margin-right: 8px;
            border-radius: 3px;
            /* Optional: rounds the corners for a softer look */
        }

        .legend-item span {
            font-size: 14px;
            color: #333;
            /* Text color for the legend items */
        }

        .error-message {
            color: red;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .warning-message {
            color: orange;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .activeAttachment {
            background-color: rgb(0, 123, 255) !important;
            box-shadow: 0 0 5px rgba(4, 170, 253, 0.5);
            transform: scale(1.05);
            /* Slight scale effect */
        }

        .error {
            color: red;
        }

        .success {
            color: green;
        }

        .modal {
            display: none;
            /* Hidden by default */
            position: fixed;
            z-index: 10;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            /* Background overlay */
        }
        .close-modal {
            color: #aaa;
            margin-right: 20px;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            /* Position relative to the modal-content */
            top: 10px;
            /* Adjust the distance from the top */
            right: 10px;
            /* Adjust the distance from the right */
            cursor: pointer;
        }

        .close-modal:hover,
        .close-modal:focus {
            color: black;
            text-decoration: none;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 30%;
            position: relative;
            /* This is needed for absolute positioning of the close button */
        }

    </style>
</head>

<body>

    <div id="main">
        <div class="row justify-content-center">
            <div class="col-md-9 mt-5">
                <div class="col-md-12">
                    <div class="card shadow-lg">
                        <div class="card-body">
                            <center>
                                <h3 style="color:#0e00a3">Hall Booking</h3><br>
                            </center>
                            <form id="userDetailsForm" method="POST" action="confirm_book.php"
                                onsubmit="return validateForm()" enctype="multipart/form-data">
                                <input type="hidden" name="user_id"
                                    value="<?php echo isset($user_id) ? $user_id : ''; ?>">
                                <div class="mb-4">
                                    <div class="form-group mb-3">
                                        <label class="form-label" style="font-weight:normal;">Hall Type</label><br>
                                        <div class="btn-group" role="group" aria-label="Hall Type">
                                            <input type="radio" class="btn-check" name="type_id" id="seminar" value="1"
                                                <?php echo (isset($_GET['type_id']) && $_GET['type_id'] == '1') ? 'checked' : ''; ?> disabled>
                                            <label class="btn btn-outline-primary" for="seminar">Seminar Hall</label>

                                            <input type="radio" class="btn-check" name="type_id" id="auditorium"
                                                value="2" <?php echo (isset($_GET['type_id']) && $_GET['type_id'] == '2') ? 'checked' : ''; ?> disabled>
                                            <label class="btn btn-outline-primary" for="auditorium">Auditorium</label>

                                            <input type="radio" class="btn-check" name="type_id" id="lecture" value="3"
                                                <?php echo (isset($_GET['type_id']) && $_GET['type_id'] == '3') ? 'checked' : ''; ?> disabled>
                                            <label class="btn btn-outline-primary" for="lecture">Lecture Hall</label>

                                            <input type="radio" class="btn-check" name="type_id" id="conference"
                                                value="4" <?php echo (isset($_GET['type_id']) && $_GET['type_id'] == '4') ? 'checked' : ''; ?> disabled>
                                            <label class="btn btn-outline-primary" for="conference">Conference
                                                Hall</label>
                                        </div>
                                    </div>
                                    <input type="hidden" name="hall_id" value="<?php echo $hall_id; ?>">

                                    <div class="form-group">
                                        <label for="school_name">School</label>
                                        <input type="text" name="school_name" id="school_name" class="form-control"
                                            value="<?php echo $school_name; ?>" readonly>
                                    </div>

                                    <div class="form-group">
                                        <label for="department_name">Department</label>
                                        <input type="text" name="department_name" id="department_name"
                                            class="form-control" value="<?php echo $department_name; ?>" readonly>
                                    </div>

                                    <div class="form-group">
                                        <label for="hall">Hall Name</label>
                                        <input type="text" name="hall" id="hall" class="form-control"
                                            value="<?php echo $hall_name; ?>" readonly>
                                    </div>
                                </div>
                                <div class="details-container">
                                    <div class="calendar-container" id="calendar">

                                        <center>
                                            <div id="calendar-container"></div>
                                        </center>

                                        <div id="organizer-details-container">
                                            <span style="display: none;" id="organizer-details-heading"><b>Booking
                                                    Details: </b></span>
                                            <br>
                                            <table id="organizer-details-table" border="1"
                                                style="display: none; margin: 0 auto 20px auto;"
                                                class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th style="width:30%">Organiser Details</th>
                                                        <th style="width:40%">Purpose</th>
                                                        <th style="width:10%">Participants</th>
                                                        <th style="width:20%">Booked on</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>


                                    <div id="booking" style="display:block;">
                                        <div class="mb-2">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="start_date" class="form-label">From:</label>
                                                    <input type="date" name="start_date" id="start_date"
                                                        class="form-control" onclick="this.showPicker()"
                                                        onchange="checkAvailability()" required>
                                                    <div id="start_date_error" class="error-message"></div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="end_date" class="form-label">To:</label>
                                                    <input type="date" name="end_date" id="end_date"
                                                        class="form-control" onclick="this.showPicker()"
                                                        onchange="checkAvailability()" required>
                                                    <div id="end_date_error" class="error-message"></div>
                                                </div>
                                            </div>
                                        </div>



                                        <!-- Slot Options -->
                                        <div class="form-group mb-3" id="slot_options">
                                            <label class="form-label">Choose Slot(s):</label>
                                            <div class="btn-group w-100" role="group">
                                                <input class="btn-check session-checkbox" type="checkbox" id="fn"
                                                    value="fn" autocomplete="off">
                                                <label class="btn btn-outline-primary" for="fn">Forenoon</label>
                                                <input class="btn-check session-checkbox" type="checkbox" id="an"
                                                    value="an" autocomplete="off">
                                                <label class="btn btn-outline-primary" for="an">Afternoon</label>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <?php
                                            $slots = [
                                                1 => '09:30am',
                                                2 => '10:30am',
                                                3 => '11:30am',
                                                4 => '12:30pm',
                                                5 => '01:30pm',
                                                6 => '02:30pm',
                                                7 => '03:30pm',
                                                8 => '04:30pm'
                                            ];
                                            foreach ($slots as $slotId => $slotLabel) {
                                                echo "
                                <div class='col-md-3 mb-2'>
                                    <div class='form-check'>
                                        <input class='form-check-input slot-checkbox' type='checkbox' name='slots[]' id='slot{$slotId}' value='{$slotId}'>
                                        <label class='form-check-label' for='slot{$slotId}'>{$slotLabel}</label>
                                    </div>
                                </div>";
                                            }
                                            ?>
                                        </div>



                                        <div id="slot_warning" class="warning-message"></div>
                                        <center>
                                            <div id="availability_message"></div>
                                        </center>
                                        <span id="booking2" style="display:none;">

                                            <div class="mb-3">
                                                <label class="form-label">Purpose of Booking</label>
                                                <div class="btn-group w-100" role="group"
                                                    aria-label="Purpose of Booking">
                                                    <input type="radio" class="btn-check" id="purpose_event"
                                                        name="purpose" value="event" required>
                                                    <label class="btn btn-outline-primary"
                                                        for="purpose_event">Event</label>
                                                    <input type="radio" class="btn-check" id="purpose_class"
                                                        name="purpose" value="class" required>
                                                    <label class="btn btn-outline-primary"
                                                        for="purpose_class">Class</label>
                                                </div>
                                            </div>

                                            <div class="mb-3" id="event-type-group" style="display: none;">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="event_type" class="form-label">Event Type</label>
                                                        <select class="form-select" id="event_type" name="event_type">
                                                            <option value="guest_lectures_seminars">Guest Lectures,
                                                                Seminars</option>
                                                            <option value="meetings_ceremonies">Meetings, Ceremonies
                                                            </option>
                                                            <option value="workshops_training">Workshops, Training
                                                            </option>
                                                            <option value="conferences_symposiums">Conferences,
                                                                Symposiums</option>
                                                            <option value="examinations_admissions_interviews">
                                                                Examinations, Admissions, Interviews</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="event_invitation" class="form-label">Upload
                                                            Invitation</label>
                                                        <input class="form-control" type="file" id="event_invitation"
                                                            name="event_invitation" accept=".jpeg, .jpg, .png, .pdf"
                                                            onchange="validateFileSize()" />
                                                        <small id="file-size-message">Supported formats: JPEG, JPG, PNG,
                                                            PDF. Max size: 1MB.</small>
                                                    </div>
                                                </div>
                                            </div>




                                            <div class="mb-3">
                                                <label for="purpose_name" class="form-label">Name of the
                                                    Event/Programme</label>
                                                <textarea class="form-control" id="purpose_name" name="purpose_name"
                                                    rows="3" required></textarea>
                                            </div>


                                            <div class="mb-3">
                                                <label for="students_count" class="form-label">Number of Participants
                                                    Expected</label> (Maximum - <?php echo $hall['capacity']; ?>)
                                                <input type="number" class="form-control" id="students_count"
                                                min="5"
                                                    name="students_count" onchange="checkCapacity()" required>
                                                <div id="alert" style="display: none; color: red;" role="alert">
                                                    The number of participants exceeds the hall capacity -
                                                    <?php echo $hall['capacity']; ?>.
                                                </div>
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


                                            if (isset($_SESSION['user_id'])) {
                                                $user_id = $_SESSION['user_id'];  // Retrieve the user_id from the session
                                                $user_role = $_SESSION['role'];
                                                $username = $_SESSION['username'];

                                                // Fetch the school_id for the user from the 'users' table
                                                $school_query = "SELECT department_id FROM users WHERE user_id = ?";
                                                $stmt = $conn->prepare($school_query);

                                                if ($stmt === false) {
                                                    die('Prepare failed: ' . htmlspecialchars($conn->error));
                                                }

                                                $stmt->bind_param("i", $user_id);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                $user_data = $result->fetch_assoc();

                                                if ($user_data) {
                                                    $user_dept_id = $user_data['department_id'];
                                                } else {
                                                    die('User not found');
                                                }
                                                if (isset($user_role) && ($user_role === 'prof' || $user_role === 'hod' || $user_role === 'dean')) {
                                                    $organizer_query = "SELECT o.*, s.* 
                        FROM employee o
                        JOIN departments s ON o.department_id = s.department_id
                        WHERE o.department_id = ? AND o.employee_name = ?";

                                                    $stmt = $conn->prepare($organizer_query);

                                                    if ($stmt === false) {
                                                        die('Prepare failed: ' . htmlspecialchars($conn->error));
                                                    }

                                                    $stmt->bind_param("is", $user_dept_id, $username);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    $organizer = $result->fetch_assoc();

                                                    if ($organizer) {
                                                        $employee_id = $organizer['employee_id'];
                                                        $organizer_name = $organizer['employee_name'];
                                                        $organizer_email = $organizer['employee_email'];
                                                        $organizer_phone = $organizer['employee_mobile'];
                                                    }
                                                }
                                            }
                                            ?>
                                            <div class="mb-3">
                                                <!-- <label for="organiser_department" class="form-label">Organiser's Department</label> -->
                                                <input type="hidden" class="form-control" id="user_role"
                                                    name="user_role" value="<?php echo htmlspecialchars($user_role); ?>"
                                                    readonly required>
                                            </div>

                                            <div class="mb-3">
                                                <!-- <label for="organiser_department" class="form-label">Organiser's Department</label> -->
                                                <input type="hidden" class="form-control" id="organiser_department"
                                                    name="organiser_department"
                                                    value="<?php echo htmlspecialchars($department_name); ?>" readonly
                                                    required>
                                            </div>
                                            <div class="mb-3">
                                                <!-- <label for="organiser_name" class="form-label">Organiser's Name</label> -->
                                                <input type="hidden" class="form-control" id="organiser_name"
                                                    name="organiser_name" required
                                                    value="<?php echo htmlspecialchars($organizer_name); ?>" readonly>
                                            </div>
                                            <input type="hidden" id="employee_id" name="employee_id"
                                                value="<?php echo htmlspecialchars($employee_id); ?>">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <!-- <label for="organiser_mobile" class="form-label">Organiser's Contact Number</label> -->
                                                    <input type="hidden" class="form-control" id="organiser_mobile"
                                                        name="organiser_mobile" required
                                                        value="<?php echo htmlspecialchars($organizer_phone); ?>"
                                                        readonly>
                                                </div>
                                                <div class="col-md-6">
                                                    <!-- <label for="organiser_email" class="form-label">Organiser's Email ID</label> -->
                                                    <input type="hidden" class="form-control" id="organiser_email"
                                                        name="organiser_email" required
                                                        value="<?php echo htmlspecialchars($organizer_email); ?>"
                                                        readonly>
                                                </div>
                                            </div>
                                            <div id="duplicate_booking_message" class="alert alert-danger"
                                                style="display: none;">
                                                Duplicate booking detected! Please modify your selection.
                                            </div>
                                            <input type="hidden" id="slot_or_session" name="slot_or_session" value="">
                                            <div id="booking-status" class="mt-2"></div>
                                            <!-- Booking status message will be displayed here -->

                                            <div class="text-center">
                                                <a href="javascript:history.back()" style="padding:7px 30px;"
                                                    class="btn btn-primary fs-5">Back</a>
                                                <button type="submit" id="submit_button" class="btn btn-success btn-lg"
                                                    style="display:none;" disabled>Book Now</button>
                                            </div>
                            </form>


                        </div>
                    </div>
                </div>
                </span>
            </div>
        </div>
    </div>
    </div>
    </div>
    <?php include 'assets/footer.php' ?>
    <!-- Add a hidden pop-up modal -->

<div id="cancelBookingModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal" id="close_modal" >&times;</span>
        <center>
            <h3 style="color: #007bff; margin-bottom:15px;">Cancel Booking</h3>
        </center>
        <h4 class="form-section-title"></h4>

        <form onsubmit="return handleCancelBooking(event);">
            <input type="hidden" name="cancel_booking_id" id="cancel_booking_id" value="<?php echo $booking['booking_id']; ?>"> <!-- Stores selected hall ID -->
            
            <div class="form-group mt-3">
                <select name="reason" id="cancel_reason" required onchange="toggleOtherReason()">
                    <option value="" disabled selected>Select a reason</option>
                    <option value="Change of plans">Change of plans</option>
                    <option value="Scheduling conflict">Scheduling conflict</option>
                    <option value="Requested to Change">Requested to Change</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <textarea name="other_reason" id="other_reason" placeholder="Please specify..."
                    style="display:none; width: 100%; height: 50px; resize: vertical;"></textarea>
            </div>
            <center><button class="btn btn-primary mt-4" type="button" id="confirm_cancel"  type="submit">Submit</button></center>
        </form>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Event delegation for dynamically added cancel button
    document.body.addEventListener("click", function(event) {
        if (event.target.id === "cancel_booking_btn") {
            let bookingId = event.target.getAttribute("data-booking-id");
            document.getElementById("cancel_booking_id").value = bookingId;
            document.getElementById("cancelBookingModal").style.display = "block";
        }
    });

    document.getElementById("confirm_cancel").addEventListener("click", function(event) {
    event.preventDefault(); // Prevents form submission
    let bookingId = document.getElementById("cancel_booking_id").value;
    let reason = document.getElementById("cancel_reason").value;

    if (!reason.trim()) {
        alert("Please enter a reason for cancellation.");
        return;
    }

    fetch("cancel_duplicate.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `booking_id=${bookingId}&reason=${encodeURIComponent(reason)}`
    })
    .then(response => response.json())
    .then(data => {
        // alert(data.message);
        document.getElementById("cancelBookingModal").style.display = "none";
        checkAvailability();
    })
    .catch(error => console.error("Error:", error));
});


    // Close modal
    document.getElementById("close_modal").addEventListener("click", function() {
        document.getElementById("cancelBookingModal").style.display = "none";
    });

});
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.addEventListener("change", function() {
            window.scrollTo({ top: document.body.scrollHeight, behavior: "smooth" });
        });
    });
</script>


    <script>

        function validateFileSize() {
            const fileInput = document.getElementById('event_invitation');
            const file = fileInput.files[0];
            const message = document.getElementById('file-size-message');

            if (file) {
                const maxSize = 1048576; // 1MB in bytes (1048576 bytes)
                if (file.size > maxSize) {
                    message.style.color = 'red';  // Change text color to red
                    message.textContent = 'File size exceeds 1MB. Please upload a smaller file.';
                    fileInput.value = ''; // Reset the file input
                } else {
                    message.style.color = '';  // Reset text color
                    message.textContent = 'Supported formats: JPEG, JPG, PNG, PDF. Max size: 1MB.';
                }
            }
        }
        // Function to check if all form fields are filled
        function checkFormFields() {
            const organiserDepartment = document.getElementById('organiser_department').value.trim();
            const organiserName = document.getElementById('organiser_name').value.trim();
            const organiserMobile = document.getElementById('organiser_mobile').value.trim();
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const selectedSlots = document.querySelectorAll('.slot-checkbox:checked').length;
            const capacity = document.getElementById('students_count').value.trim();

            // Check if purpose radio buttons are selected
            const purposeEvent = document.getElementById('purpose_event').checked;
            const purposeClass = document.getElementById('purpose_class').checked;

            // Return true if all required fields are filled
            return organiserDepartment && organiserName && organiserMobile && startDate && endDate && selectedSlots > 0 && capacity && (purposeEvent || purposeClass);
        }

        // Function to handle duplicate booking response
        function handleDuplicateBooking(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                const duplicateMessage = document.getElementById('duplicate_booking_message');
                const submitBtn = document.getElementById('submit_button');

                if (!submitBtn) {
                    console.error('Submit button not found!');
                    return;
                }

                if (response.isDuplicate) {
                    duplicateMessage.style.display = 'block';
                    duplicateMessage.textContent = 'Duplicate booking detected! Please modify your selection.';
                    submitBtn.style.display = 'none';
                    submitBtn.disabled = true;
                } else {
                    duplicateMessage.style.display = 'none';
                    updateSubmitButtonState(); // Ensure button is updated after duplicate check
                }
            } catch (error) {
                console.error('Error processing response:', error);
            }
        }

        // Function to update the state of the submit button
        function updateSubmitButtonState() {
            const submitBtn = document.getElementById('submit_button');
            const duplicateMessage = document.getElementById('duplicate_booking_message');

            if (checkFormFields()) {
                submitBtn.style.display = 'inline-block';
                submitBtn.disabled = false;
                duplicateMessage.style.display = 'none';
            } else {
                submitBtn.style.display = 'none';
                submitBtn.disabled = true;
                duplicateMessage.style.display = 'block';
                duplicateMessage.textContent = 'Please fill out all required fields.';
            }
        }

        // Event listeners to check form fields on changes
        document.getElementById('start_date').addEventListener('change', updateSubmitButtonState);
        document.getElementById('end_date').addEventListener('change', updateSubmitButtonState);
        document.querySelectorAll('.slot-checkbox').forEach(function (slot) {
            slot.addEventListener('change', updateSubmitButtonState);
        });
        document.getElementById('purpose_event').addEventListener('change', updateSubmitButtonState);
        document.getElementById('purpose_class').addEventListener('change', updateSubmitButtonState);
        document.getElementById('students_count').addEventListener('input', updateSubmitButtonState);
        document.getElementById('organiser_department').addEventListener('input', updateSubmitButtonState);
        document.getElementById('organiser_name').addEventListener('input', updateSubmitButtonState);
        document.getElementById('organiser_mobile').addEventListener('input', updateSubmitButtonState);

        document.addEventListener('DOMContentLoaded', () => {
            const organiserInput = document.getElementById('organiser_name');
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const slotCheckboxes = document.querySelectorAll('.slot-checkbox');

            organiserInput.addEventListener('input', triggerDuplicateCheck);
            startDateInput.addEventListener('change', triggerDuplicateCheck);
            endDateInput.addEventListener('change', triggerDuplicateCheck);
            slotCheckboxes.forEach(checkbox => checkbox.addEventListener('change', triggerDuplicateCheck));
        });

        function triggerDuplicateCheck() {
            const organiserName = document.getElementById('organiser_name').value;
            const organiserId = document.getElementById('employee_id').value;
            const organiserEmail = document.getElementById('organiser_email').value;
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const selectedSlots = Array.from(document.querySelectorAll('.slot-checkbox:checked')).map(slot => slot.value);

            if (organiserName && organiserId && startDate && endDate && selectedSlots.length > 0) {
                checkDuplicateBooking(organiserName, organiserId, startDate, endDate, selectedSlots);
            }
        }

        function checkDuplicateBooking(organiserName, organiserId, startDate, endDate, selectedSlots) {

            const duplicateMessage = document.getElementById('duplicate_booking_message');
            const submitBtn = document.getElementById('submit_button');

            if (!submitBtn) {
                console.error('Submit button not found!');
                return;
            }

            duplicateMessage.style.display = 'none';
            updateSubmitButtonState(); // Ensure button is updated after duplicate check



        }
    </script>
    <script>
        function validateForm() {
            const studentsCount = parseInt(document.getElementById("students_count").value, 10);
            if (studentsCount > hallCapacity) {
                // Show the alert
                document.getElementById("alert").style.display = "block";
                return false;
            
            } else {
                // Hide the alert if the input is valid
                document.getElementById("alert").style.display = "none";
            }
            return true;
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const slotCheckboxes = document.querySelectorAll('.slot-checkbox');
            const sessionCheckboxes = document.querySelectorAll('.session-checkbox');

            // Function to update session checkboxes based on selected slots
            function updateSessionCheckboxes() {
                const selectedSlots = Array.from(slotCheckboxes)
                    .filter(checkbox => checkbox.checked)
                    .map(checkbox => parseInt(checkbox.value));

                const forenoonSelected = selectedSlots.every(slot => slot >= 1 && slot <= 4) && selectedSlots.length === 4;
                const afternoonSelected = selectedSlots.every(slot => slot >= 5 && slot <= 8) && selectedSlots.length === 4;

                // Update 'Forenoon' checkbox
                document.querySelector('#fn').checked = forenoonSelected;

                // Update 'Afternoon' checkbox
                document.querySelector('#an').checked = afternoonSelected;

                // If all slots are selected, both 'fn' and 'an' should be checked
                if (selectedSlots.length === 8) {
                    document.querySelector('#fn').checked = true;
                    document.querySelector('#an').checked = true;
                }
                checkAvailability();
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
                checkAvailability();
            }

            // Add event listeners to slot checkboxes
            slotCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSessionCheckboxes);
            });

            // Add event listeners to session checkboxes
            sessionCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSlotsBasedOnSession);
            });
            checkAvailability();
            triggerDuplicateCheck();
        });
    </script>





    <script>
        const hallCapacity = <?php echo $hall['capacity']; ?>;

        function checkCapacity() {
            const studentsCount = parseInt(document.getElementById("students_count").value, 10);

            // Check if students count exceeds the capacity
            if (studentsCount > hallCapacity) {
                // Show the alert
                document.getElementById("alert").style.display = "block";
            } else {
                // Hide the alert if the input is valid
                document.getElementById("alert").style.display = "none";
            }
        }
        document.addEventListener("DOMContentLoaded", function () {
            const calendarSection = document.getElementById("calendar");
            if (calendarSection) {
                calendarSection.scrollIntoView({ behavior: "smooth", block: "center" });
            }


            const startDateInput = document.getElementById("start_date");
            const endDateInput = document.getElementById("end_date");

            const today = new Date().toISOString().split('T')[0];
            startDateInput.setAttribute('min', today);
            endDateInput.setAttribute('min', today);

            startDateInput.addEventListener("input", function () {
                // Get the selected start date
                const startDate = new Date(startDateInput.value);
                // Set the end date to the start date if it's empty or less than start date
                if (!endDateInput.value || new Date(endDateInput.value) < startDate) {
                    endDateInput.value = startDateInput.value;
                }
                // Update the min attribute of end date to ensure it can't be before the start date
                endDateInput.setAttribute('min', startDateInput.value);
            });

            endDateInput.addEventListener("input", function () {
                // If the selected end date is before the start date, reset it
                if (new Date(endDateInput.value) < new Date(startDateInput.value)) {
                    endDateInput.value = startDateInput.value;
                }

            });




            document.querySelectorAll('input[name="slots[]"]').forEach(slot => {
                slot.addEventListener('click', function () {
                    autoSelectSlots(slot);
                });
            });






            const eventRadio = document.getElementById('purpose_event');
            const classRadio = document.getElementById('purpose_class');
            const eventTypeGroup = document.getElementById('event-type-group');

            function toggleEventType() {
                if (eventRadio.checked) {
                    eventTypeGroup.style.display = 'block';
                } else {
                    eventTypeGroup.style.display = 'none';
                }
            }

            // Attach event listeners to the radio buttons
            eventRadio.addEventListener('change', toggleEventType);
            classRadio.addEventListener('change', toggleEventType);


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

        });







        const calendarData = <?php echo $calendarJson; ?>;
        const currentDateTime = new Date('<?php echo $currentDateTime; ?>');
        const timeSlots = [
            { slot: 1, time: '09:30 AM' }, { slot: 2, time: '10:30 AM' }, { slot: 3, time: '11:30 AM' }, { slot: 4, time: '12:30 PM' },
            { slot: 5, time: '01:30 PM' }, { slot: 6, time: '02:30 PM' }, { slot: 7, time: '03:30 PM' }, { slot: 8, time: '04:30 PM' }
            // ,{slot: 9, time: '05:30 PM'}, {slot: 10, time: '06:30 PM'}, {slot: 11, time: '07:30 PM'}, {slot: 12, time: '08:30 PM'}, {slot: 13, time: '09:30 PM'}
        ];

        let currentMonthIndex = 0;

        function renderCalendar(calendarData, startIndex = 0) {
            const container = document.getElementById('calendar-container');
            container.innerHTML = '';

            const monthElement = document.createElement('div');
            monthElement.className = 'month-calendar';

            const month = calendarData[startIndex];

            const prevButton = startIndex === 0
                ? '<button class="prev-month" disabled>⬅️</button>'
                : '<button class="prev-month" onclick="showPrevMonth()">⬅️</button>';

            const nextButton = startIndex === calendarData.length - 1
                ? '<button class="next-month" disabled>➡️</button>'
                : '<button class="next-month" onclick="showNextMonth()">➡️</button>';

            monthElement.innerHTML = `
    <div class="time-slot-container">
            ${renderTimeSlots(month.days, month.year, month.month)}
        </div>

        <div class="calendar-legend">
            <div class="legend-item">
                <div class="legend-color" style="background-color: rgb(85, 255, 122); border: 1px solid black;"></div>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: rgb(244, 255, 91); border: 1px solid black;"></div>
                <span>Pending</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: rgb(255, 103, 115); border: 1px solid black;"></div>
                <span>Booked</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: rgb(162, 162, 162); border: 1px solid black;"></div>
                <span>Past</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: rgb(0, 123, 255); border: 1px solid black;"></div>
                <span>Selected</span>
            </div>
        </div>

        <div class="month-header">
            ${prevButton}
            <h5 style='margin:0; padding: 5px 10px;'>${new Date(month.year, month.month - 1).toLocaleString('default', { month: 'long', year: 'numeric' })}</h5>
            ${nextButton}
        </div>

        
    `;

            container.appendChild(monthElement);
        }
        function renderTimeSlots(days, year, month) {
            const daysInMonth = new Date(year, month, 0).getDate();
            const allDays = Array.from({ length: 31 }, (_, i) => i + 1);

            // Create the header row for dates
            const dateRow = `
    <div class="time-slot-row">
        <div class="calendar-time-column"></div>
        ${allDays.map(day => {
                const foundDay = days.find(d => new Date(d.date).getDate() === day);
                if (foundDay) {
                    const dayDate = new Date(foundDay.date);
                    const dayName = dayDate.toLocaleString('en-US', { weekday: 'short' });
                    const isWeekend = dayDate.getDay() === 0 || dayDate.getDay() === 6; // Sunday or Saturday
                    const weekendClass = isWeekend ? 'weekend-cell' : '';

                    return `
                    <div class="calendar-cell ${weekendClass}">
                        <div class="day-number ${weekendClass}">${day}</div>
                        <div class="day-name ${weekendClass}">${dayName}</div>
                    </div>`;
                } else {
                    return `<div class="calendar-cell white-cell"></div>`;
                }
            }).join('')}
    </div>
`;

            // Create the rows for time slots
            const timeRows = timeSlots.map(({ slot, time }) => `
    <div class="time-slot-row">
        <div class="calendar-time-column">${time}</div>
        ${allDays.map(day => {
                const foundDay = days.find(d => new Date(d.date).getDate() === day);
                const dayDate = foundDay ? new Date(foundDay.date) : null;

                if (dayDate) {
                    const [hours, minutes, period] = time.match(/(\d+):(\d+)\s(AM|PM)/).slice(1);
                    let hour = parseInt(hours);
                    if (period === 'PM' && hour !== 12) hour += 12;
                    if (period === 'AM' && hour === 12) hour = 0;

                    const formattedTime = `${String(hour).padStart(2, '0')}:${minutes}`;
                    const slotDateTime = new Date(`${dayDate.toISOString().split('T')[0]}T${formattedTime}`);
                    const currentDateTime = new Date();

                    let cellClass = '';
                    const dayOfWeek = dayDate.getDay(); // 0 for Sunday, 6 for Saturday

                    if (slotDateTime <= currentDateTime) {
                        cellClass = 'past';
                    } else {
                        const bookedSlots = foundDay.bookedSlots.filter(bs => bs.slot === slot);
                        if (bookedSlots.length > 0) {
                            const statuses = [...new Set(bookedSlots.map(bs => bs.status))];
                            const status = statuses.includes('approved') ? 'approved' : 'pending';

                            return `
                            <div class="calendar-cell ${status} ${dayOfWeek === 0 || dayOfWeek === 6 ? 'whitish-cell' : ''}" 
                                data-organiser='${JSON.stringify(bookedSlots.map(bs => bs.organiserDetails))}'
                                onclick="${status === 'pending'
                                    ? `handlePendingClick(this, '${dayDate.toISOString().split('T')[0]}', '${slot}')`
                                    : `showOrganizerDetails(this)`}">
                            </div>`;
                        } else {
                            cellClass = 'available';
                        }
                    }

                    return `
                    <div class="calendar-cell ${cellClass} ${dayOfWeek === 0 || dayOfWeek === 6 ? 'whitish-cell' : ''}" 
                        onclick="selectDate('${dayDate.toISOString().split('T')[0]}', this, '${slot}')"
                        data-date="${dayDate.toISOString().split('T')[0]}"
                        data-slot="${slot}">
                    </div>`;
                } else {
                    return `<div class="calendar-cell white-cell"></div>`;
                }
            }).join('')}
    </div>
`).join('');

            return dateRow + timeRows;
        }


        // Function to handle both actions for "pending" slots
        function handlePendingClick(cell, date, slot) {
            // Call existing functions
            showOrganizerDetails(cell);
            selectDate(date, cell, slot); // Optional, if date selection is needed
            checkAvailability();
        }

        function selectDate(date, element, slot) {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const slotInput = document.getElementById('slot_or_session');
            const fnCheckbox = document.getElementById('fn');
            const anCheckbox = document.getElementById('an');
            fnCheckbox.checked = false;
            anCheckbox.checked = false;

            // Remove activeAttachment class from previously selected cell
            const previouslySelected = document.querySelector('.activeAttachment');
            if (previouslySelected) {
                previouslySelected.classList.remove('activeAttachment');
            }

            // Add activeAttachment class to the clicked cell
            element.classList.add('activeAttachment');

            if (!startDateInput.value && !endDateInput.value) {
                startDateInput.value = date;
                endDateInput.value = date;
            } else {
                startDateInput.value = date;
                endDateInput.value = date;
            }
            slotInput.value = slot;
            document.getElementById('booking').style.display = 'block';

            // Determine if the status is pending
            const status = element.classList.contains('pending') ? 'pending' : 'other'; // Adjust this to your logic for checking status

            if (status === 'pending') {
                // Don't hide the table for pending status
                document.getElementById('organizer-details-table').style.display = 'table';
                document.getElementById('organizer-details-heading').style.display = 'block';
                checkAvailability();
            } else {
                // Hide the table for non-pending status
                document.getElementById('organizer-details-table').style.display = 'none';
                document.getElementById('organizer-details-heading').style.display = 'none';
                checkAvailability();
            }

            // Update the slot checkboxes
            updateSlotCheckboxes(slot);
            checkAvailability();
        }


        function updateSlotCheckboxes(selectedSlot) {
            const checkboxes = document.querySelectorAll('.slot-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = checkbox.value === selectedSlot;
                checkAvailability();
            });
        }


        let currentlyActiveCell = null;
        function showOrganizerDetails(element) {
            const organiserData = element.getAttribute('data-organiser');
            let organiserDetails;

            const previouslySelected = document.querySelector('.activeAttachment');
            if (previouslySelected) {
                previouslySelected.classList.remove('activeAttachment');
            }

            element.classList.add('activeAttachment');

            try {
                organiserDetails = JSON.parse(organiserData);
            } catch (e) {
                console.error("Invalid JSON in data-organiser:", organiserData);
                return;
            }

            const tableBody = document.querySelector("#organizer-details-table tbody");
            tableBody.innerHTML = "";

            if (Array.isArray(organiserDetails) && organiserDetails.length > 0) {
                const status = element.classList.contains('approved') ? 'approved' :
                    element.classList.contains('pending') ? 'pending' : 'available';

                organiserDetails.forEach(details => {
                    const row = document.createElement("tr");

                    // Combine organiser details
                    const organiserInfo = `
                ${details.name || 'N/A'}<br>
                <b><span style="color:#0e00a3">${details.department || 'N/A'}</b></span><br>
                ${details.email || 'N/A'}<br>
                ${details.mobile || 'N/A'}
            `;

                    // Combine purpose details
                    const purpose = details.purpose ? details.purpose.toUpperCase() : 'N/A';

                    // Format event type (replace '_' with '/' and capitalize)
                    const eventType = details.event_type
                        ? details.event_type.replace(/_/g, '/').replace(/\b\w/g, char => char.toUpperCase())
                        : '';

                    // Format purpose name (capitalize first letter)
                    const purposeName = details.purpose_name
                        ? details.purpose_name.charAt(0).toUpperCase() + details.purpose_name.slice(1)
                        : '';

                    // Combine purpose details
                    const purposeInfo = `
    <span style="color:#0e00a3"><b>${purpose}</b></span><br>
   <b> ${(details.purpose === 'event' ? eventType + '<br>' : '')}</b>
    ${purposeName}
`;
                    const bookingInfo = `
                ${details.date || 'N/A'}<br>
               <span style="color:#0e00a3"> ${details.id_gen || ''}</span>
            `;
                    row.innerHTML = `
                <td>${organiserInfo}</td>
                <td>${purposeInfo}</td>
                <td>${details.capacity || 'N/A'}</td>
                <td>${bookingInfo}</td>
            `;
                    tableBody.appendChild(row);
                });

                document.getElementById('organizer-details-table').style.display = 'table';
                document.getElementById('organizer-details-heading').style.display = 'block';
                document.getElementById('booking').style.display = (status === 'approved' ? 'none' : 'block');
                checkAvailability();
            } else {
                document.getElementById('organizer-details-table').style.display = 'none';
                document.getElementById('organizer-details-heading').style.display = 'none';
                document.getElementById('booking').style.display = 'block'; // Default to showing booking form
                checkAvailability();
            }
        }
        function showPrevMonth() {
            if (currentMonthIndex > 0) {
                currentMonthIndex--;
                renderCalendar(calendarData, currentMonthIndex);
            }
        }

        function showNextMonth() {
            if (currentMonthIndex < calendarData.length - 1) {
                currentMonthIndex++;
                renderCalendar(calendarData, currentMonthIndex);
            }
        }

        // Initial render
        renderCalendar(calendarData);

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
            checkAvailability();
        }





        function checkAvailability() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const hallId = document.querySelector('input[name="hall_id"]').value;
            const organiserId = document.getElementById('employee_id').value; // Get organiser ID

            const bookingType = 'slot';

            // Collect selected slots
            const selectedSlots = Array.from(document.querySelectorAll('input[name="slots[]"]:checked'))
                .map(slot => slot.value);

            const sessionOrSlots = selectedSlots.join(',');

            // Check if all required fields are filled
            if (startDate && endDate && bookingType && sessionOrSlots && organiserId) { // Include organiserId
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'check_availability_modify.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        const messageDiv = document.getElementById('availability_message');
                        if (response.available) {
                            messageDiv.innerHTML = `<span class="text-success">${response.message}</span>`;
                            document.getElementById('booking2').style.display = 'block';
                        } else {
                            messageDiv.innerHTML = `<span class="text-danger">${response.message}</span>`;
                            document.getElementById('booking2').style.display = 'none';
                        }
                    } else {
                        console.error('Request failed with status:', xhr.status);
                    }
                };

                // Include organiser_id in the request
                xhr.send(`hall_id=${hallId}&start_date=${startDate}&end_date=${endDate}&booking_type=${bookingType}&session_or_slots=${sessionOrSlots}&organiser_id=${organiserId}`);
            }
        }

        const today = new Date().toISOString().split('T')[0];

        // Retrieve the DOM elements, NOT their values
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        // Ensure both inputs have a minimum value of today
        startDateInput.setAttribute('min', today);
        endDateInput.setAttribute('min', today);

        startDateInput.addEventListener("input", function () {
            // Get the selected start date
            const startDate = new Date(startDateInput.value);

            // If the end date is empty or set to a date before the start date, update it to match the start date.
            if (!endDateInput.value || new Date(endDateInput.value) < startDate) {
                endDateInput.value = startDateInput.value;
            }

            // Update the min attribute of the end date to ensure it cannot be set to a day before the start date.
            endDateInput.setAttribute('min', startDateInput.value);
        });

        endDateInput.addEventListener("input", function () {
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
</body>

</html>