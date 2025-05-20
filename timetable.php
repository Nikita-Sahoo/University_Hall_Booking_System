<?php
include "assets/conn.php"; 
include 'assets/header.php';

$employee_id = $_SESSION['employee_id'];


$slot_times = [
    1 => '09:30:00',
    2 => '10:30:00',
    3 => '11:30:00',
    4 => '12:30:00',
    5 => '13:30:00',
    6 => '14:30:00',
    7 => '15:30:00',
    8 => '16:30:00'
];

$sql = "
   SELECT b.*, h.hall_name, ht.type_name
FROM bookings b
JOIN hall_details h ON b.hall_id = h.hall_id
LEFT JOIN hall_type ht ON h.type_id = ht.type_id
WHERE b.organiser_id = ?
AND (
    b.status = 'approved'
    OR (b.status = 'pending' AND day_of_week IS NOT NULL)
);
";

$stmt = $conn->prepare($sql);


$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();




$events = [];

while ($row = $result->fetch_assoc()) {
   
    $hall_name = $row['hall_name'];
    $purpose = $row['purpose_name'];

    // Ensure 'slot_or_session' field exists
    if (!isset($row['slot_or_session'])) {
        echo "Error: 'slot_or_session' field is missing.<br>";
        continue;
    }

    // Convert slot string ("1,2,3") into an array
    $slot_array = explode(",", $row['slot_or_session']); 

    // Create an event for each slot
    foreach ($slot_array as $slot) {
        $slot = trim($slot); // Remove spaces

        if (!isset($slot_times[$slot])) {
            echo "Invalid slot: " . $slot . "<br>";
            continue;
        }

        $start_time = $row['start_date'] . "T" . $slot_times[$slot];

        $events[] = [
            'title' => $hall_name . " - " . $purpose,
            'start' => $start_time,
            'color' => '#007bff' // Blue color
        ];
    }
}




// Convert events to JSON for JavaScript
$events_json = json_encode($events);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Booking Calendar</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <link rel="stylesheet" href="assets/design.css" />
    
<style>
    /* 🎨 Change background color */
.fc {
    background-color: #f8f9fa; /* Light gray background */
    border-radius: 10px;
    padding: 10px;
}

/* 🗓️ Style the day headers (Mon, Tue, etc.) */
.fc-day-header {
    background-color: #007bff !important; /* Blue background */
    color: black !important;
    font-weight: bold;
    text-transform: uppercase;
    padding: 8px;
    
}

#calendar{
height:525px;}

#main a{
    color: black !important;

}
/* 🔳 Add borders to each time slot */
.fc-timegrid-slot {
    border-bottom: 1px solid #ddd !important; /* Light gray border */
}
.fc-daygrid-dot-event .fc-event-title {
    flex-grow: 1;
    flex-shrink: 1;
    min-width: 0;
    overflow: hidden;
    font-weight: 700;
    color:#007bff;
}
/* 🌟 Highlight the current day */
.fc-day-today {
    background-color: rgba(255, 215, 0, 0.3) !important; /* Light yellow */
    border: 2px solid #ff9800 !important; /* Orange border */
}

/* 🖌 Add border around events */
.fc-event {
    border: 1px solid #343a40 !important; /* Dark border */
    border-radius: 5px;
    font-size: 14px;
}

/* 📅 Month view: add border around dates */
.fc-daygrid-day {
    border: 1px solid #ccc !important;
}

/* 🏷️ Style the title bar */
.fc-toolbar-title {
    font-size: 20px;
    font-weight: bold;
    color: #343a40;
}

/* ⏰ Style time labels */
.fc-timegrid-slot-label {
    font-weight: bold;
    color: #007bff;
}
#main button {
    text-transform: capitalize;
}
.fc-event-time, .fc-timegrid-slot-label {
    text-transform: uppercase;
}
.fc th {
    height: 50px;
    text-align: center;
    vertical-align: middle;
    font-weight: bold;
    color: darkblue  !important;
    background-color: #f0f8ff; 
}
.fc th a{
    
}
</style>
</head>
<body>
<div id="main">
<div class="container mt-5">
    <br>
    <br>
    <br>
    <div id="calendar"></div>
    </div></div>
    <?php include "assets/footer.php";?>
    <script>
 document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');
    
    if (!calendarEl) {
        console.error("Calendar element not found! Check your HTML.");
        return;
    }

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        allDaySlot: false,
        slotMinTime: "09:30:00",
        slotMaxTime: "17:30:00",
        slotDuration: "00:30:00",
        aspectRatio: 1.5,
        eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
        slotLabelFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
        eventMaxStack: 2,
        views: {
            timeGridWeek: { dayMaxEvents: true, eventMinHeight: 20,  displayEventTime: false },
            timeGridDay: { dayMaxEvents: true,  eventMinHeight: 20,  displayEventTime: false },
            dayGridMonth: { dayMaxEvents: 3, eventMaxHeight: 20 }
        },
        events: <?php echo $events_json; ?>,
    });

    calendar.render();

    // Ensure events are loaded correctly
    setTimeout(() => {
        calendar.refetchEvents(); // Refresh event data
        calendar.render(); // Force re-render
    }, 500);

    document.querySelector('.modal-close').addEventListener('click', function () {
        document.getElementById('eventModal').style.display = 'none';
    });
});


    </script>

</body>
</html>

