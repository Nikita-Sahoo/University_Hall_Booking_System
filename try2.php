<?php
include 'assets/conn.php';
include 'assets/header.php';
$sql = "
    SELECT 
        b.*, 
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
            border: 1px solid #888;
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
      .conflict-group-1 {
        background-color: #FFCCCB !important;
    }
    .conflict-group-2 {
        background-color: #FFDAB9 !important;
    }
    .conflict-group-3 {
        background-color: #E6E6FA !important;
    }
    .conflict-group-4 {
        background-color: #98FB98 !important;
    }
    .conflict-group-5 {
        background-color: #ADD8E6 !important;
    }
    .no-conflict {
        background-color: #FFFFFF !important;
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
        <?php 
        $conflict_query = "
        SELECT b.*,  r.hall_name, 
            d.department_name, 
            ht.type_name 
        FROM bookings b
        JOIN hall_details r ON b.hall_id = r.hall_id
        LEFT JOIN departments d ON d.department_id = r.department_id
        LEFT JOIN hall_type ht ON r.type_id = ht.type_id
        WHERE b.hall_id = ? 
        AND b.booking_id != ?
        AND b.status IN ('approved', 'pending')
        AND (
            (b.start_date <= ? AND b.end_date >= ?) OR
            (b.start_date <= ? AND b.end_date >= ?) OR
            (b.start_date BETWEEN ? AND ?) OR
            (b.end_date BETWEEN ? AND ?)
        )
        AND (
            FIND_IN_SET(SUBSTRING_INDEX(?, ',', 1), b.slot_or_session) > 0 OR
            FIND_IN_SET(SUBSTRING_INDEX(?, ',', 2), b.slot_or_session) > 0 OR
            FIND_IN_SET(SUBSTRING_INDEX(?, ',', 3), b.slot_or_session) > 0 OR
            FIND_IN_SET(SUBSTRING_INDEX(?, ',', 4), b.slot_or_session) > 0 OR
            FIND_IN_SET(SUBSTRING_INDEX(?, ',', 5), b.slot_or_session) > 0 OR
            FIND_IN_SET(SUBSTRING_INDEX(?, ',', 6), b.slot_or_session) > 0 OR
            FIND_IN_SET(SUBSTRING_INDEX(?, ',', 7), b.slot_or_session) > 0 OR
            FIND_IN_SET(SUBSTRING_INDEX(?, ',', 8), b.slot_or_session) > 0
        )";

        $stmt = $conn->prepare($conflict_query);
        $conflict_groups = [];
        $group_counter = 0;

        if ($result->num_rows > 0): 
            while ($row = $result->fetch_assoc()):
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
                    $row['slot_or_session']
                );
                $stmt->execute();
                $conflict_result = $stmt->get_result();

                $group_class = '';

                if ($conflict_result->num_rows > 0) {
                    $conflict_row = $conflict_result->fetch_assoc();
                    $hall_id = $conflict_row['hall_id'];
                    $booking_date = $conflict_row['start_date']; 
                    $slot = $conflict_row['slot_or_session']; // Example: "1", "2", ..., "8"
                
                    // Extract the slot number
                    $slot_number = intval($slot); // Convert to integer
                
                    // Group slots: 1-4 (Forenoon), 5-8 (Afternoon)
                    $slot_group = ($slot_number >= 1 && $slot_number <= 4) ? 'FN' : 'AN';
                
                    // Create a group key using hall_id, date, and session type
                    $group_key = "{$hall_id}_{$booking_date}_{$slot_group}";
                
                    // If a group doesn't exist, create a new one
                    if (!isset($conflict_groups[$group_key])) {
                        $conflict_groups[$group_key] = $group_counter;
                        $group_counter++;
                    }
                
                    // Assign a color based on the group
                    $group_class = 'conflict-group-' . ($conflict_groups[$group_key] % 5 + 1);
                }
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
                        if (strtolower($row['purpose']) === 'event') {
                            echo '<span style="color:blue;">' . ucwords(str_replace('_', ' / ', $row['event_type'])) . '</span><br>';
                        }
                        echo $row['purpose_name'];
                        ?>
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
                        $booked_slots = array_map('intval', explode(',', $booked_slots_string));
                        sort($booked_slots);

                        $forenoon_slots = [1, 2, 3, 4];
                        $afternoon_slots = [5, 6, 7, 8];
                        $full_day_slots = [1, 2, 3, 4, 5, 6, 7, 8];
                        
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
                            $booked_timings = implode("<br> ", array_map(function ($slot) use ($slot_timings) {
                                return $slot_timings[$slot];
                            }, $booked_slots));
                        }

                        if (!empty($booking_type)) {
                            echo "(" . $booking_type . ")<br>";
                        }

                        if (!empty($booked_timings)) {
                            echo "(" . $booked_timings . ")";
                        }
                        ?>
                    </td>
                    <td>
                        <b><?php echo ucwords($row['organiser_department']); ?></b><br>
                        <?php echo ucwords($row['organiser_name']); ?><br>
                        <?php echo $row['organiser_mobile']; ?><br>
                        <?php echo $row['organiser_email']; ?>
                    </td>
                    <td>
                        <?php
                        $status = strtolower($row['status']);
                        if ($status === 'allow') {
                            echo "Pending to Forward";
                        } elseif ($status === 'pending') {
                            echo "Pending to Approve";
                        } else {
                            echo ucwords(htmlspecialchars($status));
                        }
                        ?>
                       
                    </td>
                    <td>
                        <form action="conflict_bookings.php" method="post" style="display:inline;">
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
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

<?php
$conn->close();
?>

