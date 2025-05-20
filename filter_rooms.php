<?php
include('assets/conn.php');

// Fetch parameters from GET request
$school_id = $_GET['school_id'] ?? null;
$department_id = $_GET['department_id'] ?? null;
$type_id = $_GET['type_id'] ?? null;
$from_date = $_GET['from_date'] ?? null;
$to_date = $_GET['to_date'] ?? null;
$booking_type = $_GET['booking_type'] ?? null;
$slots = isset($_GET['slots']) ? $_GET['slots'] : [];
$capacity = isset($_GET['capacity']) ? $_GET['capacity'] : '';
$features = isset($_GET['features']) ? $_GET['features'] : [];
$view_choice = isset($_GET['view_choice']) ? trim($_GET['view_choice']) : 'no_image';


$total_count_query = "
    SELECT type_id, COUNT(*) as total_count 
    FROM hall_details 
    WHERE 1=1";

if ($school_id) {
    $total_count_query .= " AND school_id = " . intval($school_id);
}
if ($department_id) {
    $total_count_query .= " AND department_id = " . intval($department_id);
}

$total_count_query .= " GROUP BY type_id";

$total_result = mysqli_query($conn, $total_count_query);
$total_counts = [];
while ($row = mysqli_fetch_assoc($total_result)) {
    $total_counts[$row['type_id']] = $row['total_count'];
}

// Query to get filtered count per type
$filtered_count_query = "
    SELECT COUNT(DISTINCT h.hall_id) as filtered_count, h.type_id 
    FROM hall_details h
    LEFT JOIN schools s ON h.school_id = s.school_id
    LEFT JOIN departments d ON h.department_id = d.department_id
    LEFT JOIN hall_type rt ON h.type_id = rt.type_id
    LEFT JOIN bookings b ON h.hall_id = b.hall_id
    WHERE 1=1";

// Apply filters if selected
if ($school_id) {
    $filtered_count_query .= " AND h.school_id = " . intval($school_id);
}
if ($department_id) {
    $filtered_count_query .= " AND h.department_id = " . intval($department_id);
}
if ($type_id) {
    $filtered_count_query .= " AND h.type_id = " . intval($type_id);
}
if ($capacity) {
    if ($capacity == '50') {
        $filtered_count_query .= " AND h.capacity < 50";
    } elseif ($capacity == '100') {
        $filtered_count_query .= " AND h.capacity BETWEEN 50 AND 100";
    } elseif ($capacity == '101') {
        $filtered_count_query .= " AND h.capacity > 100";
    }
}
if (!empty($features)) {
    foreach ($features as $feature) {
        $feature = mysqli_real_escape_string($conn, $feature);
        $filtered_count_query .= " AND h.$feature != 'No'";
    }
}
if ($from_date && $to_date) {
    $selected_slots = !empty($slots) ? $slots : [];
    if (!empty($selected_slots)) {
        $slot_conditions = [];
        foreach ($selected_slots as $slot) {
            $slot_conditions[] = "FIND_IN_SET($slot, b.slot_or_session)";
        }
        $slot_condition = implode(" OR ", $slot_conditions);

        $filtered_count_query .= " AND h.hall_id NOT IN (
            SELECT DISTINCT b.hall_id 
            FROM bookings b
            WHERE (
                (b.booking_date BETWEEN '$from_date' AND '$to_date') 
                OR (b.start_date <= '$to_date' AND b.end_date >= '$from_date')
            )
            AND ($slot_condition) AND b.status = 'approved'
        )";
    }
}

$filtered_count_query .= " GROUP BY h.type_id";
$filtered_result = mysqli_query($conn, $filtered_count_query);
$filtered_counts = [];
while ($row = mysqli_fetch_assoc($filtered_result)) {
    $filtered_counts[$row['type_id']] = $row['filtered_count'];
}

// Base query without any filters
$query = "SELECT DISTINCT 
            h.hall_id, 
            h.hall_name, 
            h.capacity,
            h.availability,
            h.type_id,
            CONCAT_WS(', ',
                IF(h.wifi != 'No', 'WiFi', NULL),
                IF(h.ac != 'No', 'AC', NULL),
                IF(h.projector != 'No', 'Projector', NULL),
                IF(h.computer != 'No', 'Computer', NULL),
                IF(h.audio_system != 'No', 'Audio System', NULL),
                IF(h.podium != 'No', 'Podium', NULL),
                IF(h.ramp != 'No', 'Ramp', NULL),
                IF(h.smart_board != 'No', 'Smart Board', NULL),
                IF(h.lift != 'No', 'Lift', NULL),
                IF(h.white_board != 'No', 'White Board', NULL),
                IF(h.blackboard != 'No', 'Blackboard', NULL)
            ) AS features,
            s.school_name, 
            d.department_name, 
            rt.type_name,
            h.image
          FROM hall_details h
          LEFT JOIN schools s ON h.school_id = s.school_id
          LEFT JOIN departments d ON h.department_id = d.department_id
          LEFT JOIN hall_type rt ON h.type_id = rt.type_id
          WHERE 1=1"; // Keep this as a base condition to always fetch results

// Apply filters if they exist
if ($school_id) {
    $query .= " AND h.school_id = " . intval($school_id);
}
if ($department_id) {
    $query .= " AND h.department_id = " . intval($department_id);
}
if ($type_id) {
    $query .= " AND h.type_id = " . intval($type_id);
}


// Apply capacity filter (if selected)
if ($capacity) {
    if ($capacity == '50') {
        // Filter for halls with less than 50 capacity
        $query .= " AND h.capacity < 50";
    } elseif ($capacity == '100') {
        // Filter for halls with capacity between 50 and 100 (inclusive)
        $query .= " AND h.capacity BETWEEN 50 AND 100";
    } elseif ($capacity == '101') {
        // Filter for halls with more than 100 capacity
        $query .= " AND h.capacity > 100";
    }
}


// Apply feature filters (if selected)
if (!empty($features)) {
    foreach ($features as $feature) {
        $feature = mysqli_real_escape_string($conn, $feature);
        $query .= " AND h.$feature != 'No'";
    }
}

// Filter by availability based on the selected booking dates and slots/sessions
if ($from_date && $to_date) {
    $selected_slots = [];
    
        if (!empty($slots)) {
        $selected_slots = $slots;
    }

    if (!empty($selected_slots)) {
        $slot_conditions = [];
        foreach ($selected_slots as $slot) {
            $slot_conditions[] = "FIND_IN_SET($slot, b.slot_or_session)";
        }
        $slot_condition = implode(" OR ", $slot_conditions);
    
        $query .= " AND h.hall_id NOT IN (
            SELECT DISTINCT b.hall_id 
            FROM bookings b
            WHERE (
                (b.booking_date BETWEEN '$from_date' AND '$to_date') 
                OR (b.start_date <= '$to_date' AND b.end_date >= '$from_date')
            )
            AND ($slot_condition) AND b.status = 'approved'
        )";
        
    }
}

$query .= "
   
    ORDER BY 
        CASE 
            WHEN hall_name LIKE 'LHC%' THEN 2 
            ELSE 1
        END, 
        hall_name ASC
";
$result = mysqli_query($conn, $query);
?>




<!-- Display total count and filtered count -->
<div class="col-md-8">

<div class="row" style="margin-top:-70px;">
    <?php
    // Fetch hall types
    $hall_types_query = "SELECT type_id, type_name FROM hall_type";
    $hall_types_result = mysqli_query($conn, $hall_types_query);
    $hall_types = [];
    while ($row = mysqli_fetch_assoc($hall_types_result)) {
        $hall_types[$row['type_id']] = $row['type_name'];
    }

    // Display only the selected type_id
    if ($type_id && isset($hall_types[$type_id])): 
        $filtered = $filtered_counts[$type_id] ?? 0;
        $total = $total_counts[$type_id] ?? 0;
        ?>
        <div class="col-md-5">
            <div class="p-2">
                <h4 style="color:#0e00a3;font-weight: bold;"><?php echo htmlspecialchars($hall_types[$type_id] . " (" . $filtered . " / " . $total .')'); ?></h4>
            </div>
        </div>
    <?php endif; ?>
    </div>
    </div>

    <?php
if (mysqli_num_rows($result) > 0):
    $lhc_section = true; // Flag to track LHC header
    $classroom_section = true; // Flag to track Classroom header
    $filtered_classroom_count = 0;
    $filtered_lhc_count = 0;
    
    // Default values to avoid undefined variable errors
    $total_classroom_count = 0;
    $total_lhc_count = 0;

    // Fetch total counts for Classrooms and LHC
    $total_classroom_query = "SELECT COUNT(*) as total FROM hall_details WHERE type_id = 3 AND hall_name NOT LIKE 'LHC%'";
    $total_lhc_query = "SELECT COUNT(*) as total FROM hall_details WHERE type_id = 3 AND hall_name LIKE 'LHC%'";
    if ($school_id) {
        $total_classroom_query .= " AND school_id = " . intval($school_id);
        $total_lhc_query .= " AND school_id = " . intval($school_id);
    }
    if ($department_id) {
        $total_classroom_query .= " AND department_id = " . intval($department_id);
        $total_lhc_query .= " AND department_id = " . intval($department_id);
    }
    $classroom_result = mysqli_query($conn, $total_classroom_query);
    $lhc_result = mysqli_query($conn, $total_lhc_query);

    if ($classroom_result) {
        $total_classroom_count = mysqli_fetch_assoc($classroom_result)['total'] ?? 0;
    }

    if ($lhc_result) {
        $total_lhc_count = mysqli_fetch_assoc($lhc_result)['total'] ?? 0;
    }

    // Count only filtered halls
    mysqli_data_seek($result, 0);
    while ($hall = mysqli_fetch_assoc($result)) {
        if ($hall['type_id'] == 3) {
            if (str_starts_with($hall['hall_name'], 'LHC')) {
                $filtered_lhc_count++;
            } else {
                $filtered_classroom_count++;
            }
        }
    }
    
    mysqli_data_seek($result, 0); // Reset pointer for actual table display

    if ($view_choice === 'tbl'): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Hall Name</th>
                    <th>Type</th>
                    <th>Department</th>
                    <th>Capacity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($hall = mysqli_fetch_assoc($result)): ?>
                    
                    <?php if ($hall['type_id'] == 3 && $classroom_section && $total_classroom_count > 0 && $filtered_classroom_count>0 ): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; font-weight: bold;">
                                Class Rooms (<?php echo $filtered_classroom_count . " / " . $total_classroom_count; ?>)
                            </td>
                        </tr>
                        <?php $classroom_section = false; ?>
                    <?php endif; ?>

                    <?php if ($hall['type_id'] == 3 && $lhc_section && str_starts_with($hall['hall_name'], 'LHC') && $total_lhc_count > 0 && $filtered_lhc_count>0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; font-weight: bold;">
                                Lecture Hall Complex (<?php echo $filtered_lhc_count . " / " . $total_lhc_count; ?>)
                            </td>
                        </tr>
                        <?php $lhc_section = false; ?>
                    <?php endif; ?>

              <tr>
                        <td><?php echo $hall['hall_name']; ?></td>
                        <td><?php echo $hall['type_name']; ?></td>
                        <td><?php echo $hall['department_name']; ?></td>
                        <td><?php echo $hall['capacity']; ?></td>
                        <td>
                            <?php if ($hall['availability'] == 'Yes'): ?>
                                <a href="view_cal.php?hall_id=<?php echo $hall['hall_id']; ?>" class="btn btn-primary">View</a>
                                <a href="book_hall.php?hall_id=<?php echo $hall['hall_id']; ?>&type_id=<?php echo $type_id; ?>&school_name=<?php echo urlencode($hall['school_name']); ?>&department_name=<?php echo urlencode($hall['department_name']); ?>&hall_name=<?php echo urlencode($hall['hall_name']); ?>&from_date=<?php echo isset($from_date) ? $from_date : ''; ?>&to_date=<?php echo isset($to_date) ? $to_date : ''; ?>&booking_type=<?php echo isset($booking_type) ? $booking_type : ''; ?>&session_choice=<?php echo isset($session_choice) ? $session_choice : ''; ?>&slots=<?php echo isset($slots) ? implode(',', $slots) : ''; ?>" class="btn btn-primary">Book</a>
                            <?php else: ?>
                                <span style="color: red;">Unavailable</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php elseif ($view_choice === 'with_image' || $view_choice === 'no_image'): ?>
        <div class="row">
            <?php while ($hall = mysqli_fetch_assoc($result)): ?>
               
                <?php if ($hall['type_id'] == 3 && $classroom_section && $total_classroom_count > 0 && $filtered_classroom_count>0 ): ?>
                    <div class="col-12">
                        <h5 style="color:#0e00a3;font-weight: bold;" class="p-2 mb-3">
                        Class Rooms (<?php echo $filtered_classroom_count . " / " . $total_classroom_count; ?>)
                        </h5>
                        </div>
                        <?php $classroom_section = false; ?>
                    <?php endif; ?>

                    <?php if ($hall['type_id'] == 3 && $lhc_section && str_starts_with($hall['hall_name'], 'LHC') && $total_lhc_count > 0 && $filtered_lhc_count>0): ?>
                        <div class="col-12">
                        <hr>
                        <h5 style="color:#0e00a3;font-weight: bold;" class="p-2 mb-3">
                        Lecture Hall Complex (<?php echo $filtered_lhc_count . " / " . $total_lhc_count; ?>)
                        </h5>
                                </div>
                        <?php $lhc_section = false; ?>
                    <?php endif; ?>
                


                <div class="col-md-3 hall-card">
                    <div class="card mx-auto d-flex flex-column" style="height: 100%; box-shadow: 2px 4px 10px rgba(0, 0, 0, 0.2);">
                        <?php if ($view_choice === 'with_image'): ?>
                            <img src="<?php echo !empty($hall['image']) && file_exists($hall['image']) ? htmlspecialchars($hall['image']) : 'image/sh3.jpeg'; ?>" class="card-img-top" alt="Hall Image" style="height: 150px; object-fit: cover;">
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column" style="flex-grow: 1;">
                        <!-- Removed "text-center"    -->
                        <h5 class="card-title" style="font-size: 1.2rem; font-weight: bold; color: #343a40;">
                                <?php echo $hall['hall_name']; ?>
                                <span class="text-center" style="font-size: 1rem; color: #6c757d;">
                                    (<?php echo $hall['type_name']; ?>)
                                </span>
                            </h5>
                            <p class="card-text" style="font-size: 15px; color: rgb(67, 20, 199);">
                                <?php echo $hall['department_name']; ?>
                                <br><span class="card-text" style="font-size: 1rem; color: #fd7e14;">
                                    Capacity: <?php echo $hall['capacity']; ?>
                                </span>
                            </p>
                        </div>

                        <div class="card-footer" style="margin-top: auto; text-align: center; background-color: aliceblue;">
                            <?php if ($hall['availability'] == 'Yes'): ?>
                                <a href="view_cal.php?hall_id=<?php echo $hall['hall_id']; ?>" class="btn btn-primary" style="width: 45%;">View</a>
                                <a href="book_hall.php?hall_id=<?php echo $hall['hall_id']; ?>&type_id=<?php echo $type_id; ?>&school_name=<?php echo urlencode($hall['school_name']); ?>&department_name=<?php echo urlencode($hall['department_name']); ?>&hall_name=<?php echo urlencode($hall['hall_name']); ?>&from_date=<?php echo isset($from_date) ? $from_date : ''; ?>&to_date=<?php echo isset($to_date) ? $to_date : ''; ?>&booking_type=<?php echo isset($booking_type) ? $booking_type : ''; ?>&session_choice=<?php echo isset($session_choice) ? $session_choice : ''; ?>&slots=<?php echo isset($slots) ? implode(',', $slots) : ''; ?>" class="btn btn-primary" style="width: 45%;">Book</a>
                            <?php else: ?>
                                <span style="color: red;">Unavailable: <?php echo $hall['availability']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="col-12">
            <p class="text-center text-muted">No halls available for the selected criteria.</p>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="col-12">
        <p class="text-center text-muted">No halls available for the selected criteria.</p>
    </div>
<?php endif;

mysqli_close($conn);
