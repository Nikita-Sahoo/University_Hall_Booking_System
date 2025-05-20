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

// Fetch all hall details with type names
$query = "SELECT DISTINCT 
            h.hall_id, 
            h.hall_name, 
            h.capacity,
            h.availability,
            h.type_id,
            rt.type_name,
            s.school_name, 
            d.department_name,
            h.image
          FROM hall_details h
          LEFT JOIN schools s ON h.school_id = s.school_id
          LEFT JOIN departments d ON h.department_id = d.department_id
          LEFT JOIN hall_type rt ON h.type_id = rt.type_id
          WHERE 1=1";

// Apply the same filters as above
if ($school_id) {
    $query .= " AND h.school_id = " . intval($school_id);
}
if ($department_id) {
    $query .= " AND h.department_id = " . intval($department_id);
}
if ($type_id) {
    $query .= " AND h.type_id = " . intval($type_id);
}
if ($capacity) {
    if ($capacity == '50') {
        $query .= " AND h.capacity < 50";
    } elseif ($capacity == '100') {
        $query .= " AND h.capacity BETWEEN 50 AND 100";
    } elseif ($capacity == '101') {
        $query .= " AND h.capacity > 100";
    }
}
if (!empty($features)) {
    foreach ($features as $feature) {
        $feature = mysqli_real_escape_string($conn, $feature);
        $query .= " AND h.$feature != 'No'";
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

$query .= " ORDER BY rt.type_name, h.hall_name ASC";
$result = mysqli_query($conn, $query);

?>

<!-- Display total count and filtered count -->
<!-- Display total count and filtered count -->
<div class="row" style="margin-top:-50px;">
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
        <div class="col-md-3">
            <div class="card text-center p-3">
                <h5><?php echo htmlspecialchars($hall_types[$type_id] . " - " . $filtered . "/" . $total); ?></h5>
            </div>
        </div>
    <?php endif; ?>
</div>


<!-- Display halls -->
<?php if (mysqli_num_rows($result) > 0): ?>
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
                <tr>
                    <td><?php echo $hall['hall_name']; ?></td>
                    <td><?php echo $hall['type_name']; ?></td>
                    <td><?php echo $hall['department_name']; ?></td>
                    <td><?php echo $hall['capacity']; ?></td>
                    <td>
                        <?php if ($hall['availability'] == 'Yes'): ?>
                            <a href="view_cal.php?hall_id=<?php echo $hall['hall_id']; ?>" class="btn btn-primary">View</a>
                        <?php else: ?>
                            <span style="color: red;">Unavailable</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p class="text-center text-muted">No halls available for the selected criteria.</p>
<?php endif; ?>

<?php mysqli_close($conn); ?>
