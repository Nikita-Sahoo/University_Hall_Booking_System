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
        OR (b.status = 'pending' AND b.day_of_week IS NOT NULL) 
    )
";


$stmt = $conn->prepare($sql);


$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();




$events = [];

while ($row = $result->fetch_assoc()) {
   
    $hall_name = $row['hall_name'];
    $purpose = $row['purpose_name'];
    $type_name = ""; 
    $type_query = $conn->prepare("SELECT type_name FROM hall_type WHERE type_id = ?");
    $type_query->bind_param("i", $row['type_id']);
    $type_query->execute();
    $type_result = $type_query->get_result();
    if ($type_row = $type_result->fetch_assoc()) {
        $type_name = $type_row['type_name'];
    }
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
            'title' => $row['hall_name'] . " - " . $row['purpose_name'], 
            'start' => $start_time,
            'color' => '#007bff',
            'extendedProps' => [ // Custom properties
                'purpose' => $row['purpose_name'],
                'event_type' => $row['event_type'],
                'hall_name' => $row['hall_name'],
                'type_name' => $type_name
            ]
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
    /* max-width:100vh; */
}

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
/* Modal Background Overlay */
.modal-overlay {
    display: none; /* Hidden by default */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.4); /* Semi-transparent black */
    z-index: 99; /* Behind the modal */
}

/* Modal Box */
.modal {
    display: none; /* Hidden by default */
    background: white;
    border: 1px solid black;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    width: 300px;
    z-index: 100; /* Above overlay */
}

/* Modal Content */
.modal-content {
    text-align: left;
}

/* Modal Footer */
.modal-footer {
    text-align: right;
    margin-top: 10px;
}

/* Close Button */
.modal-close {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 5px 10px;
    cursor: pointer;
    border-radius: 5px;
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

    <!-- Modal Structure -->
<div id="eventModal" class="modal">
    <div class="modal-content">
        <h4 id="modal-title"></h4>
        <p><strong>Purpose:</strong> <span id="modal-purpose"></span></p>
        <p><strong>Event Type:</strong> <span id="modal-event-type"></span></p>
        <p><strong>Hall Name:</strong> <span id="modal-hall-name"></span></p>
    </div>
    <div class="modal-footer">
        <button class="modal-close btn">Close</button>
    </div>
</div>




    <script>
  document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');
    if (!calendarEl) {
        console.error("Calendar element not found! Check your HTML.");
        return;
    }
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek', // Default view
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        
        allDaySlot: false,
        slotMinTime: "09:30:00", // Start from 9:30 AM
        slotMaxTime: "17:30:00", // End at 5:30 PM
        slotDuration: "00:30:00", // Show 30-minute intervals for better spacing
        height: 'auto', // Auto-adjust height
        aspectRatio: 1.5, // Makes week/day views less tall
        
        eventTimeFormat: { // Format time in events
        hour: 'numeric',
        minute: '2-digit',
        meridiem: 'short'  // Ensures 'AM' and 'PM' instead of 'a' or 'p'
    },
    slotLabelFormat: {
            hour: 'numeric',
            minute: '2-digit',
            meridiem: 'short'
        }, // Show times as 9:30 AM, 10:30 AM, etc.
        eventMaxStack: 2, // Reduce event stacking to avoid clutter
        views: {
            timeGridWeek: {
                dayMaxEvents: true, // Show "+X more" when many events exist
                eventMinHeight: 20 // Reduce event size
            },
            timeGridDay: {
                dayMaxEvents: true,
                eventMinHeight: 20
            },
            dayGridMonth: {
                dayMaxEvents: 3, // Limit events per day in month view
                eventMaxHeight: 20 // Reduce event size
            }
        },
        
        events: <?php echo $events_json; ?>,
        eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
        
        eventClick: function (info) {
            // Populate modal with event details
            document.getElementById('modal-title').innerText = info.event.title;
            document.getElementById('modal-purpose').innerText = info.event.extendedProps.purpose;
            document.getElementById('modal-event-type').innerText = info.event.extendedProps.event_type;
            document.getElementById('modal-hall-name').innerText = info.event.extendedProps.hall_name;

            // Show modal
            document.getElementById('eventModal').style.display = 'block';
        }
    });

    calendar.render();

    // Close modal function
    document.querySelector('.modal-close').addEventListener('click', function () {
        document.getElementById('eventModal').style.display = 'none';
    });
});

    </script>

</body>
</html>

