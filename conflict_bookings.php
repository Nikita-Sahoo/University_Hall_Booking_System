<?php
include 'assets/conn.php';
include 'assets/header.php';
$sql = "
   SELECT b.*, 
        r.hall_name, 
        d.department_name, 
        ht.type_name
         FROM bookings b
    JOIN hall_details r ON b.hall_id = r.hall_id
    LEFT JOIN departments d ON d.department_id = r.department_id
    LEFT JOIN hall_type ht ON r.type_id = ht.type_id
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

if (isset($_GET['hall_id']) && !empty($_GET['hall_id'])) {
    $hall_id = $_GET['hall_id'];
    $filters[] = "(r.hall_id LIKE '%$hall_id%')";
}


if (isset($_GET['type_id']) && !empty($_GET['type_id'])) {
    $type_id = $_GET['type_id'];
    $filters[] = "(r.type_id = '$type_id')"; // Add type_id condition
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

if (isset($_GET['event_type']) && !empty($_GET['event_type'])) {
    $event_type = $_GET['event_type'];
    $filters[] = "b.event_type LIKE '%$event_type%'";
}

if (isset($_GET['purpose_name']) && !empty($_GET['purpose_name'])) {
    $purpose_name = $_GET['purpose_name'];
    $filters[] = "b.purpose_name LIKE '%$purpose_name%'";
}

// Apply filters to SQL query if any are present
if (!empty($filters)) {
    $sql .= " AND " . implode(" AND ", $filters);
}


$sql .= " ORDER BY start_date, end_date, slot_or_session, hall_id";

// Check if any filters are applied
$filterApplied = !empty($_GET['from_date']) && !empty($_GET['to_date'])  || !empty($_GET['start_date']) || !empty($_GET['purpose']) || !empty($_GET['event_type'])  || !empty($_GET['purpose_name']) || !empty($_GET['end_date']) ||
    !empty($_GET['hall_id']) || !empty($_GET['type_id']) || !empty($_GET['organiser_name']) || !empty($_GET['status']);

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
            // Convert slot_or_session to REGEXP pattern (e.g., "1,2,3" → "1|2|3")
            $slot_pattern = str_replace(',', '|', $slot_or_session);
        
            // Check for conflicting approved bookings
            $sql_check_conflict = "SELECT booking_id FROM bookings 
                WHERE hall_id = ? 
                AND status = 'approved'
                AND (
                    (? BETWEEN start_date AND end_date) OR 
                    (? BETWEEN start_date AND end_date) OR
                    (start_date BETWEEN ? AND ?) OR 
                    (end_date BETWEEN ? AND ?)
                )
                AND slot_or_session REGEXP ?"; // Use REGEXP instead of FIND_IN_SET()
        
            $stmt = $conn->prepare($sql_check_conflict);
            if (!$stmt) {
                die("Prepare failed: " . $conn->error); // Debugging statement
            }
        
            $stmt->bind_param("isssssss", 
                $hall_id, 
                $start_date, 
                $end_date, 
                $start_date, 
                $end_date, 
                $start_date, 
                $end_date,$slot_pattern); 
        
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->close();
                echo "<script>alert('Error: Conflicting booking already approved for this date range and time slot.');</script>";
                echo "<script>window.location.href = 'conflict_bookings.php';</script>";
                exit();
            }
            $stmt->close();
        
        $sql_reject_conflicts = "UPDATE bookings 
        SET status = 'rejected' 
        WHERE hall_id = ? 
        AND status in ( 'pending', 'allow')
        AND (
            (? BETWEEN start_date AND end_date) OR 
            (? BETWEEN start_date AND end_date) OR
            (start_date BETWEEN ? AND ?) OR 
            (end_date BETWEEN ? AND ?)
        )
        AND slot_or_session REGEXP ?";

    $stmt = $conn->prepare($sql_reject_conflicts);
    $stmt->bind_param("isssssss", 
        $hall_id, 
        $start_date, 
        $end_date, 
        $start_date, 
        $end_date, 
        $start_date, 
        $end_date,$slot_pattern);
    $stmt->execute();
    $stmt->close();}

        // If no conflict, update status
        $sql_update = "UPDATE bookings SET status = ? WHERE booking_id = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("si", $new_status, $booking_id);

        if ($stmt->execute()) {
            $stmt->close();
            echo "<script>window.location.href = 'update_conflicts.php?booking_id=$booking_id&new_status=$new_status';</script>";
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
            /* border: 1px solid #888;*/
            width: 40%;
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
        
        .conflict-group-1, .conflict-group-1 td {
            background-color:rgb(255, 226, 225) !important;
        }
        .conflict-group-2, .conflict-group-2 td {
            background-color:rgb(236, 236, 255) !important;
        }
        .conflict-group-3, .conflict-group-3 td {
            background-color:rgb(255, 239, 224) !important;
        }
        .conflict-group-4, .conflict-group-4 td {
            background-color:rgb(235, 255, 235) !important;
        }
        .conflict-group-5, .conflict-group-5 td {
            background-color:rgb(224, 247, 255) !important;
        }

        .input-group {
        display: flex;
        align-items: center;
        gap: 10px; /* Space between label and input */
    }

    .input-group label {
        width: 120px; /* Adjust width to align properly */
        font-weight: bold;
    }

    .input-group input {
        flex: 1;
        padding: 8px;
        border: 1px solid #ccc;
    }

    .radio-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .radio-group input {
        margin-right: 5px;
    }

    .form-group {
        margin-bottom: 10px;
    }

    .btn-primary {
        background-color: #007bff;
        color: white;
        border: none;
        /* padding: 10px 15px; */
        border-radius: 5px;
        cursor: pointer;
    }

    .btn-primary:hover {
        background-color: #0056b3;
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
                        <table class=" table-bordered" id="bookingTable">
                            <thead>  
                            <?php if ($filterApplied): ?>
                                <div class="clear-filters">
                                    <a href="conflict_bookings.php" class="btn btn-danger">Clear Filters</a>
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
                                        <!-- <span class="bi bi-sliders" style="cursor: pointer;"
                                            onclick="openModal('statusFilterModal')"></span> -->
                                    </div>
                                </th>

                                <th width="10%">
                                    <center>Action </center>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
$conflict_query = "
SELECT b.*, r.hall_name, d.department_name, ht.type_name 
FROM bookings b
JOIN hall_details r ON b.hall_id = r.hall_id
LEFT JOIN departments d ON d.department_id = r.department_id
LEFT JOIN hall_type ht ON r.type_id = ht.type_id
WHERE b.hall_id = ? 
AND b.booking_id != ? 
AND b.status IN ('approved', 'pending')
AND (
    (b.start_date <= ? AND b.end_date >= ?) -- Check if dates overlap
    OR (b.start_date >= ? AND b.start_date <= ?) -- Check if start date is within range
    OR (b.end_date >= ? AND b.end_date <= ?) -- Check if end date is within range
)
AND (
    -- Check if any slot from the new booking conflicts with existing booking slots
    EXISTS (
        SELECT 1 
        FROM (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(?, ',', numbers.n), ',', -1) slot
              FROM (SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) numbers
              WHERE CHAR_LENGTH(?) - CHAR_LENGTH(REPLACE(?, ',', '')) >= numbers.n - 1
        ) new_slots
        WHERE FIND_IN_SET(new_slots.slot, b.slot_or_session) > 0
    )
)
";

$stmt = $conn->prepare($conflict_query);
$conflict_groups = [];
$group_counter = 0;

if ($result->num_rows > 0): 
    while ($row = $result->fetch_assoc()):
        $stmt->bind_param(
            "iisssssssss", 
            $row['hall_id'], 
            $row['booking_id'], 
            $row['start_date'], 
            $row['end_date'],
            $row['start_date'], // New booking start date
            $row['end_date'],   // New booking end date
            $row['start_date'], // New booking start date
            $row['end_date'],   // New booking end date
            $row['slot_or_session'], // New booking slots
            $row['slot_or_session'], // For CHAR_LENGTH check
            $row['slot_or_session']  // For CHAR_LENGTH check
        );
        
        if (!$stmt->execute()) {
            die("Execution failed: " . $stmt->error);
        }
        
        $conflict_result = $stmt->get_result();
        
        if ($conflict_result->num_rows > 0):
            // Process conflicts...
        while ($conflict_row = $conflict_result->fetch_assoc()):
                $hall_id = $conflict_row['hall_id'];
                $start_date = $conflict_row['start_date'];
                $end_date = $conflict_row['end_date'];
                $slots = explode(',', $conflict_row['slot_or_session']); // Convert to array for flexible slot matching
        
                $group_found = false;
                $merged_group = null;
        
                foreach ($conflict_groups as $key => $group) {
                    list($group_hall, $group_start, $group_end, $group_slots) = explode('_', $key);
                    $group_slots = explode(',', $group_slots); // Convert stored slots to array
        
                    // Check if same hall and date range overlaps
                    $date_overlap = ($start_date <= $group_end && $end_date >= $group_start);
                    
                    // Check if slots overlap (even partially)
                    $slot_overlap = array_intersect($slots, $group_slots); 
        
                    if ($hall_id == $group_hall && $date_overlap && $slot_overlap) {
                        $merged_group = $group; // Use the existing group
                        $group_found = true;
                        break;
                    }
                }
        
                // If a matching group is found, assign the same group
                if ($group_found) {
                    $conflict_groups["{$hall_id}_{$start_date}_{$end_date}_" . implode(',', $slots)] = $merged_group;
                } else { 
                    // Assign new group if no conflict found
                    $conflict_groups["{$hall_id}_{$start_date}_{$end_date}_" . implode(',', $slots)] = $group_counter;
                    $merged_group = $group_counter;
                    $group_counter++;
                }
        
                // Assign consistent class based on group
                $group_class = 'conflict-group-' . ($merged_group % 5 + 1);
                ?>
                
        
                <tr class="<?php echo $group_class; ?>">
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
                            <br><br>
                            <p style="color: red;">Conflict Exists</p>
                                        </td>

                                        <td>
                                            <form action="conflict_bookings.php" method="post" style="display:inline;">
                                                <input type="hidden" name="booking_id"
                                                    value="<?php echo htmlspecialchars($row['booking_id']); ?>">

                                                <?php if ($row['status'] == 'pending') { ?>
                                                    <button type="submit" name="new_status" value="approved"
                                                        class="btn btn-outline-success mt-2"><b>Approve</b></button>
                                                    <button type="submit" name="new_status" value="rejected"
                                                        class="btn btn-outline-danger mt-2"><b>Reject</b></button>
                                                <?php } else { ?>
                                                    <button type="submit" name="new_status" value="approved"
                                                        class="btn btn-outline-success mt-2" disabled><b>Approve</b></button>
                                                    <button type="submit" name="new_status" value="rejected"
                                                        class="btn btn-outline-danger mt-2" disabled><b>Reject</b></button>
                                                <?php } ?>
                                            </form>

                                </td>
</tr>   <?php endwhile; ?>
        <?php endif; ?>
    <?php endwhile; ?>
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
            <h3 style="color:#007bff;" >Filter by Booked Date</h3>
        </center>
        <h4 class="form-section-title"></h4>

        <form id="bookedDateFilterForm">
            <div class="form-group mt-3">
                <div class="input-group">
                    <label for="from_date">From Date:</label>
                    <input type="date" id="from_date" name="from_date" class="form-control"
                        onchange="updateToDate()" onclick="this.showPicker()">
                </div>
            </div>
            <div class="form-group mt-2">
                <div class="input-group">
                    <label for="to_date">To Date:</label>
                    <input type="date" id="to_date" name="to_date" class="form-control"
                        onclick="this.showPicker()">
                </div>
            </div>
            <center>
                <button type="submit" class="btn btn-primary mt-3">Apply Filter</button>
            </center>
        </form>
    </div>
</div>

    <!-- Date & Time Filter Modal -->
    <div id="dateRangeFilterModal" class="modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeModal('dateRangeFilterModal')">&times;</span>
                    <center>
                        <h3 style="color:#007bff;" >Filter by Date & Time</h3>
                    </center>
        <h4 class="form-section-title"></h4>
                    <form id="dateRangeFilterForm">
                        <div class="form-group mt-3">
                <div class="input-group">
                            <label for="start_date">Start Date:</label>
                            <input type="date" id="start_date" name="start_date" class="form-control"
                                onchange="updateEndDate()" onclick="this.showPicker()">
                        </div>
                        </div>
                        <div class="form-group mt-3">
                        <div class="input-group">
                            <label for="end_date">End Date:</label>
                            <input type="date" id="end_date" name="end_date" class="form-control"
                                onclick="this.showPicker()">
                        </div>
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
SELECT DISTINCT r.hall_name, r.hall_id, r.type_id 
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

$sql_halls .= " ORDER BY r.hall_name";

    $result_halls = $conn->query($sql_halls);

   
$halls = [];

if ($result_halls && $result_halls->num_rows > 0) {
    while ($row = $result_halls->fetch_assoc()) {
        $halls[] = [
            'hall_name' => $row['hall_name'],
            'hall_id' => $row['hall_id'],
            'type_id' => $row['type_id']
        ];
    }
}

    ?>
    
<div id="hallDetailFilterModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('hallDetailFilterModal')">&times;</span>
        <center>
            <h3 style="color:#007bff;">Filter Booking Details</h3>
        </center>
        <h4 class="form-section-title"></h4>
        <form id="hallFilterForm">
            <!-- Hall Type Selection -->
            <div class="form-group mt-3">

                <label><b>Hall Type:</b></label>
            <div class="btn-group mt-2"  role="group">
                    <input class="btn-check"  type="radio" name="type_id" value="1" id="seminar_hall" onclick="filterHalls()">
                    <label class="btn btn-outline-primary" for="seminar_hall">Seminar Hall</label>

                    <input class="btn-check" type="radio" name="type_id" value="2" id="auditorium" onclick="filterHalls()">
                    <label class="btn btn-outline-primary"  for="auditorium">Auditorium</label>

                    <input class="btn-check" type="radio" name="type_id" value="3" id="lecture_hall" onclick="filterHalls()">
                    <label class="btn btn-outline-primary" for="lecture_hall">Lecture Hall</label>

                    <input class="btn-check" type="radio" name="type_id" value="4" id="conference_hall" onclick="filterHalls()">
                    <label class="btn btn-outline-primary"for="conference_hall">Conference Hall</label>
                </div>
            </div>

            <!-- Hall Name Selection -->
            <div class="form-group mt-3">
                <label for="hall_id"><b>Hall Name:</b></label>
                <select id="hall_id" name="hall_id" class="form-control">
                    <option value="">-- Select Hall --</option>
                    <?php foreach ($halls as $hall): ?>
                        <option value="<?php echo htmlspecialchars($hall['hall_id']); ?>" 
                                data-type="<?php echo htmlspecialchars($hall['type_id']); ?>">
                            <?php echo htmlspecialchars($hall['hall_name']); ?>
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
<script>
    function filterHalls() {
        let selectedType = document.querySelector('input[name="type_id"]:checked').value;
        let hallDropdown = document.getElementById('hall_id');
        let options = hallDropdown.getElementsByTagName('option');

        for (let i = 0; i < options.length; i++) {
            if (options[i].value === "") continue; // Skip default option
            if (options[i].getAttribute('data-type') === selectedType) {
                options[i].style.display = "block";
            } else {
                options[i].style.display = "none";
            }
        }

        // Reset the selection
        hallDropdown.value = "";
    }
</script>
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

<?php

    // Default query (optional, or could leave empty)
    $sql_class = "SELECT DISTINCT purpose_name, event_type 
                  FROM bookings 
                  WHERE purpose = 'class'";
 
$result_class = $conn->query($sql_class);

if ($result_class && $result_class->num_rows > 0) {
    while ($row = $result_class->fetch_assoc()) {
        $classes[] = [
            'purpose_name' => $row['purpose_name'],
            'event_type' => $row['event_type']
        ];
    }
}
?>
  
<!-- Purpose Filter Modal -->
<div id="purposeFilterModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('purposeFilterModal')">&times;</span>
        <center>
            <h3 style="color:#007bff;">Filter by Purpose</h3>
        </center>
        <h4 class="form-section-title"></h4>

        <form id="purposeFilterForm">
            <!-- Radio Buttons for Purpose -->
            <div class="form-group mt-3">
                <label for="purpose" class="form-label">Purpose :</label>
                <div class="btn-group mt-2" role="group">
                    <input class="btn-check" type="radio" name="purpose" value="event" id="event" onclick="toggleFields('event')">
                    <label class="btn btn-outline-primary" for="event">Event</label>

                    <input class="btn-check" type="radio" name="purpose" value="class" id="class" onclick="toggleFields('class')"> 
                    <label class="btn btn-outline-primary" for="class">Class</label>
                </div>
            </div>

            <!-- Event Fields -->
       

            <!-- Event Fields -->
            <div id="eventFields" style="display: none;">
                <div class="form-group mt-3">
                    <label for="event_type" class="form-label">Event Type :</label>
                    <select class="form-control" id="event_type" name="event_type">
                    <option value="">-- Select Event Type --</option>
                    <option value="guest_lectures_seminars">Guest Lectures, Seminars</option>
                        <option value="meetings_ceremonies">Meetings, Ceremonies</option>
                        <option value="workshops_training">Workshops, Training</option>
                        <option value="conferences_symposiums">Conferences, Symposiums</option>
                        <option value="examinations_admissions_interviews">Examinations, Admissions, Interviews</option>
                    </select>
                </div>

                
            </div>

            <!-- Class Fields -->
            <div id="classFields" style="display:none;">
               

                <div class="form-group mt-3">
                    <label for="course_name" class="form-label">Course Name :</label>
                    <select class="form-control" id="purpose_name" name="purpose_name">
                        <option value="">-- Select Course Name --</option>
                        <?php foreach ($classes as $class): ?>
                            <?php if (!empty($class['purpose_name'])): ?>
                                <option value="<?php echo htmlspecialchars($class['purpose_name']); ?>">
                                    <?php echo htmlspecialchars($class['purpose_name']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <center>
                <button type="submit" class="btn btn-primary mt-3">Apply Filter</button>
            </center>
        </form>
    </div>
</div>

<script>
// Toggle the fields based on the selected purpose
function toggleFields(purpose) {
    if (purpose === 'event') {
        document.getElementById('eventFields').style.display = 'block';
        document.getElementById('classFields').style.display = 'none';
    } else if (purpose === 'class') {
        document.getElementById('eventFields').style.display = 'none';
        document.getElementById('classFields').style.display = 'block';
    }
}
</script>


    <div id="organiserFilterModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('organiserFilterModal')">&times;</span>
            <center>
                <h3 style="color:#007bff;">Filter by Organizer</h3>
            </center>
        <h4 class="form-section-title"></h4>

            <form id="organiserFilterForm">
                <!-- Organizer Department Dropdown -->
                <div class="form-group">
                <div class="input-group">
                <label for="organiser_department">Department:</label>
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
                </div>

                <!-- Organizer Name Dropdown -->
                <div class="form-group">
                <div class="input-group">
                <label for="organiser_name">Organizer:</label>
                    <select id="organiser_name" name="organiser_name" class="form-control">
                        <option value="">-- Select Organizer --</option>
                        <!-- Names will be dynamically populated -->
                    </select>
                </div>
                </div>

                <center>
                    <button type="submit" class="btn btn-primary mt-3">Apply Filter</button>
                </center>
            </form>
        </div>
    </div>

    <!-- Status Filter Modal 
    <div id="statusFilterModal" class="modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="closeModal('statusFilterModal')">&times;</span>
                    <center>
                    <h3 style="color:#007bff;" >Filter by Status</h3>
                    </center>
        <h4 class="form-section-title"></h4>

                    <form id="statusFilterForm">
                        <div class="form-group">
                           
                <div class="input-group">
                <label for="status">Status:</label>
                            <select id="status" name="status" class="form-control">
                                <option value="">-- Select Status --</option>
                                <option value="approved">Approved</option>
                                <option value="allow">Pending to Forward</option>
                                <option value="pending">Pending to Approve</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="rejected">Rejected</option>

                            </select>
                        </div>
                        </div>
                        <center>
                            <button type="submit" class="btn btn-primary mt-3">Apply Filter</button>
                        </center>
                    </form>
                </div>
            </div>
                        -->




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
                window.location.href = "conflict_bookings.php?" + queryString.toString();
            };
        });

        // Add a new function to handle clearing all filters
        function clearAllFilters() {
            window.location.href = "conflict_bookings.php";
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

