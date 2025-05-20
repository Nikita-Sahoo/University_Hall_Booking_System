<?php
include('assets/conn.php');
include 'assets/header.php'; 

if (isset($_GET['hall_id'])) {
    $hall_id = intval($_GET['hall_id']);
    $sql = "SELECT h.*, s.*, d.*
    FROM hall_details h
    LEFT JOIN schools s ON h.school_id = s.school_id
    LEFT JOIN departments d ON h.department_id = d.department_id
    WHERE h.hall_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo '<p>Database error: ' . htmlspecialchars($conn->error) . '</p>';
        exit;
    }
    $stmt->bind_param("i", $hall_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $hall = $result->fetch_assoc();
    } else {
        echo '<p>Hall not found.</p>';
        exit;
    }$feature_columns = ['wifi', 'ac', 'projector', 'computer', 'audio_system', 'podium', 'ramp', 'smart_board', 'lift', 'white_board', 'blackboard'];

    $features = [];
    foreach ($feature_columns as $column) {
        if (!empty($hall[$column]) && $hall[$column] != "No") {
            // Convert column name to a user-friendly label
            $features[] = ucfirst(str_replace('_', ' ', $column));
        }
    }

} else {
    echo '<p>No hall selected.</p>';
    exit;
}

function getDaysInMonth($year, $month) {
    return date('t', mktime(0, 0, 0, $month, 1, $year));
}


function getBookedSlots($conn, $hall_id, $date) {
    $query = "SELECT slot_or_session, status, booking_id_gen,booking_date,students_count, organiser_name, organiser_email, organiser_department, organiser_mobile, organiser_department, purpose, event_type, purpose_name 
              FROM bookings 
              WHERE hall_id = ? 
              AND ? BETWEEN start_date AND end_date
              AND status IN ('approved', 'pending')";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $hall_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookedSlots = [];
    while ($row = $result->fetch_assoc()) {
        $slots = explode(',', $row['slot_or_session']);
        foreach ($slots as $slot) {
            $organiserDetails = [
                'id_gen' => htmlspecialchars($row['booking_id_gen']),
                'date' => htmlspecialchars($row['booking_date']),
                'name' => htmlspecialchars($row['organiser_name']),
                'department' => htmlspecialchars($row['organiser_department']),
                'mobile' => htmlspecialchars($row['organiser_mobile']),
                'email' => htmlspecialchars($row['organiser_email']),
                'dept' => htmlspecialchars($row['organiser_department']),
                'capacity' => htmlspecialchars($row['students_count']),
            'purpose' => htmlspecialchars($row['purpose']),
            'event_type' => htmlspecialchars($row['event_type']),
            'purpose_name' => htmlspecialchars($row['purpose_name'])
        ];
            $bookedSlots[] = [
                'slot' => intval($slot),
                'status' => $row['status'],
                'organiserDetails' => $organiserDetails
            ];
        }
    }
    return $bookedSlots;
}

$calendar = [];
$currentYear = date('Y');
$currentMonth = date('n'); // Get the current month as an integer (1-12)
$monthsToView = 3; // Number of months to display (including the current month)

// Loop through the months
for ($month = $currentMonth; $month < $currentMonth + $monthsToView; $month++) {
    // If the month exceeds 12, move to the next year
    $adjustedMonth = $month > 12 ? $month - 12 : $month;
    $adjustedYear = $month > 12 ? $currentYear + 1 : $currentYear;

    $daysInMonth = getDaysInMonth($adjustedYear, $adjustedMonth);

    $monthCalendar = [
        'year' => $adjustedYear,
        'month' => $adjustedMonth,
        'days' => []
    ];

    // Loop through the days in the current month
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf("%04d-%02d-%02d", $adjustedYear, $adjustedMonth, $day);
        $bookedSlots = getBookedSlots($conn, $hall_id, $date);
        $monthCalendar['days'][] = [
            'date' => $date,
            'bookedSlots' => $bookedSlots
        ];
    }

    $calendar[] = $monthCalendar;
}

$calendarJson = json_encode($calendar, JSON_PRETTY_PRINT);
$currentDateTime = date('Y-m-d H:i:s');

// Debug output
echo "<!-- Debug: Calendar Data -->\n";
echo "<!-- " . print_r($calendar, true) . " -->\n";
echo "<!-- End Debug -->\n";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hall['hall_name']); ?> Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/design.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: white;
        }
        #main {
      margin-left:250px;
      transition: margin-left .5s;
      padding: 16px;
      padding-top: 0px;
    }
        .hall-row {
            display: flex;
            flex-wrap: nowrap;
            gap: 20px;
        }
        .hall-details {
            flex: 0 0 25%;
            max-width: 25%;
            background-color: #ffffff;
            text-align: left;
            border: none;
            border-radius: 8px;
        }
        .calendar-container {
  width: 70%;
  margin: 0 auto;
}
.calendar-time-column {
        width: 100px; /* Set fixed width for the time column */
        text-align: center;
        padding: 2px;
        font-size: 0.9rem;
    }


    .calendar-cell {
        width: 20px;
        height: 20px;
        text-align: center;
        vertical-align: middle;
        cursor: pointer;
        margin:2px;
        font-size: 0.9rem;

    }

    .calendar-time-row {
        display: flex;
        flex-direction: row;
    }/* Available cells (green) */
.calendar-cell.available {
    background-color: rgb(85, 255, 122); /* Available */
    border: 1px solid black;
    transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s ease-in-out;
}

.calendar-cell.available:hover {
    background-color: rgb(20, 255, 71); /* Hover color for available */
    border: 1px solid black;
    transform: scale(1.05); /* Slight scale effect */
    box-shadow: 0 2px 5px rgba(0, 180, 39, 0.6); /* Subtle shadow effect */
    color: white; /* Change text color on hover */
}

/* Pending cells (yellow) */
.calendar-cell.pending {
    background-color: rgb(244, 255, 91); /* Pending */
    border: 1px solid black;
    transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s ease-in-out;
}

.calendar-cell.pending:hover {
    background-color: rgb(242, 255, 0); /* Hover color for pending */
    border: 1px solid black;
    transform: scale(1.05); /* Slight scale effect */
    box-shadow: 0 2px 5px rgba(243, 255, 6, 0.6); /* Subtle shadow effect */
    color: black; /* Change text color on hover */
}

/* Approved cells (red) */
.calendar-cell.approved {
    background-color: rgb(255, 103, 115); /* Booked */
    border: 1px solid black;
    transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s ease-in-out;
}

.calendar-cell.approved:hover {
    background-color: rgb(255, 40, 58); /* Hover color for approved */
    border: 1px solid black;
    transform: scale(1.05); /* Slight scale effect */
    box-shadow: 0 2px 5px rgba(255, 40, 58, 0.6); /* Subtle shadow effect */
    color: white; /* Change text color on hover */
}



/* Past cells */
.calendar-cell.past {
    background-color:rgb(162, 162, 162); /* Past */
    border: 1px solid black;
    cursor: not-allowed; /* Disable active state */
}

/* White cells */
.white-cell {
    background-color: white;
    /* border: 1px solid #ddd; */
    cursor: none;

}
.calendar-cell.whitish-cell {
    background-color: rgba(255, 255, 200, 0.3); /* Light yellow overlay */
    border: 1px solid #f0e68c; /* Optional border */
}

.calendar-cell.approved.whitish-cell {
    background-color: rgba(255, 0, 0, 0.3); /* Approved with weekend overlay */
}

.calendar-cell.pending.whitish-cell {
    background-color: rgba(255, 165, 0, 0.3); /* Pending with weekend overlay */
}
.calendar-cell.past.whitish-cell {
    background-color: rgba(87, 87, 87, 0.3); /* Pending with weekend overlay */
}
.calendar-cell.available.whitish-cell {
    background-color: rgba(144, 238, 144, 0.58); /* Available with weekend overlay */
}

.feature-icon {
            font-size: 1.2em;
            color: #007bff;
        }

.day-name{
    font-size: 0.6em;
}
.calendar-cell.weekend-cell {
    color: #333; /* Optional: Adjust text color for better contrast */
}

.calendar-cell.white-cell {
    background-color: #ffffff;
}
.weekend-cell {
    color: red; /* Change the text color for weekends */
}

.day-number.weekend-cell,
.day-name.weekend-cell {
    color: red; /* Apply same color to both day and name */
}


    /* Make the time column's width fixed */
    .time-slot-row {
        display: flex;
        justify-content: flex-start;
        align-items: center;
    }
    .time-slot-row:first-of-type {
        margin-bottom: 20px;
    }
    /* For calendar cells, use flex to ensure layout alignment */
    .time-slot-container {
        display: flex;
        flex-direction: column;
    }

/* Status indicator for approved and pending */
.status-indicator {
    display: none; /* Hide the status indicator */
}

/* Container for the month header */
.month-header {
    display: flex;
    justify-content: space-between; /* Space between elements (buttons and month name) */
    align-items: center;            /* Align items vertically */
    width: 100%;                    /* Make the header take up full width */
    max-width: 300px;               /* Optional: You can set a max-width if you want a limit */
    margin: 0 auto;                 /* This centers the container horizontally */
}

/* Style for the buttons */
.month-header button {
    font-size: 1.5rem;
    padding: 5px 10px;
    cursor: pointer;
    border: 0px;
    background-color: transparent;
}

/* Style for the disabled button */
.month-header button:disabled {
    cursor: not-allowed;
}


#organizer-details-heading {
    display: block;  /* Keep heading visible */
}

.organizer-details-container {
    width: 100%;      /* Set width to 80% */
    padding: 0;      /* Remove any padding */
}
#organizer-details-table {
    width: 100%;      /* Set width to 80% */
    padding: 0;      /* Remove any padding */
}

#organizer-details-table th, 
#organizer-details-table td {
    margin: 0;       /* Remove any margin */
    text-align: center;  /* Center-align text */
    padding:2px;
    font-size: 0.8rem;

}

body {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh; /* Ensure full viewport height */
    margin: 0;
}

.calendar-legend {
    display: flex;
    flex-wrap: wrap;
    justify-content: center; /* Centers the legend items horizontally */
}

.legend-item {
    display: flex;
    align-items: center;
    margin-right: 20px;
}

.legend-color {
    width: 15px;
    height: 15px;
    margin-right: 8px;
    border-radius: 3px; /* Optional: rounds the corners for a softer look */
}

.legend-item span {
    font-size: 14px;
    color: #333; /* Text color for the legend items */
}
        .vertical-line {
        width: 2px; /* Thickness of the vertical line */
        background-color: #000000; /* Light gray color for the line */
        height: auto; /* Adjust height dynamically */
    }

    </style>
</head>
<body>
<div id="main">

        <br>
        <br>
        <br>
        <br>

        <div class="hall-row">
            <div class="hall-details">
               <center> <h3>Hall Details</h3></center><br>
               <b><span style="color:blue;"><?= htmlspecialchars($hall['hall_name']) ?></span></b><br>
               <b><span style="color:black;"><?= htmlspecialchars($hall['school_name']) ?></b></span><br>
                <?= htmlspecialchars($hall['department_name']) ?><br>
                <strong>Capacity:</strong><?php echo $hall['capacity']; ?><br>
                <?php echo $hall['floor']; ?><br>
                <?php echo $hall['zone']; ?><br><br>
                
                <span><strong>Features:</strong></span>
                <div class="feature-grid">
    <?php if (!empty($features)): ?>
        <?php foreach ($features as $feature): ?>
            <div class="feature-item">
                <i class="feature-icon fas fa-check-circle"></i>
                <span style="padding-left:10px;" class="feature-name"><?php echo htmlspecialchars(trim($feature)); ?></span>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No features available.</p>
    <?php endif; ?>
</div>
                <br>
                
                <strong>In-Charge Details:</strong> 
                <br><?= htmlspecialchars($hall['incharge_name']) ?><br>
                <?= htmlspecialchars($hall['designation']) ?><br>
                <a href="mailto:<?= htmlspecialchars($hall['incharge_email']) ?>" style="color: blue; padding:0px">
                    <?= htmlspecialchars($hall['incharge_email']) ?>
                </a>
                <br><?= htmlspecialchars($hall['incharge_intercom']) ?><br>
            </div>
            <div class="vertical-line"></div>

            <div class="calendar-container">
               <center> <h3>Booking Details</h3></center>

        
        <div id="calendar-container"></div>
        
        <center>
        <a href="javascript:history.back()" class="btn btn-primary">Back</a>

    <!-- Book Now Button -->
    <a href="book_hall.php?hall_id=<?php echo $hall['hall_id']; ?>
    &type_id=<?php echo $hall['type_id']; ?>
    &school_name=<?php echo urlencode($hall['school_name']); ?>
    &department_name=<?php echo urlencode($hall['department_name']); ?>
    &hall_name=<?php echo urlencode($hall['hall_name']); ?>" 
    class="btn btn-primary">Book</a>
    </center>
    
<div id="organizer-details-container" style="margin-top: 20px;">
<!-- <center>  <h3 style="display: none;" id="organizer-details-heading">Organizer Details</h3> </center> -->
   <br> <table id="organizer-details-table" border="1" style="display: none;" class="table-wrapper table-bordered">
   <thead>
            <tr>
                <th style="width:30%">Organiser Details</th>
                <th style="width:40%">Purpose</th>
                <th style="width:10%">Participants</th>
                <th style="width:20%">Booked on</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
    </div>
    </div>


    </div>




    <script>
    const calendarData = <?php echo $calendarJson; ?>;
    const currentDateTime = new Date('<?php echo $currentDateTime; ?>');
    const timeSlots = [
        {slot: 1, time: '09:30 AM'}, {slot: 2, time: '10:30 AM'}, {slot: 3, time: '11:30 AM'}, {slot: 4, time: '12:30 PM'},
        {slot: 5, time: '01:30 PM'}, {slot: 6, time: '02:30 PM'}, {slot: 7, time: '03:30 PM'}, {slot: 8, time: '04:30 PM'}
        // ,{slot: 9, time: '05:30 PM'}, {slot: 10, time: '06:30 PM'}, {slot: 11, time: '07:30 PM'}, {slot: 12, time: '08:30 PM'}, {slot: 13, time: '09:30 PM'}
    ];

    let currentMonthIndex = 0;

    function renderCalendar(calendarData, startIndex = 0) {
    const container = document.getElementById('calendar-container');
    container.innerHTML = '';

    const monthElement = document.createElement('div');
    monthElement.className = 'month-calendar';

    const month = calendarData[startIndex];

    const prevButton = startIndex === 0
        ? '<button class="prev-month" disabled>⬅️</button>'
        : '<button class="prev-month" onclick="showPrevMonth()">⬅️</button>';

    const nextButton = startIndex === calendarData.length - 1
        ? '<button class="next-month" disabled>➡️</button>'
        : '<button class="next-month" onclick="showNextMonth()">➡️</button>';

    monthElement.innerHTML = `
    <div class="time-slot-container">
            ${renderTimeSlots(month.days, month.year, month.month)}
        </div>
        
        <div class="calendar-legend">
            <div class="legend-item">
                <div class="legend-color" style="background-color: rgb(85, 255, 122); border: 1px solid black;"></div>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: rgb(244, 255, 91); border: 1px solid black;"></div>
                <span>Pending</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: rgb(255, 103, 115); border: 1px solid black;"></div>
                <span>Booked</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: rgb(162, 162, 162); border: 1px solid black;"></div>
                <span>Past</span>
            </div>
        </div>

        <div class="month-header">
            ${prevButton}
            <h5 style='margin:0; padding: 5px 10px;'>${new Date(month.year, month.month - 1).toLocaleString('default', { month: 'long', year: 'numeric' })}</h5>
            ${nextButton}
        </div>

        
    `;

    container.appendChild(monthElement);
}
    function renderTimeSlots(days, year, month) {
    const daysInMonth = new Date(year, month, 0).getDate();
    const allDays = Array.from({ length: 31 }, (_, i) => i + 1);

    // Create the header row for dates
    const dateRow = `
    <div class="time-slot-row">
        <div class="calendar-time-column"></div>
        ${allDays.map(day => {
            const foundDay = days.find(d => new Date(d.date).getDate() === day);
            if (foundDay) {
                const dayDate = new Date(foundDay.date);
                const dayName = dayDate.toLocaleString('en-US', { weekday: 'short' });
                const isWeekend = dayDate.getDay() === 0 || dayDate.getDay() === 6; // Sunday or Saturday
                const weekendClass = isWeekend ? 'weekend-cell' : '';

                return `
                    <div class="calendar-cell ${weekendClass}">
                        <div class="day-number ${weekendClass}">${day}</div> <!-- Apply weekendClass here -->
                        <div class="day-name ${weekendClass}">${dayName}</div> <!-- Apply weekendClass here -->
                    </div>`;
            } else {
                // Add white-cell for missing days
                return `<div class="calendar-cell white-cell"></div>`;
            }
        }).join('')}
    </div>
`;

    // Create the rows for time slots
    const timeRows = timeSlots.map(({ slot, time }) => `
    <div class="time-slot-row">
        <div class="calendar-time-column">${time}</div>
        ${allDays.map(day => {
            const foundDay = days.find(d => new Date(d.date).getDate() === day);
            const dayDate = foundDay ? new Date(foundDay.date) : null;

            if (dayDate) {
                const [hours, minutes, period] = time.match(/(\d+):(\d+)\s(AM|PM)/).slice(1);
                let hour = parseInt(hours);
                if (period === 'PM' && hour !== 12) {
                    hour += 12;
                } else if (period === 'AM' && hour === 12) {
                    hour = 0;
                }

                // Ensure that slotDateTime is created in the same timezone as currentDateTime
                const formattedTime = `${String(hour).padStart(2, '0')}:${minutes}`;
                const slotDateTime = new Date(`${dayDate.toISOString().split('T')[0]}T${formattedTime}`);

                // Compare the slot date-time with the current date-time
                const currentDateTime = new Date(); // Make sure this is the correct current time
                let cellClass = '';
                const dayOfWeek = dayDate.getDay(); // 0 for Sunday, 6 for Saturday

                // Check if the day is Sunday (0 = Sunday in Date object)
                if (slotDateTime <= currentDateTime) {
                    cellClass = 'past';
                } else {
                    const bookedSlots = foundDay.bookedSlots.filter(bs => bs.slot === slot);
                    if (bookedSlots.length > 0) {
                        const statuses = [...new Set(bookedSlots.map(bs => bs.status))];
                        const status = statuses.includes('approved') ? 'approved' : 'pending';

                        return `
                            <div class="calendar-cell ${status} ${dayOfWeek === 0 || dayOfWeek === 6 ? 'whitish-cell' : ''}" 
                               data-organiser='${JSON.stringify(bookedSlots.map(bs => bs.organiserDetails))}'
                                    onclick="showOrganizerDetails(this)">
                                </div>`;
                    } else {
                        cellClass = 'available';
                    }
                }

                return `
                    <div class="calendar-cell ${cellClass} ${dayOfWeek === 0 || dayOfWeek === 6 ? 'whitish-cell' : ''}" 
                        available">
                    </div>`;
            } else {
                return `<div class="calendar-cell white-cell"></div>`;
            }
        }).join('')}
    </div>
`).join('');

    return dateRow + timeRows;
}
let currentlyActiveCell = null;

function showOrganizerDetails(element) {
    const organiserData = element.getAttribute('data-organiser');
    let organiserDetails;

    const previouslySelected = document.querySelector('.activeAttachment');
    if (previouslySelected) {
        previouslySelected.classList.remove('activeAttachment');
    }

    element.classList.add('activeAttachment');

    try {
        organiserDetails = JSON.parse(organiserData);
    } catch (e) {
        console.error("Invalid JSON in data-organiser:", organiserData);
        return;
    }

    const tableBody = document.querySelector("#organizer-details-table tbody");
    tableBody.innerHTML = "";

    if (Array.isArray(organiserDetails) && organiserDetails.length > 0) {
        const status = element.classList.contains('approved') ? 'approved' :
                       element.classList.contains('pending') ? 'pending' : 'available';

        organiserDetails.forEach(details => {
            const row = document.createElement("tr");

            // Combine organiser details
            const organiserInfo = `
                ${details.name || 'N/A'}<br>
                <b><span style="color:#0e00a3">${details.department || 'N/A'}</b></span><br>
                ${details.email || 'N/A'}<br>
                ${details.mobile || 'N/A'}
            `;

            // Combine purpose details
            const purpose = details.purpose ? details.purpose.toUpperCase() : 'N/A';

// Format event type (replace '_' with '/' and capitalize)
const eventType = details.event_type 
    ? details.event_type.replace(/_/g, '/').replace(/\b\w/g, char => char.toUpperCase()) 
    : '';

// Format purpose name (capitalize first letter)
const purposeName = details.purpose_name 
    ? details.purpose_name.charAt(0).toUpperCase() + details.purpose_name.slice(1) 
    : '';

// Combine purpose details
const purposeInfo = `
    <span style="color:#0e00a3"><b>${purpose}</b></span><br>
   <b> ${(details.purpose === 'event' ? eventType + '<br>' : '')}</b>
    ${purposeName}
`;
            const bookingInfo = `
                ${details.date || 'N/A'}<br>
               <span style="color:#0e00a3"> ${details.id_gen || ''}</span>
            `;
            row.innerHTML = `
                <td>${organiserInfo}</td>
                <td>${purposeInfo}</td>
                <td>${details.capacity || 'N/A'}</td>
                <td>${bookingInfo}</td>
            `;
            tableBody.appendChild(row);
        });

        document.getElementById('organizer-details-table').style.display = 'table';
        document.getElementById('organizer-details-heading').style.display = 'block';
        document.getElementById('booking').style.display = (status === 'approved' ? 'none' : 'block');
        checkAvailability();
    } else {
        document.getElementById('organizer-details-table').style.display = 'none';
        document.getElementById('organizer-details-heading').style.display = 'none';
        document.getElementById('booking').style.display = 'block'; // Default to showing booking form
        checkAvailability();
    }
}



    function showPrevMonth() {
        if (currentMonthIndex > 0) {
            currentMonthIndex--;
            renderCalendar(calendarData, currentMonthIndex);
        }
    }

    function showNextMonth() {
        if (currentMonthIndex < calendarData.length - 1) {
            currentMonthIndex++;
            renderCalendar(calendarData, currentMonthIndex);
        }
    }

    // Initial render
    renderCalendar(calendarData);
    </script>
</body>
</html>









</body>
</html>

