<?php
include 'assets/conn.php';
include 'assets/header.php';

$id = $_SESSION['user_id']; // Logged-in user's ID
$role = $_SESSION['role'];
$department_id = $_SESSION['department_id'];

$filters = [];
$pastBooking = isset($_GET['past_booking']) ? $_GET['past_booking'] : '0';

$semesterBooking = isset($_GET['semester']) ? $_GET['semester'] : '0';
$employee_id = null;
$employeeQuery = "
    SELECT e.employee_id 
    FROM employee e 
    JOIN users u ON e.employee_email = u.email 
    WHERE u.user_id = $id
";
$result = mysqli_query($conn, $employeeQuery);
if ($row = mysqli_fetch_assoc($result)) {
    $employee_id = $row['employee_id'];
}


// Build SQL Query
$sql = "
    SELECT 
        b.booking_id_gen, 
        GROUP_CONCAT(DISTINCT b.booking_id ORDER BY b.booking_id ASC) as booking_ids,
        MIN(b.start_date) as min_start_date,
        MAX(b.end_date) as max_end_date,
        GROUP_CONCAT(DISTINCT b.start_date ORDER BY b.start_date) as start_dates,
        GROUP_CONCAT(DISTINCT b.end_date ORDER BY b.end_date) as end_dates,
        GROUP_CONCAT(DISTINCT b.slot_or_session) as all_slots,
        r.hall_name,
        d.department_name,
        ht.type_name,
        b.*,
        COALESCE(GROUP_CONCAT(DISTINCT b.day_of_week ORDER BY b.day_of_week ASC), '') as day_of_week
    FROM bookings b
    JOIN hall_details r ON b.hall_id = r.hall_id
    LEFT JOIN users u ON b.user_id = u.user_id  -- Join users table to get department_id
    LEFT JOIN departments d ON d.department_id = r.department_id
    LEFT JOIN hall_type ht ON r.type_id = ht.type_id
    WHERE 1=1 ";

    if ($pastBooking == "1") {
        $filters[] =  " b.end_date < CURDATE() AND b.status = 'approved'"; 

    } else {
        $filters[] =  " b.end_date >= CURDATE()"; 
    }

  if ($semesterBooking == "1") {
    $filters[] = "b.day_of_week IS NOT NULL"; 
    if ($employee_id) {
        $filters[] = "(b.user_id = $id OR b.organiser_id = $employee_id)";
    } else {
        $filters[] = "b.user_id = $id";
    }
} else {
    $filters[] = "b.day_of_week IS NULL";
    $filters[] = "b.user_id = $id";
}

if (!empty($_GET['hall_id'])) {
    $filters[] = "b.hall_id = " . intval($_GET['hall_id']);
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
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
    $filters[] = "(b.start_date >= '$start_date' AND b.end_date <= '$end_date')";
}

if (isset($_GET['hall_id']) && !empty($_GET['hall_id'])) {
    $hall_id = $_GET['hall_id'];
    $filters[] = "(r.hall_id LIKE '%$hall_id%')";
}

if (isset($_GET['type_id']) && !empty($_GET['type_id'])) {
    $type_id = $_GET['type_id'];
    $filters[] = "(r.type_id = '$type_id')"; // Add type_id condition
}

if (isset($_GET['purpose']) && !empty($_GET['purpose'])) {
    $purpose = $_GET['purpose'];
    $filters[] = "b.purpose LIKE '%$purpose%'";
}

if (isset($_GET['organiser_name']) && !empty($_GET['organiser_name'])) {
    $organiser_name = $_GET['organiser_name'];
    $filters[] = "b.organiser_name LIKE '%$organiser_name%'";
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

// Append filters to the query
if (!empty($filters)) {
    $sql .= " AND " . implode(" AND ", $filters);
}


$sql .= " GROUP BY b.booking_id_gen ORDER BY b.booking_id DESC";

$filterApplied = !empty($_GET['from_date']) && !empty($_GET['to_date']) || !empty($_GET['start_date']) || !empty($_GET['purpose']) || !empty($_GET['event_type']) || !empty($_GET['purpose_name']) || !empty($_GET['end_date']) ||
    !empty($_GET['hall_id']) || !empty($_GET['type_id']) || !empty($_GET['organiser_name']) || !empty($_GET['status']);


// Debugging: Print the query to check for correctness
// echo $sql;

// Execute the query
$result = $conn->query($sql);

// Check if the query execution was successful
if ($result === false) {
    die('Error executing the SQL query: ' . $conn->error);
}

// Output the result for debugging
// var_dump($result);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall Booking - Check Availability</title>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Include Bootstrap for calendar -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/design1.css" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
            /*  border: 1px solid #888; */
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

        .input-group {
            display: flex;
            align-items: center;
            gap: 10px;
            /* Space between label and input */
        }

        .input-group label {
            width: 120px;
            /* Adjust width to align properly */
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
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .green-button.active {
            background-color: green;
            color: white;
        }
        .yellow-button.active {
    background-color: orange;
    color: white;
}
.rating label {
            cursor: pointer;
            margin-right: 10px;
            
        }
        strong{
            color:#212f3d;
        } 
    </style>
</head>
<bod>

    <div id="main">
        <div class="table-wrapper mt-5">
            <div class="card shadow-lg">
                <div class="card-body">
                    <center>
                        <h3 style="color:#170098; ">Manage Bookings</h3>
                        <center>

                            <div class="row">
                                <div class="col-6">
                                    <div style="display: flex;  align-items: center; gap: 10px; margin: 20px;">
<?php
if (!$pastBooking ):
?>
                                        <!-- Modify (Blue) -->
                                        <button onclick="modifySelected()" class="icon-button blue-button">
                                            <i class="fa-solid fa-pen-to-square"></i> Modify
                                        </button>

                                        <!-- Achieve Selected (Red) -->
                                        <button onclick="cancelModalPopUp()" class="icon-button gray-button">
                                            <i class="fa-solid fa-ban"></i> Cancel
                                        </button>

                                        <?php endif; ?>

                                    </div>
                                </div>
                                <div class="col-6">
                                    <div
                                        style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin: 20px;">
                                        <button id="pastBookingBtn" class="icon-button yellow-button" onclick="togglePastBooking()">Past Booking</button>
                                        <?php if ($user_role == 'hod'): ?>

                                            <button id="semesterToggleBtn" class="icon-button green-button"
                                            onclick="toggleSemesterBooking()">Semester Booking</button>
                                            <?php endif; ?>
                                            
                                        <!-- Clear All Filters (Yellow) -->
                                        <?php if ($filterApplied): ?>
                                            <a href="view_modify_booking.php" class="btn btn-danger">Clear Filters</a>
                                        <?php else: ?>
                                            <button class="icon-button redd-button">
                                                <i class=""></i> Clear Filters
                                            </button>
                                        <?php endif; ?>

                                    </div>
                                </div>
                            </div>


                            <div class="table-container" style="max-height: 500px; overflow-y: auto;">
                                <table class="table table-bordered" id="bookingTable">
                                    <thead>
                                        <th>Select</th>
                                        <th>                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center;">
                                                <span style="flex-grow: 1; text-align: center;">Booked On</span>
                                                <span class="bi bi-sliders" style="cursor: pointer;"
                                                    onclick="openModal('bookedDateFilterModal')"></span>
                                            </div>
                                        </th>
                                        <th>                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center;">
                                                <span style="flex-grow: 1; text-align: center;">Hall Details</span>
                                                <span class="bi bi-sliders" style="cursor: pointer;"
                                                    onclick="openModal('hallDetailFilterModal')"></span>
                                            </div>
                                        </th>
                                        <th>                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center;">
                                                <span style="flex-grow: 1; text-align: center;">Purpose</span>
                                                <span class="bi bi-sliders" style="cursor: pointer;"
                                                    onclick="openModal('purposeFilterModal')"></span>
                                            </div>
                                        </th>
                                        <th>                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center;">
                                                <span style="flex-grow: 1; text-align: center;">Date & Time</span>
                                                <span class="bi bi-sliders" style="cursor: pointer;"
                                                    onclick="openModal('dateRangeFilterModal')"></span>
                                            </div>
                                        </th>
                                        <?php if ($semesterBooking && $user_role == 'hod'): ?>
                                            <th>                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center;">
                                                <span style="flex-grow: 1; text-align: center;">Booked By</span>
                                                <span class="bi bi-sliders" style="cursor: pointer;"
                                                    onclick="openModal('organiserFilterModal')"></span>
                                            </div>
                                        </th>
                                        <?php endif; ?>
                                        <th>                          
    <?php     
if ($pastBooking ):
?>
                        <center>
                        Action
</center>

                        <?php else:?>
<div
                                                style="display: flex; justify-content: space-between; align-items: center;">
                                                <span style="flex-grow: 1; text-align: center;">Status</span>
                                                <span class="bi bi-sliders" style="cursor: pointer;"
                                                    onclick="openModal('statusFilterModal')"></span>
                                        <?php endif; ?>
                                          
                                                </div>
                                        </th>

                                        <?php if ($role === 'admin'): ?>
                                            <th colspan="2" style="width: 10%; text-align: center;">Actions</th>
                                        <?php endif; ?>
                                        </tr>

                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()):
                                                $booking_ids = explode(',', $row['booking_ids']);
                                                $start_dates = explode(',', $row['start_dates']);
                                                $end_dates = explode(',', $row['end_dates']);
                                                $all_slots = explode(',', $row['all_slots']);
                                                ?>
                                                    <td><input type="checkbox"
                                                            style="width: 20px; height: 20px; margin: 50% 50%"
                                                            class="hall-checkbox" value="<?php echo $row['booking_id']; ?>">
                                                    </td>
                                                    <td>

                                                        <center>
                                                            <?php echo date("d-m-Y", strtotime($row['booking_date'])) . "  <br>"; ?>
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
                                                        <div
                                                            style="display: flex; justify-content: space-between; align-items: center;">
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
                                                        if (!empty($row['event_type'])) {
                                                            echo '<span style="color:blue;">' . ucwords(str_replace('_', ' / ', $row['event_type'])) . '</span><br>';
                                                        }
                                                        echo $row['purpose_name'];

                                                        if (!empty($row['day_of_week'])) {
                                                            echo '<br><span style="color:rgba(255, 66, 66, 0.79);"><b>Semester Booking</b></span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <center>
                                                            <?php
                                                            $min_date = date("d-m-Y", strtotime($row['min_start_date']));
                                                            $max_date = date("d-m-Y", strtotime($row['max_end_date']));

                                                            if ($min_date == $max_date) {
                                                                echo $min_date . " (" . date('l', strtotime($min_date)) . ")<br>";
                                                            } else {
                                                                echo $min_date . " (" . date('l', strtotime($min_date)) . ")<br> to <br>" .
                                                                    $max_date . " (" . date('l', strtotime($max_date)) . ")<br>";
                                                            }

                                                            $all_slots = array_unique(explode(',', $row['all_slots']));
                                                            sort($all_slots);

                                                            $forenoon_slots = [1, 2, 3, 4];
                                                            $afternoon_slots = [5, 6, 7, 8];
                                                            $full_day_slots = [1, 2, 3, 4, 5, 6, 7, 8];

                                                            $slot_timings = [
                                                                1 => '9:30 AM',
                                                                2 => '10:30 AM',
                                                                3 => '11:30 AM',
                                                                4 => '12:30 PM',
                                                                5 => '1:30 PM',
                                                                6 => '2:30 PM',
                                                                7 => '3:30 PM',
                                                                8 => '4:30 PM'
                                                            ];

                                                            if ($all_slots == $full_day_slots) {
                                                                echo "<b>Full Day</b>";
                                                            } elseif ($all_slots == $forenoon_slots) {
                                                                echo "<b>Forenoon</b>";
                                                            } elseif ($all_slots == $afternoon_slots) {
                                                                echo "<b>Afternoon</b>";
                                                            } else {
                                                                $booked_timings = implode(", ", array_map(function ($slot) use ($slot_timings) {
                                                                    return $slot_timings[$slot];
                                                                }, $all_slots));
                                                                echo "<b>" . $booked_timings . "</b>";
                                                            }
                                                            ?>
                                                        </center>
                                                    </td>
                                                    <?php if ($semesterBooking && $user_role == 'hod'): ?>

                                                    <td><b><?php echo ucwords($row['organiser_department']); ?></b><br>
                                                        <?php echo ucwords($row['organiser_name']); ?><br>
                                                        <?php echo $row['organiser_mobile']; ?><br>
                                                        <?php echo $row['organiser_email']; ?>


                                                    </td>
                                                    <?php endif;?>
                                                    <td>
                                                    <?php

if ($pastBooking ): ?>
    <button onclick="openFeedbackModal('<?php echo $row['booking_id']; ?>')" class="btn btn-primary">Feedback</button>
                                        <?php else: 
                                        
                                        $status = strtolower($row['status']); // Convert to lowercase for consistency
                                                        if ($status === 'allow') {
                                                            echo "Pending to Forward";
                                                        } elseif ($status === 'pending') {
                                                            echo "Pending to Approve";
                                                        } elseif ($status === 'cancelled') {
                                                            echo "Cancelled <br><br> <span style='color: blue; font-size: 15px;'>(" . htmlspecialchars($row['cancellation_reason']) . ")</span>";
                                                        } else {
                                                            echo ucwords(htmlspecialchars($status)); // Print the original status if not 'allow' or 'pending'
                                                        }
                                                        ?>

                                            <?php endif; ?>
                                        </td>


                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6">No pending requests.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const table = document.getElementById('bookingTable');
                const headers = table.getElementsByTagName('th');
                const bodyRows = table.getElementsByTagName('tr');

                // Set width for td elements based on th widths
                for (let i = 0; i < headers.length; i++) {
                    const width = headers[i].offsetWidth;
                    for (let j = 1; j < bodyRows.length; j++) {
                        const cell = bodyRows[j].cells[i];
                        if (cell) {
                            cell.style.width = width + 'px';
                        }
                    }
                }
            });
        </script>
        <!-- Cancel Booking Modal -->
        <div id="cancelModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal('cancelModal')">&times;</span>
                <center>
                    <h3 style="color: #007bff; margin-bottom:15px;">Cancel Booking</h3>
                </center>
                <h4 class="form-section-title"></h4>

                <form onsubmit="return handleCancelBooking(event);">
                    <input type="hidden" name="booking_id" id="booking_id" value="<?php echo $row['booking_id']; ?>">
                    <!-- Stores selected hall ID -->

                    <div class="form-group mt-3">
                        <select name="reason" id="reason" required onchange="toggleOtherReason()">
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
                    <center><button class="btn btn-primary mt-4" type="submit">Submit</button></center>
                </form>
            </div>
        </div>


        <!-- Booked On Filter Modal -->

        <div id="bookedDateFilterModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal('bookedDateFilterModal')">&times;</span>
                <center>
                    <h3 style="color:#007bff;">Filter by Booked Date</h3>
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


        <?php
        // Fetch hall names and IDs for dropdown
        $sql_halls = "SELECT DISTINCT r.hall_name, b.hall_id, r.type_id 
              FROM hall_details r 
              RIGHT JOIN bookings b ON r.hall_id = b.hall_id
              WHERE user_id = $id";
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
                        <div class="btn-group mt-2" role="group">
                            <input class="btn-check" type="radio" name="type_id" value="1" id="seminar_hall"
                                onclick="filterHalls()">
                            <label class="btn btn-outline-primary" for="seminar_hall">Seminar Hall</label>

                            <input class="btn-check" type="radio" name="type_id" value="2" id="auditorium"
                                onclick="filterHalls()">
                            <label class="btn btn-outline-primary" for="auditorium">Auditorium</label>

                            <input class="btn-check" type="radio" name="type_id" value="3" id="lecture_hall"
                                onclick="filterHalls()">
                            <label class="btn btn-outline-primary" for="lecture_hall">Lecture Hall</label>

                            <input class="btn-check" type="radio" name="type_id" value="4" id="conference_hall"
                                onclick="filterHalls()">
                            <label class="btn btn-outline-primary" for="conference_hall">Conference Hall</label>
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

        // Default query (optional, or could leave empty)
        $sql_class = "SELECT DISTINCT purpose_name, event_type 
                  FROM bookings 
                  WHERE user_id = $id AND purpose = 'class'";

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
                            <input class="btn-check" type="radio" name="purpose" value="event" id="event"
                                onclick="toggleFields('event')">
                            <label class="btn btn-outline-primary" for="event">Event</label>

                            <input class="btn-check" type="radio" name="purpose" value="class" id="class"
                                onclick="toggleFields('class')">
                            <label class="btn btn-outline-primary" for="class">Class</label>
                        </div>
                    </div>

                    <!-- Event Fields -->


                    <!-- Event Fields -->
                    <div id="eventFields" style="display: none;">
                        <div class="form-group mt-3">
                            <label for="event_type" class="form-label">Event Type :</label>
                            <select class="form-control" id="event_type" name="event_type">
                                <option value="">-- Select Event Name --</option>
                                <option value="guest_lectures_seminars">Guest Lectures, Seminars</option>
                                <option value="meetings_ceremonies">Meetings, Ceremonies</option>
                                <option value="workshops_training">Workshops, Training</option>
                                <option value="conferences_symposiums">Conferences, Symposiums</option>
                                <option value="examinations_admissions_interviews">Examinations, Admissions, Interviews
                                </option>
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



        <!-- Date & Time Filter Modal -->
        <div id="dateRangeFilterModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal('dateRangeFilterModal')">&times;</span>
                <center>
                    <h3 style="color:#007bff;">Filter by Date & Time</h3>
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
        <?php
        $department_id = $_SESSION['department_id'];
        $sql_organisers = "
SELECT DISTINCT employee_name 
FROM employee WHERE department_id = $department_id";

        // Execute the query
        $result_organisers = $conn->query($sql_organisers);

        // Process the results into a structured array
        $organisers = [];
        if ($result_organisers->num_rows > 0) {
            while ($row = $result_organisers->fetch_assoc()) {
                $name = $row['employee_name'];

                // Add the name to the organisers array ensuring no duplicates
                if (!in_array($name, $organisers)) {
                    $organisers[] = $name;
                }
            }
        }
        ?>

        <!-- Booked By Filter Modal -->
        <div id="organiserFilterModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal('organiserFilterModal')">&times;</span>
                <center>
                    <h3 style="color:#007bff;">Filter by Organiser</h3>
                </center>
                <h4 class="form-section-title"></h4>
                <form id="organiserFilterForm">
                    <div class="form-group">
                        <div class="input-group">
                            <label for="organiser_name">Organiser:</label>
                            <select id="organiser_name" name="organiser_name" class="form-control">
                                <option value="">-- Select Organiser --</option>
                                <?php foreach ($organisers as $organiser): ?>
                                    <option value="<?php echo htmlspecialchars($organiser); ?>">
                                        <?php echo htmlspecialchars($organiser); ?>
                                    </option>
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
        <!-- Status Filter Modal -->
        <div id="statusFilterModal" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal('statusFilterModal')">&times;</span>
                <center>
                    <h3 style="color:#007bff;">Filter by Status</h3>
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

        <div id="eventImageModal" class="modal1">
            <div class="modal1-content">
                <div style="position: relative; padding: 20px;">
                    <img id="eventImage" src="" alt="Event Image"
                        style="max-width: 90%; max-height: 70%; border: 1px solid #fff; display: none;">
                    <span class="close-modal1" onclick="closeModal('eventImageModal')">&times;</span>
                </div>
            </div>
        </div>

<!-- Feedback Modal -->
<div id="feedbackModal" class="modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered 	modal-xl">
        <div class="modal-content" style="width:60%; margin:0% auto">
            <div class="modal-header">
            <h3 class="modal-title">Feedback</h3>
            <button type="button" class="btn-close" onclick="closeFeedbackModal()"></button>
            </div>
            <div class="modal-body">
            <form action="submit_feedback.php" method="POST">
    <input type="hidden" id="bookingIdInput" name="booking_id"> <!-- Hidden field for booking ID -->
     <h5></h5>
    <table class="table">
        <tbody>
            <tr>
                <td><strong>Overall Rating:</strong></td>
                <td>
                    <input type="radio" name="overall" value="Not Good" required> Not Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="overall" value="Good"> Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="overall" value="Very Good"> Very Good
                </td>
            </tr>
            <tr>
                <td><strong>Cleanliness & Maintenance:</strong></td>
                <td>
                    <input type="radio" name="cleanliness" value="Not Good" required> Not Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="cleanliness" value="Good"> Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="cleanliness" value="Very Good"> Very Good
                </td>
            </tr>
            <tr>
                <td><strong>Seating & Space Availability:</strong></td>
                <td>
                    <input type="radio" name="seating" value="Not Good" required> Not Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="seating" value="Good"> Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="seating" value="Very Good"> Very Good
                </td>
            </tr>
            <tr>
                <td><strong>Lighting & Ambience:</strong></td>
                <td>
                    <input type="radio" name="lighting" value="Not Good" required> Not Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="lighting" value="Good"> Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="lighting" value="Very Good"> Very Good
                </td>
            </tr>
            <tr>
                <td><strong>Audio/Visual Equipment Quality:</strong></td>
                <td>
                    <input type="radio" name="audio" value="Not Good" required> Not Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="audio" value="Good"> Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="audio" value="Very Good"> Very Good
                </td>
            </tr>
            <tr>
                <td><strong>Air Conditioning/Ventilation:</strong></td>
                <td>
                    <input type="radio" name="ac" value="Not Good" required> Not Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="ac" value="Good"> Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="ac" value="Very Good"> Very Good
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Additional Feedback -->
    <div class="mb-3">
        <label class="form-label">Do you find any other problem?</label>
        <textarea class="form-control" name="additional_feedback" placeholder="Describe Your Problem"></textarea>
    </div>

    <div>
        <center><button type="submit" class="btn btn-primary">Submit Feedback</button></center>
    </div>
</form>

            </div>
            
        </div>
    </div>
</div>

<!-- JavaScript for Modal -->
<script>
    function openFeedbackModal(bookingId) {
        document.getElementById('bookingIdInput').value = bookingId;
        document.getElementById('feedbackModal').style.display = 'block';
    }
    function closeFeedbackModal() {
        document.getElementById('feedbackModal').style.display = 'none';
    }
</script>

        <script>
        function togglePastBooking() {
    let url = new URL(window.location.href);
    
    // Get current state of past_booking
    let pastBooking = url.searchParams.get("past_booking");

    if (pastBooking === "1") {
        url.searchParams.delete("past_booking"); // Remove past booking filter to show future bookings
    } else {
        url.searchParams.set("past_booking", "1"); // Set to show past bookings
        url.searchParams.delete("semester"); // Remove semester booking
    }

    window.location.href = url.toString();
}

function toggleSemesterBooking() {
    let url = new URL(window.location.href);
    
    // Get current state of semester booking
    let semester = url.searchParams.get("semester");

    if (semester === "1") {
        url.searchParams.delete("semester"); // Remove semester booking filter
    } else {
        url.searchParams.set("semester", "1"); // Set to show semester bookings
        url.searchParams.delete("past_booking"); // Remove past booking
    }

    window.location.href = url.toString();
}

document.addEventListener("DOMContentLoaded", function () {
    let urlParams = new URLSearchParams(window.location.search);

    // Semester Booking Button
    let semesterButton = document.getElementById("semesterToggleBtn");
    if (semesterButton) {
        if (urlParams.get("semester") === "1") {
            semesterButton.classList.add("active");
        } else {
            semesterButton.classList.remove("active");
        }
    }

    // Past Booking Button
    let pastButton = document.getElementById("pastBookingBtn");
    if (pastButton) {
        if (urlParams.get("past_booking") === "1") {
            pastButton.classList.add("active");
        } else {
            pastButton.classList.remove("active");
        }
    }
});




            function modifySelected() {
                const selected = document.querySelectorAll('.hall-checkbox:checked');

                if (selected.length === 0) {
                    alert("Please select a hall to modify.");
                    return;
                }

                if (selected.length > 1) {
                    alert("You can modify only one hall at a time.");
                    return;
                }

                const hallId = selected[0].value;
                window.location.href = `edit_booking.php?id=${hallId}`;

            }
            function cancelModalPopUp() {
                const selectedHall = document.querySelector('.hall-checkbox:checked');

                if (!selectedHall) {
                    alert("Please select a hall to modify the Availability.");
                    return;
                }

                const hallId = selectedHall.value;
                document.getElementById('booking_id').value = hallId; // Set hall ID in form
                document.getElementById('cancelModal').style.display = 'block';
            }
            function showEventImage(imageUrl) {
                const eventImage = document.getElementById('eventImage');
                eventImage.src = imageUrl;
                eventImage.style.display = 'block'; // Make sure it's visible
                document.getElementById('eventImageModal').style.display = 'flex';
            }



            // PHP-encoded data for all departments and their unique names
          
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
                    window.location.href = "view_modify_booking.php?" + queryString.toString();
                };
            });

            // Add a new function to handle clearing all filters
            function clearAllFilters() {
                window.location.href = "view_modify_booking.php";
            }

            



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