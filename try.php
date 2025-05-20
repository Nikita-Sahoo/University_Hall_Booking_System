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


// Apply filters to SQL query if any are present
if (!empty($filters)) {
    $sql .= " AND " . implode(" AND ", $filters);
}


$sql .= " ORDER BY start_date, end_date, slot_or_session, hall_id";

$result = $conn->query($sql);

// Check if the query execution was successful
if ($result === false) {
    die('Error executing the SQL query: ' . $conn->error);
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
<style>

    .sidenav, .navbar, .toggle-btn {
        display: none;
    }
    
        .conflict-group-1, .conflict-group-1 td {
            background-color: #FFCCCB !important;
        }
        .conflict-group-2, .conflict-group-2 td {
            background-color: #FFDAB9 !important;
        }
        .conflict-group-3, .conflict-group-3 td {
            background-color: #E6E6FA !important;
        }
        .conflict-group-4, .conflict-group-4 td {
            background-color: #98FB98 !important;
        }
        .conflict-group-5, .conflict-group-5 td {
            background-color: #ADD8E6 !important;
        }
    </style>
</head>

<body>

    
                        <table>
                            <thead>  
                            
                            <tr>
                                <th width="10%">
                                    Booked On
                                    </div>
                                </th>
                                <th width="20%">
                                Hall Details
                                </th>
                                <th width="15%">
                                Purpose
                                </th>
                                <th width="15%">
                                Date & Time
                                </th>
                                <th width="20%">
                                Booked By
                                </th>
                                <th width="10%">
                                Status
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
$group_counter = 0; // Start with group 0

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
            $start_date = $conflict_row['start_date'];
            $end_date = $conflict_row['end_date'];
            $slot = $conflict_row['slot_or_session'];

            $group_key = "{$hall_id}_{$start_date}_{$end_date}_{$slot}";
            if (!isset($conflict_groups[$group_key])) {
                $conflict_groups[$group_key] = $group_counter;
                $group_counter++;
            }
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
                            <?php if ($group_class != '') { echo "<p style='color: red;'>Conflict Exists</p>"; } ?>
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
</tr>   <?php endwhile; ?>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>

</html>

<?php
$conn->close();
?>

