<?php
include 'assets/conn.php';
include 'assets/header.php';
$sql = "
    SELECT 
        b.*, 
        r.hall_name, 
        d.department_name, 
        ht.type_name, 
        conflict.booking_id AS conflict_id, 
        conflict.start_date AS conflict_start, 
        conflict.end_date AS conflict_end, 
        conflict.slot_or_session AS conflict_slot 
    FROM bookings b
    JOIN hall_details r ON b.hall_id = r.hall_id
    LEFT JOIN departments d ON d.department_id = r.department_id
    LEFT JOIN hall_type ht ON r.type_id = ht.type_id
    LEFT JOIN bookings conflict 
        ON conflict.hall_id = b.hall_id 
        AND conflict.status = 'approved'
        AND (
            (b.start_date BETWEEN conflict.start_date AND conflict.end_date) OR 
            (b.end_date BETWEEN conflict.start_date AND conflict.end_date) OR 
            (conflict.start_date BETWEEN b.start_date AND b.end_date) OR 
            (conflict.end_date BETWEEN b.start_date AND b.end_date)
        )
        AND FIND_IN_SET(b.slot_or_session, conflict.slot_or_session) > 0
    WHERE b.status = 'pending' 
    AND b.end_date >= CURDATE()  
";

$filters = [];

// Apply role-based filters
if ($_SESSION['role'] === 'hod') {
    $department_id = $_SESSION['department_id'];
    $filters[] = "r.department_id = $department_id";
} elseif ($_SESSION['role'] === 'dean') {
    $school_id = $_SESSION['school_id'];
    $filters[] = "d.school_id = $school_id";
}



// Apply filters based on URL parameters
if (
    isset($_GET['from_date']) && isset($_GET['to_date']) &&
    !empty($_GET['from_date']) && !empty($_GET['to_date'])
) {
    $from_date = $_GET['from_date'];
    $to_date = $_GET['to_date'];

    // Validate the date range
    if ($from_date <= $to_date) {
        $filters[] = "DATE(b.booking_date) BETWEEN '$from_date' AND '$to_date'";
    } else {
        // Optional: Handle the case where the date range is invalid
        echo "<script>alert('Invalid date range. Please check your dates.');</script>";
    }
}

if (isset($_GET['start_date']) && !empty($_GET['start_date']) && isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $from_date = $_GET['start_date'];
    $to_date = $_GET['end_date'];
    $filters[] = "(b.start_date <= '$to_date' AND b.end_date >= '$from_date')";
}
if (isset($_GET['hall_name']) && !empty($_GET['hall_name'])) {
    $hall_name = $_GET['hall_name'];
    $filters[] = "r.hall_name = '$hall_name'";
}

if (isset($_GET['purpose']) && !empty($_GET['purpose'])) {
    $purpose = $_GET['purpose'];
    $filters[] = "b.purpose = '$purpose'";
}

if (isset($_GET['organiser_department']) && !empty($_GET['organiser_department'])) {
    $organiser_department = $_GET['organiser_department'];
    $filters[] = "b.organiser_department = '$organiser_department'";
}
if (isset($_GET['organiser_name']) && !empty($_GET['organiser_name'])) {
    $organiser_name = $_GET['organiser_name'];
    $filters[] = "b.organiser_name = '$organiser_name'";
}
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status = $_GET['status'];
    $filters[] = "b.status = '$status'";
}

// Apply filters to SQL query if any are present
if (!empty($filters)) {
    $sql .= " AND " . implode(" AND ", $filters);
}

$sql .= " ORDER BY start_date, end_date, slot_or_session, hall_id";

// Check if any filters are applied
$filterApplied = (!empty($_GET['from_date']) && !empty($_GET['to_date']) || !empty($_GET['start_date']) || !empty($_GET['end_date']) ||
    !empty($_GET['hall_name']) || !empty($_GET['organiser_department']) || !empty($_GET['organiser_name']) || !empty($_GET['status']));

// Execute the query
$result = $conn->query($sql);

// Check if the query execution was successful
if ($result === false) {
    die('Error executing the SQL query: ' . $conn->error);
}

// Handle booking status update (approve/reject)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id'], $_POST['new_status'])) {

    // Sanitize input
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_SANITIZE_NUMBER_INT);
    $new_status = filter_input(INPUT_POST, 'new_status', FILTER_SANITIZE_STRING);

    if (in_array($new_status, ['approved', 'rejected'])) {
        // Fetch booking details
        $sql_fetch = "SELECT hall_id, start_date, end_date, slot_or_session FROM bookings WHERE booking_id = ?";
        $stmt = $conn->prepare($sql_fetch);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->bind_result($hall_id, $start_date, $end_date, $slot_or_session);
        $stmt->fetch();
        $stmt->close();

        if ($new_status == 'approved') {
            // Check for conflicting approved bookings
            $sql_check_conflict = "SELECT booking_id FROM bookings 
            WHERE hall_id = ? 
            AND status = 'approved'
            AND 
                (start_date < ? AND end_date > ?) OR
(start_date <= ? AND end_date >= ?) OR
(start_date BETWEEN ? AND ?) OR
(end_date BETWEEN ? AND ?)
            AND FIND_IN_SET(?, slot_or_session) > 0";
        

            $stmt = $conn->prepare($sql_check_conflict);
            $stmt->bind_param("isssssssss", 
            $hall_id, 
            $end_date, $start_date,  
            $start_date, $end_date, 
            $start_date, $end_date, 
            $start_date, $end_date, 
            $slot_or_session 
        );
        
        
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->close();
                echo "<script>alert('Error: Conflicting booking already approved for this date range and time slot.');</script>";
                echo "<script>window.location.href = 'status_update_mail.php';</script>";
                exit();
            }
            $stmt->close();
        }

        // If no conflict, update status
        $sql_update = "UPDATE bookings SET status = ? WHERE booking_id = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("si", $new_status, $booking_id);

        if ($stmt->execute()) {
            $stmt->close();
            echo "<script>window.location.href = 'update_mail.php?booking_id=$booking_id&new_status=$new_status';</script>";
            exit();
        }

        $stmt->close();
    }

    $conn->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall Booking - Check Availability</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/design.css" />
    <style>
        .table-wrapper {
            overflow-x: auto;
            /* Allow horizontal scrolling */
            max-width: 100%;
        }

        .card {
            border: none;
        }

        .btn {
            width: 100px;
            /* Set consistent width */
            margin: 5px;
            /* Add spacing between buttons */
            font-size: 16px;
            /* Ensure consistent font size */
            text-align: center;
            /* Center text */
            line-height: 30px;
            /* Vertically center text */
            padding: 3px;
        }

        /* Modal styles */
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

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 30%;
            position: relative;
            /* This is needed for absolute positioning of the close button */
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

        .clear-filters {
            position: absolute;
            top: 30px;
            /* Adjust as needed */
            right: 30px;
            /* Adjust as needed */
        }

        .type-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Styling for the hall name */
        .hall-name {
            color: blue;
            cursor: pointer;
            display: inline-block;
            transition: transform 0.2s ease-in-out;
        }

        /* Hover effect for the hall name */
        .hall-name:hover {
            transform: scale(1.05);
        }

        .modal1 {
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

        .modal1-content {
            margin: 5% auto;
            padding: 0px;
/*  border: 1px solid #888; */            width: 40%;
            height: 70%;
            position: relative;
            /* This is needed for absolute positioning of the close button */
        }

        .close-modal1 {
            color: black;
            -webkit-text-stroke-width: 1px;
            -webkit-text-stroke-color: white;
            margin-right: 30px;
            font-size: 30px;
            font-weight: bold;
            position: absolute;
            /* Position relative to the modal-content */
            top: 20px;
            /* Adjust the distance from the top */
            right: 60px;
            /* Adjust the distance from the right */
            cursor: pointer;
            z-index: 100;
        }

        #bookingTable {
            width: 100%;
            border-collapse: collapse;
        }

        #bookingTable th,
        #bookingTable td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        #bookingTable th {
            background-color: #f2f2f2;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .table-container {
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>

<body>

    <div id="main">
        <div class="table-wrapper mt-5">
            <div class="card shadow-lg">
                <div class="card-body">


                            <center>
                                <h3 style="color:#0e00a3">Approve / Reject Bookings</h3><br>
                            </center>
                            <div class="table-container" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-bordered" id="bookingTable">
                            <thead>  
                            <?php if ($filterApplied): ?>
                                <div class="clear-filters">
                                    <a href="status_update_mail.php" class="btn btn-danger">Clear Filters</a>
                                </div>
                            <?php endif; ?>
                            <tr>
                                <th width="10%">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="flex-grow: 1; text-align: center;">Booked On</span>
                                        <span class="bi bi-sliders" style="cursor: pointer;"
                                            onclick="openModal('bookedDateFilterModal')"></span>
                                    </div>
                                </th>
                                <th width="20%">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="flex-grow: 1; text-align: center;">Hall Details</span>
                                        <span class="bi bi-sliders" style="cursor: pointer;"
                                            onclick="openModal('hallDetailFilterModal')"></span>
                                    </div>
                                </th>
                                <th width="15%">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="flex-grow: 1; text-align: center;">Purpose</span>
                                        <span class="bi bi-sliders" style="cursor: pointer;"
                                            onclick="openModal('purposeFilterModal')"></span>
                                    </div>
                                </th>
                                <th width="15%">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="flex-grow: 1; text-align: center;">Date & Time</span>
                                        <span class="bi bi-sliders" style="cursor: pointer;"
                                            onclick="openModal('dateRangeFilterModal')"></span>
                                    </div>
                                </th>
                                <th width="20%">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="flex-grow: 1; text-align: center;">Booked By</span>
                                        <span class="bi bi-sliders" style="cursor: pointer;"
                                            onclick="openModal('organiserFilterModal')"></span>
                                    </div>
                                </th>
                                <th width="10%">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="flex-grow: 1; text-align: center;">Status</span>
                                        <span class="bi bi-sliders" style="cursor: pointer;"
                                            onclick="openModal('statusFilterModal')"></span>
                                    </div>
                                </th>

                                <th width="10%">
                                    <center>Action </center>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <center><?php echo date("d-m-Y", strtotime($row['booking_date'])) . "  <br>"; ?>
                                                <span style="color:blue;font-size:0.9rem;">
                                                    <?php echo $row['booking_id_gen'] . "  <br>"; ?></span>
                                            </center>
                                        </td>
                                        <td>
                                            <div class="type-container">
                                                <b><?php echo ucwords($row['type_name']); ?></b>
                                            </div>
                                            <!-- Hall Name with Onclick and Hover Effect -->
                                            <span class="hall-name"
                                                onclick="openCalendarModal('calender', <?php echo $row['hall_id']; ?>)">
                                                <?php echo strtoupper($row['hall_name']); ?>
                                            </span>
                                            <br>
                                            <?php echo $row['department_name']; ?>
                                            <br>
                                        </td>

                                        <td>

                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <span><b><?php echo ucwords($row['purpose']); ?></b></span>
                                                <?php
                                                if (!empty($row['event_image'])) {
                                                    $fileExtension = pathinfo($row['event_image'], PATHINFO_EXTENSION);
                                                    if (in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
                                                        echo '<a href="javascript:void(0);" onclick="showEventImage(\'' . $row['event_image'] . '\')">';
                                                        echo '<i class="bi bi-eye-fill" style="color: black;"></i>';
                                                        echo '</a>';
                                                    } elseif (strtolower($fileExtension) === 'pdf') {
                                                        echo '<a href="' . $row['event_image'] . '" target="_blank">';
                                                        echo '<i class="bi bi-eye-fill" style="color: black;"></i>';
                                                        echo '</a>';
                                                    }
                                                }
                                                ?>
                                            </div>

                                            <?php
                                            // Check if the purpose is 'event'
                                            if (strtolower($row['purpose']) === 'event') {
                                                echo '<span style="color:blue;">' . ucwords(str_replace('_', ' / ', $row['event_type'])) . '</span><br>';
                                            }
                                            ?>
                                            <?php echo $row['purpose_name']; ?>
                                        </td>

                                        <td>
                                            <?php
                                            $booking_id = $row['booking_id'];
                                            if ($row['start_date'] == $row['end_date']) {
                                                echo date("d-m-Y", strtotime($row['start_date'])) . "<br>";
                                            } else {
                                                echo date("d-m-Y", strtotime($row['start_date'])) . " to " . date("d-m-Y", strtotime($row['end_date'])) . "<br>";
                                            }
                                            $booked_slots_string = $row['slot_or_session'];
                                            $booked_slots = array_map('intval', explode(',', $booked_slots_string)); // Convert to an array of integers
                                            sort($booked_slots);

                                            $forenoon_slots = [1, 2, 3, 4]; // Forenoon slots
                                            $afternoon_slots = [5, 6, 7, 8]; // Afternoon slots
                                            $full_day_slots = [1, 2, 3, 4, 5, 6, 7, 8]; // Full Day slots
                                    
                                            // Map of slot numbers to time strings
                                            $slot_timings = [
                                                1 => '9:30 AM - 10:30 AM',
                                                2 => '10:30 AM - 11:30 AM',
                                                3 => '11:30 AM - 12:30 PM',
                                                4 => '12:30 PM - 1:30 PM',
                                                5 => '1:30 PM - 2:30 PM',
                                                6 => '2:30 PM - 3:30 PM',
                                                7 => '3:30 PM - 4:30 PM',
                                                8 => '4:30 PM - 5:30 PM'
                                            ];

                                            $booking_type = '';
                                            $booked_timings = '';

                                            if ($booked_slots === $full_day_slots) {
                                                $booking_type = "Full Day";
                                            } elseif ($booked_slots === $forenoon_slots) {
                                                $booking_type = "Forenoon";
                                            } elseif ($booked_slots === $afternoon_slots) {
                                                $booking_type = "Afternoon";
                                            } else {
                                                // If it's a custom slot, show only the booked timings
                                                $booked_timings = implode("<br> ", array_map(function ($slot) use ($slot_timings) {
                                                    return $slot_timings[$slot];
                                                }, $booked_slots));
                                            }

                                            // Output the booking type if it exists
                                            if (!empty($booking_type)) {
                                                echo "(" . $booking_type . ")<br>";
                                            }

                                            // If there are booked timings (custom slots), display them
                                            if (!empty($booked_timings)) {
                                                echo "(" . $booked_timings . ")";
                                            }

                                            ?>
                                             <?php
                                            if (!empty($row['event_type'])) {
                                                echo '<span style="color:blue;">' . ucwords(str_replace('_', ' / ', $row['event_type'])) . '</span><br>';
                                            }
                                            echo $row['purpose_name'];

                                            if (!empty($row['day_of_week'])) {
                                                echo '<br><span style="color:rgba(255, 66, 66, 0.79);"><b>Semester Booking</b></span>';
                                            }
                                            ?>
                                        </td>
                                        <td><b><?php echo ucwords($row['organiser_department']); ?></b><br>
                                            <?php echo ucwords($row['organiser_name']); ?><br>
                                            <?php echo $row['organiser_mobile']; ?><br>
                                            <?php echo $row['organiser_email']; ?>

                                        </td>
<td>                                            <?php
                                            $status = strtolower($row['status']); // Convert to lowercase for consistency
                                            if ($status === 'allow') {
                                                echo "Pending to Forward";
                                            } elseif ($status === 'pending') {
                                                echo "Pending to Approve";
                                            } else {
                                                echo ucwords(htmlspecialchars($status)); // Print the original status if not 'allow' or 'pending'
                                            }
                                            ?>
                                            <br>
                                            <br>
                                            <?php
$conflict_query = "
SELECT * FROM bookings 
WHERE hall_id = ? 
AND booking_id != ?

AND status IN ('approved', 'pending')
AND (
    (start_date <= ? AND end_date >= ?) OR  -- New booking starts within an existing booking
    (start_date <= ? AND end_date >= ?) OR  -- New booking ends within an existing booking
    (start_date BETWEEN ? AND ?) OR         -- Existing booking starts within new booking
    (end_date BETWEEN ? AND ?)              -- Existing booking ends within new booking
)
AND (
    FIND_IN_SET(SUBSTRING_INDEX(?, ',', 1), slot_or_session) > 0 OR
    FIND_IN_SET(SUBSTRING_INDEX(?, ',', 2), slot_or_session) > 0 OR
    FIND_IN_SET(SUBSTRING_INDEX(?, ',', 3), slot_or_session) > 0 OR
    FIND_IN_SET(SUBSTRING_INDEX(?, ',', 4), slot_or_session) > 0 OR
    FIND_IN_SET(SUBSTRING_INDEX(?, ',', 5), slot_or_session) > 0 OR
    FIND_IN_SET(SUBSTRING_INDEX(?, ',', 6), slot_or_session) > 0 OR
    FIND_IN_SET(SUBSTRING_INDEX(?, ',', 7), slot_or_session) > 0 OR
    FIND_IN_SET(SUBSTRING_INDEX(?, ',', 8), slot_or_session) > 0
)";
$stmt = $conn->prepare($conflict_query);

// Ensure all slots are properly compared
$stmt->bind_param(
    "iissssssssssssssss", 
    $row['hall_id'], 
    $row['booking_id'], 
    $row['start_date'], 
    $row['end_date'], 
    $row['start_date'], 
    $row['end_date'], 
    $row['start_date'],
    $row['end_date'], 

    $row['start_date'], 
    $row['end_date'], 
    $row['slot_or_session'], 
    $row['slot_or_session'], 
    $row['slot_or_session'], 
    $row['slot_or_session'], 
    $row['slot_or_session'], 
    $row['slot_or_session'], 
    $row['slot_or_session'], 
    $row['slot_or_session'] // This is for checking multiple session values
);

$stmt->execute();
$stmt->store_result();

                                  
                                        
                                            if ($stmt->num_rows > 0) {
                                                echo "<span style='color: red;'>Conflict Exists</span>";
                                            } 
                                        
                                            $stmt->close();
                                            ?>
                                        </td>

                                        <td>
                                            <form action="status_update_mail.php" method="post" style="display:inline;">
                                                <input type="hidden" name="booking_id"
                                                    value="<?php echo htmlspecialchars($row['booking_id']); ?>">

                                                <?php if ($row['status'] == 'pending') { ?>
                                                    <button type="submit" name="new_status" value="approved"
                                                        class="btn btn-outline-success"><b>Approve</b></button>
                                                    <button type="submit" name="new_status" value="rejected"
                                                        class="btn btn-outline-danger"><b>Reject</b></button>
                                                <?php } else { ?>
                                                    <button type="submit" name="new_status" value="approved"
                                                        class="btn btn-outline-success" disabled><b>Approve</b></button>
                                                    <button type="submit" name="new_status" value="rejected"
                                                        class="btn btn-outline-danger" disabled><b>Reject</b></button>
                                                <?php } ?>
                                            </form>

                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">No pending requests.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal for Date Filter -->
    <div id="bookedDateFilterModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('bookedDateFilterModal')">&times;</span>
            <center>
                <h3>Filter by Booked Date</h3>
            </center>
            <form id="bookedDateFilterForm">
                <div class="form-group">
                    <label for="from_date">From Date:</label>
                    <input type="date" id="from_date" name="from_date" class="form-control" onchange="updateToDate()"
                        onclick="this.showPicker()">
                </div>
                <div class="form-group mt-2">
                    <label for="to_date">To Date:</label>
                    <input type="date" id="to_date" name="to_date" class="form-control" onclick="this.showPicker()">
                </div>
                <center>
                    <button type="submit" class="btn btn-primary mt-3">Apply Filter</button>
                </center>
            </form>
        </div>
    </div>

    <!-- Filter Modal for Start and End Date -->
    <div id="dateRangeFilterModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('dateRangeFilterModal')">&times;</span>
            <center>
                <h3>Filter by Date Range</h3>
            </center>
            <form id="dateRangeFilterForm">
                <div class="form-group">
                    <label for="start_date">Select Start Date:</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" onchange="updateEndDate()"
                        onclick="this.showPicker()">
                </div>
                <div class="form-group">
                    <label for="end_date">Select End Date:</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" onclick="this.showPicker()">
                </div>
                <center>
                    <button type="submit" class="btn btn-primary mt-3">Apply Filter</button>
                </center>
            </form>
        </div>
    </div>
    <!-- Filter Modal for Hall Details -->

    <?php
    $sql_halls = "
SELECT DISTINCT r.hall_name 
FROM hall_details r
JOIN hall_type ht ON r.type_id = ht.type_id
JOIN departments d ON d.department_id = r.department_id";

    // Apply filtering based on the user's role (if needed)
    if ($_SESSION['role'] === 'hod') {
        $department_id = $_SESSION['department_id'];
        $sql_halls .= " WHERE r.department_id = $department_id";
    } elseif ($_SESSION['role'] === 'dean') {
        $school_id = $_SESSION['school_id'];
        $sql_halls .= " WHERE d.school_id = $school_id";
    }

    // Execute the query
    $result_halls = $conn->query($sql_halls);
    $hall_names = [];
    if ($result_halls->num_rows > 0) {
        while ($row = $result_halls->fetch_assoc()) {
            $hall_names[] = $row['hall_name'];
        }
    }

    ?>
    <div id="hallDetailFilterModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('hallDetailFilterModal')">&times;</span>
            <center>
                <h3>Filter by Hall Name</h3>
            </center>
            <form id="hallDetailFilterForm">
                <div class="form-group">
                    <label for="hall_name">Select Hall Name:</label>
                    <select id="hall_name" name="hall_name" class="form-control">
                        <option value="">-- Select Hall --</option>
                        <?php foreach ($hall_names as $hall_name): ?>
                            <option value="<?php echo htmlspecialchars($hall_name); ?>">
                                <?php echo htmlspecialchars($hall_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <center>
                    <button type="submit" class="btn btn-primary mt-3">Apply Filter</button>
                </center>
            </form>
        </div>
    </div>

    <?php
    $sql_organisers = "
SELECT DISTINCT organiser_department, organiser_name 
FROM bookings
ORDER BY organiser_department, organiser_name";

    // Execute the query
    $result_organisers = $conn->query($sql_organisers);

    // Process the results into a structured array
    $organizedData = [];
    if ($result_organisers->num_rows > 0) {
        while ($row = $result_organisers->fetch_assoc()) {
            $department = $row['organiser_department'];
            $name = $row['organiser_name'];

            if (!isset($organizedData[$department])) {
                $organizedData[$department] = [];
            }

            // Add the name to the department, ensuring it's not duplicated
            if (!in_array($name, $organizedData[$department])) {
                $organizedData[$department][] = $name;
            }
        }
    }

    ?>

    <!-- Purpose Filter Modal -->
    <div id="purposeFilterModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('purposeFilterModal')">&times;</span>
            <center>
                <h3>Filter by Purpose</h3>
            </center>
            <form id="purposeFilterForm">
                <!-- Radio Buttons for Purpose -->
                <div class="form" style="display: flex; align-items: center; gap: 10px;">
                    <label for="purpose" style="margin: 0;">Select Purpose:</label>
                    <label style="margin: 0;">
                        <input type="radio" name="purpose" value="event" onclick="toggleEventTypeGroup('event')"> Event
                    </label>
                    <label style="margin: 0;">
                        <input type="radio" name="purpose" value="class" onclick="toggleEventTypeGroup('class')"> Class
                    </label>
                </div>




                <!-- Event Type Dropdown (Visible only if Event is selected) -->
                <!-- <div class="mb-3 mt-3" id="event-type-group" style="display: none;">
                <label for="event_type" class="form-label">Event Type</label>
                <select class="form-select" id="event_type" name="event_type">
                    <option value="seminar">Seminar</option>
                    <option value="faculty_meeting">Faculty Meeting</option>
                    <option value="conference">Conference</option>
                </select>
            </div> -->

                <center>
                    <button type="submit" class="btn btn-primary mt-3">Apply Filter</button>
                </center>
            </form>
        </div>
    </div>

    <div id="organiserFilterModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('organiserFilterModal')">&times;</span>
            <center>
                <h3>Filter by Organizer</h3>
            </center>
            <form id="organiserFilterForm">
                <!-- Organizer Department Dropdown -->
                <div class="form-group">
                    <label for="organiser_department">Select Department:</label>
                    <select id="organiser_department" name="organiser_department" class="form-control"
                        onchange="filterNames()">
                        <option value="">-- Select Department --</option>
                        <?php foreach ($organizedData as $department => $names): ?>
                            <option value="<?php echo htmlspecialchars($department); ?>">
                                <?php echo ucwords($department); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Organizer Name Dropdown -->
                <div class="form-group">
                    <label for="organiser_name">Select Organizer:</label>
                    <select id="organiser_name" name="organiser_name" class="form-control">
                        <option value="">-- Select Organizer --</option>
                        <!-- Names will be dynamically populated -->
                    </select>
                </div>

                <center>
                    <button type="submit" class="btn btn-primary mt-3">Apply Filter</button>
                </center>
            </form>
        </div>
    </div>

    <div id="statusFilterModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('statusFilterModal')">&times;</span>
            <center>
                <h3>Apply By Status</h3>
            </center>
            <form id="statusFilterForm">

                <label for="status">Status:</label>
                <select id="status" name="status" class="form-control">
                    <option value="">-- Select Status --</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <br>
                <center>
                    <button type="submit" class="btn btn-primary mt-3">Apply Filter</button>
                </center>
            </form>
        </div>
    </div>


    <div id="eventImageModal" class="modal1">
        <div class="modal1-content">
            <div style="position: relative; padding: 20px;">
                <img id="eventImage" src="" alt="Event Image"
                    style="max-width: 90%; max-height: 70%; border: 1px solid #fff; display: none;">
                <span class="close-modal1" onclick="closeModal('eventImageModal')">&times;</span>
            </div>
        </div>
    </div>

    <script>
        function showEventImage(imageUrl) {
            const eventImage = document.getElementById('eventImage');
            eventImage.src = imageUrl;
            eventImage.style.display = 'block'; // Make sure it's visible
            document.getElementById('eventImageModal').style.display = 'flex';
        }


        // PHP-encoded data for all departments and their unique names
        const organizedData = <?php echo json_encode($organizedData); ?>;

        function filterNames() {
            const department = document.getElementById("organiser_department").value;
            const nameSelect = document.getElementById("organiser_name");

            // Clear existing options
            nameSelect.innerHTML = '<option value="">-- Select Organizer --</option>';

            if (department && organizedData[department]) {
                // Populate the name dropdown with names for the selected department
                organizedData[department].forEach(name => {
                    const option = document.createElement("option");
                    option.value = name;
                    option.textContent = name;
                    nameSelect.appendChild(option);
                });
            }
        }
        function updateEndDate() {
            // Get the start_date and end_date input fields
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            // If the start_date has a value
            if (startDateInput.value) {
                // Set the end_date to the same date as the start_date if end_date is empty or earlier
                if (!endDateInput.value || new Date(endDateInput.value) < new Date(startDateInput.value)) {
                    endDateInput.value = startDateInput.value;
                }
                // Ensure the end_date's min attribute is updated to the selected start_date
                endDateInput.min = startDateInput.value;
            }
        }

        function updateToDate() {
            // Get the start_date and end_date input fields
            const startDateInput = document.getElementById('from_date');
            const endDateInput = document.getElementById('to_date');

            // If the start_date has a value
            if (startDateInput.value) {
                // Set the end_date to the same date as the start_date if end_date is empty or earlier
                if (!endDateInput.value || new Date(endDateInput.value) < new Date(startDateInput.value)) {
                    endDateInput.value = startDateInput.value;
                }
                // Ensure the end_date's min attribute is updated to the selected start_date
                endDateInput.min = startDateInput.value;
            }
        }
        // Open modal
        // Open modal
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        document.querySelectorAll("#bookedDateFilterForm, #dateRangeFilterForm, #hallDetailFilterForm, #organiserFilterForm, #statusFilterForm").forEach(form => {
            form.onsubmit = function (event) {
                event.preventDefault();
                const formData = new FormData(this);
                let queryString = new URLSearchParams(window.location.search);

                // Add form data to query string, ensure existing values are preserved
                for (const [key, value] of formData.entries()) {
                    if (value) {
                        queryString.set(key, value);
                    } else {
                        queryString.delete(key);
                    }
                }

                // Redirect with updated query string
                window.location.href = "status_update_mail.php?" + queryString.toString();
            };
        });

        // Add a new function to handle clearing all filters
        function clearAllFilters() {
            window.location.href = "status_update_mail.php";
        }

        // Update the clear filters button event listener
        document.querySelector(".clear-filters a").addEventListener("click", function (event) {
            event.preventDefault();
            clearAllFilters();
        });


    </script>

    <script>
        function openCalendarModal(modalId, hallId) {
            console.log("Opening modal for Hall ID: " + hallId);  // Logs Hall ID in console

            // URL for the calendar page
            var url = 'view_calendar.php?hall_id=' + hallId;

            // Set the size of the popup window
            var width = 800;
            var height = 600;

            // Calculate the position to center the window
            var left = (window.innerWidth / 2) - (width / 2);
            var top = (window.innerHeight / 2) - (height / 2);

            // Open the popup window at the calculated position
            var popupWindow = window.open(url, 'PopupWindow', 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',scrollbars=yes');

            // Optional: focus on the popup window
            popupWindow.focus();
        }



    </script>
</body>

</html>

<?php
$conn->close();
?>

