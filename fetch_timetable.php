<?php
include 'assets/conn.php'; 

// Set the correct content type for JSON output
header('Content-Type: application/json');

// Check that all required GET parameters are provided
if (isset($_GET['hall_id'], $_GET['start_date'], $_GET['end_date'])) {
    $hall_id    = $_GET['hall_id'];
    $start_date = $_GET['start_date']; // Expected format: YYYY-MM-DD
    $end_date   = $_GET['end_date'];   // Expected format: YYYY-MM-DD

    $timetable = [];

    // Modify the query to include date filtering.
    // This assumes your bookings table has 'booking_start' and 'booking_end' columns.
    $query = "
        SELECT 
            day_of_week, 
            slot_or_session, 
            purpose_name 
        FROM 
            bookings 
        WHERE 
            hall_id = '$hall_id'
            AND start_date >= '$start_date'
            AND end_date <= '$end_date'
    ";

    $result = mysqli_query($conn, $query);

    if ($result) {
        // Process each booking row
        while ($row = mysqli_fetch_assoc($result)) {
            $day = $row['day_of_week'];
            // Split the slot_or_session string into an array if it contains multiple slots
            $slots = explode(',', $row['slot_or_session']);
            
            // Populate the timetable array with purpose names per day and slot
            foreach ($slots as $slot) {
                // Trim any extra whitespace
                $slot = trim($slot);
                $timetable[$day][$slot] = $row['purpose_name'];
            }
        }

        // Return the timetable as JSON
        echo json_encode($timetable);
    } else {
        // Return an error message if the query fails
        echo json_encode(["error" => "Query failed: " . mysqli_error($conn)]);
    }
} else {
    // Return an error if any required parameter is missing
    echo json_encode(["error" => "Required parameters (hall_id, start_date, end_date) not provided"]);
}
?>
