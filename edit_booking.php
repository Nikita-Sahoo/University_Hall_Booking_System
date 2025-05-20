<?php

include('assets/conn.php');


if (isset($_GET['id'])) {
    $booking_id = $_GET['id'];

    // Prepare the SQL statement with a JOIN to fetch hall name from hall_details table
    $stmt = $conn->prepare("
        SELECT b.*, v.hall_name, v.capacity 
        FROM bookings AS b 
        JOIN hall_details AS v ON b.hall_id = v.hall_id 
        WHERE b.booking_id = ?
    ");
    $stmt->bind_param("i", $booking_id);

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the booking details
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();

        // Add the logic to determine session_choice
        if (in_array($booking['slot_or_session'], [1, 2, 3, 4])) {
            $booking['session_choice'] = 'fn'; // Forenoon
        } elseif (in_array($booking['slot_or_session'], [5, 6, 7, 8])) {
            $booking['session_choice'] = 'an'; // Afternoon
        } elseif (in_array($booking['slot_or_session'], range(1, 8))) {
            $booking['session_choice'] = 'both'; // Both sessions
        }
    } else {
        echo "No booking found for the provided ID.";
        $booking = null; // Set booking to null to avoid undefined variable warning
    }

    // Close the statement
    $stmt->close();
} else {
    echo "Booking ID is not set.";
    $booking = null; // Set booking to null to avoid undefined variable warning
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- <link rel="stylesheet" href="assets/design.css" /> -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <link rel="stylesheet" href="assets/design.css" />
    <title>admin Home</title>
    <style>
        .container {
            margin-left: 250px;
            width: calc(100% - 250px);
            /* Make container take full width minus side nav */
            max-width: none;
            /* Remove any max-width restriction */
        }

        .container-fluid {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2%;
        }
    </style>

</head>

<body>
    <?php include 'assets/header.php' ?>

    <div id="main">
        <div class="row justify-content-center">
            <div class="col-md-8 mt-5">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4" style="color:#170098;">Update Booking</h2>
                        <form action="update_booking.php" method="post" onsubmit="return validateForm()">
                            <input type="hidden" name="booking_id"
                                value="<?php echo htmlspecialchars($booking['booking_id']); ?>">
                            <input type="hidden" name="hall_id"
                                value="<?php echo htmlspecialchars($booking['hall_id'] ?? ''); ?>"> <input type="hidden"
                                name="status" value="<?php echo htmlspecialchars($booking['status']); ?>">
                            <input type="hidden" name="booking_date" value="<?php echo date('Y-m-d'); ?>">

                            <div class="form-group mb-3">
                                <label for="hall_name" class="form-label">Hall Name:</label>
                                <input type="text" class="form-control" name="hall_name"
                                    value="<?php echo htmlspecialchars($booking['hall_name']); ?>" disabled>
                            </div>

                            <div class="mb-4">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date" class="form-label">Start Date:</label>
                                        <input type="date" name="start_date" id="start_date" class="form-control"
                                            value="<?php echo htmlspecialchars($booking['start_date']); ?>" required
                                            onchange="checkAvailability()" onclick="this.showPicker()">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="end_date" class="form-label">End Date:</label>
                                        <input type="date" name="end_date" id="end_date" class="form-control"
                                            value="<?php echo htmlspecialchars($booking['end_date']); ?>" required
                                            onchange="checkAvailability()" onclick="this.showPicker()">
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label">Booking Type:</label><br>
                                    <div class="btn-group" role="group" aria-label="Booking Type">
                                        <input type="radio" class="btn-check" name="booking_type" id="session"
                                            value="session" required onclick="showSessionOptions(); checkAvailability()"
                                            <?php echo ($booking['slot_or_session'] == 'session') ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary" for="session">Session</label>

                                        <input type="radio" class="btn-check" name="booking_type" id="slot" value="slot"
                                            required onclick="showSlotOptions(); checkAvailability()" <?php echo ($booking['slot_or_session'] == 'slot') ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary" for="slot">Slot</label>
                                    </div>
                                </div>

                                <div class="form-group mb-3" id="session_options" style="display:none;">
                                    <label class="form-label">Choose Session:</label><br>
                                    <div class="btn-group" role="group" aria-label="Session Choice">
                                        <input type="radio" class="btn-check" name="session_choice" value="fn" id="fn"
                                            onchange="checkAvailability()" <?php echo ($booking['slot_or_session'] == '1,2,3,4') ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary" for="fn">Forenoon</label>

                                        <input type="radio" class="btn-check" name="session_choice" value="an" id="an"
                                            onchange="checkAvailability()" <?php echo ($booking['slot_or_session'] == '5,6,7,8') ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary" for="an">Afternoon</label>

                                        <input type="radio" class="btn-check" name="session_choice" value="both"
                                            id="both" onchange="checkAvailability()" <?php echo ($booking['slot_or_session'] == '1,2,3,4,5,6,7,8') ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary" for="both">Both</label>
                                    </div>
                                </div>

                                <div class="form-group mb-3" id="slot_options" style="display:none;">
                                    <label class="form-label">Choose Slot(s):</label><br>
                                    <div class="row">
                                        <!-- Row 1 -->
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="slots[]"
                                                    id="slot1" value="1" onchange="checkAvailability()" <?php echo (in_array(1, explode(',', $booking['slot_or_session']))) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="slot1">09:30am</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="slots[]"
                                                    id="slot2" value="2" onchange="checkAvailability()" <?php echo (in_array(2, explode(',', $booking['slot_or_session']))) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="slot2">10:30am</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="slots[]"
                                                    id="slot3" value="3" onchange="checkAvailability()" <?php echo (in_array(3, explode(',', $booking['slot_or_session']))) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="slot3">11:30am</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="slots[]"
                                                    id="slot4" value="4" onchange="checkAvailability()" <?php echo (in_array(4, explode(',', $booking['slot_or_session']))) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="slot4">12:30pm</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <!-- Row 2 -->
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="slots[]"
                                                    id="slot5" value="5" onchange="checkAvailability()" <?php echo (in_array(5, explode(',', $booking['slot_or_session']))) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="slot5">01:30pm</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="slots[]"
                                                    id="slot6" value="6" onchange="checkAvailability()" <?php echo (in_array(6, explode(',', $booking['slot_or_session']))) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="slot6">02:30pm</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="slots[]"
                                                    id="slot7" value="7" onchange="checkAvailability()" <?php echo (in_array(7, explode(',', $booking['slot_or_session']))) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="slot7">03:30pm</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="slots[]"
                                                    id="slot8" value="8" onchange="checkAvailability()" <?php echo (in_array(8, explode(',', $booking['slot_or_session']))) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="slot8">04:30pm</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <center>
                                    <div id="availability_message"></div>
                                </center>

                                <div class="mb-3">
                                    <label class="form-label">Purpose of Booking</label>
                                    <div class="btn-group w-100" role="group" aria-label="Purpose of Booking">
                                        <input type="radio" class="btn-check" name="purpose" id="purpose_event"
                                            value="event" <?php echo ($booking['purpose'] == 'event') ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary" for="purpose_event">Event</label>
                                        <input type="radio" class="btn-check" name="purpose" id="purpose_class"
                                            value="class" <?php echo ($booking['purpose'] == 'class') ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary" for="purpose_class">Class</label>
                                    </div>
                                </div>
                                <!-- Event Type Dropdown -->
                                <div class="mb-3" id="event-type-group"
                                    style="display: <?php echo ($formData['purpose'] == 'event') ? 'block' : 'none'; ?>;">
                                    <label for="event_type" class="form-label">Event Type</label>
                                    <select class="form-select" id="event_type" name="event_type">
                                        <option value="guest_lectures_seminars" <?php echo (isset($booking['event_type']) && $booking['event_type'] == 'guest_lectures_seminars') ? 'selected' : ''; ?>>
                                            Guest Lectures, Seminars
                                        </option>
                                        <option value="meetings_ceremonies" <?php echo (isset($booking['event_type']) && $booking['event_type'] == 'meetings_ceremonies') ? 'selected' : ''; ?>>
                                            Meetings, Ceremonies
                                        </option>
                                        <option value="workshops_training" <?php echo (isset($booking['event_type']) && $booking['event_type'] == 'workshops_training') ? 'selected' : ''; ?>>
                                            Workshops, Training
                                        </option>
                                        <option value="conferences_symposiums" <?php echo (isset($booking['event_type']) && $booking['event_type'] == 'conferences_symposiums') ? 'selected' : ''; ?>>
                                            Conferences, Symposiums
                                        </option>
                                        <option value="examinations_admissions_interviews" <?php echo (isset($booking['event_type']) && $booking['event_type'] == 'examinations_admissions_interviews') ? 'selected' : ''; ?>>
                                            Examinations, Admissions, Interviews
                                        </option>
                                    </select>
                                </div>



                                <div class="mb-3">
                                    <label for="purpose_name" class="form-label">Name of the Programme/Event</label>
                                    <textarea class="form-control" id="purpose_name" name="purpose_name" rows="3"
                                        required><?php echo htmlspecialchars($booking['purpose_name']); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="students_count" class="form-label">Number of Participants
                                    Expected</label> (Maximum - <?php echo $booking['capacity']; ?>)
                                    <input type="number" class="form-control" id="students_count"
                                        name="students_count" onchange="checkCapacity()" 
                                        value="<?php echo htmlspecialchars($booking['students_count']); ?>"
                                        min="5"
                                        required>
                                    <div id="alert" style="display: none; color: red;" role="alert">
                                        The number of participants exceeds the hall capacity -
                                        <?php echo $booking['capacity']; ?>.
                                    </div>
                                </div>


                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="organiser_name" class="form-label">Organiser's Name</label>
                                        <input type="text" class="form-control" id="organiser_name"
                                            name="organiser_name"
                                            value="<?php echo htmlspecialchars($booking['organiser_name']); ?>"
                                            required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="organiser_department" class="form-label">Organiser's
                                            Department</label>
                                        <input type="text" class="form-control" id="organiser_department"
                                            name="organiser_department" required
                                            oninput="suggestDepartments(this.value)"
                                            value="<?php echo htmlspecialchars($booking['organiser_department']); ?>"
                                            required>
                                        <div id="suggestions" class="suggestions-box"></div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="organiser_mobile" class="form-label">Organiser's Contact
                                            Number</label>
                                        <input type="text" class="form-control" id="organiser_mobile"
                                            name="organiser_mobile"
                                            value="<?php echo htmlspecialchars($booking['organiser_mobile']); ?>"
                                            required pattern="^(\+?\d{1,3}[-\s]?)?(\d{1,4}[-\s]?){2,3}\d{1,4}$"
                                            title="Please enter a valid mobile number.">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="organiser_email" class="form-label">Organiser's Email ID</label>
                                        <input type="email" class="form-control" id="organiser_email"
                                            name="organiser_email"
                                            value="<?php echo htmlspecialchars($booking['organiser_email']); ?>"
                                            required>
                                    </div>
                                </div>
                                <!-- <label>Status:</label>
            <div class="radio-group">
            <input type="radio" name="status" id="pending" value="pending" <?php echo ($booking['status'] == 'pending') ? 'checked' : ''; ?>>
                <label for="pending">Pending</label>
                <input type="radio" name="status" id="approved" value="approved" <?php echo ($booking['status'] == 'approved') ? 'checked' : ''; ?>>
                <label for="approved">Approved</label>

                <input type="radio" name="status" id="booked" value="booked" <?php echo ($booking['status'] == 'booked') ? 'checked' : ''; ?>>
                <label for="booked">Booked</label>
                
                <input type="radio" name="status" id="cancelled" value="cancelled" <?php echo ($booking['status'] == 'cancelled') ? 'checked' : ''; ?>>
                <label for="cancelled">Cancelled</label>
                <input type="radio" name="status" id="rejected" value="rejected" <?php echo ($booking['status'] == 'rejected') ? 'checked' : ''; ?>>
                <label for="rejected">Rejected</label>

            </div> -->

                                <div class="text-center mt-4">
                                    <a href="javascript:history.back()" style="padding:10px;"
                                        class="btn btn-primary">Back</a>

                                    <button type="submit" class="btn btn-success btn-lg">Update Booking</button>
                                </div>
                        </form>
                        </fieldset>
                        <?php include 'assets/footer.php' ?>

                        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
                        <script>
                            const hallCapacity = <?php echo $booking['capacity']; ?>;

                            function checkCapacity() {
                                const studentsCount = parseInt(document.getElementById("students_count").value, 10);

                                // Check if students count exceeds the capacity
                                if (studentsCount > hallCapacity) {
                                    // Show the alert
                                    document.getElementById("alert").style.display = "block";
                                } else {
                                    // Hide the alert if the input is valid
                                    document.getElementById("alert").style.display = "none";
                                }
                            }

                            function validateForm() {
                                const studentsCount = parseInt(document.getElementById("students_count").value, 10);
                                if (studentsCount > hallCapacity) {
                                    // Show the alert
                                    document.getElementById("alert").style.display = "block";
                                    return false;

                                } else {
                                    // Hide the alert if the input is valid
                                    document.getElementById("alert").style.display = "none";
                                }
                                return true;
                            }
                        </script>
                        <script>

                            function setEndDate() {
                                const startDate = document.getElementById('start_date').value;
                                document.getElementById('end_date').value = startDate;
                            }

                            // Show session options or slot options based on user selection
                            function showSessionOptions() {
                                document.getElementById('session_options').style.display = 'block';
                                document.getElementById('slot_options').style.display = 'none';
                            }

                            function showSlotOptions() {
                                document.getElementById('session_options').style.display = 'none';
                                document.getElementById('slot_options').style.display = 'block';
                            }
                            function checkAvailability() {
                                const startDate = document.getElementById('start_date').value;
                                const BookingId = document.querySelector('input[name="booking_id"]').value; // Fetch value correctly
                                const endDate = document.getElementById('end_date').value;
                                const hallId = document.querySelector('input[name="hall_id"]').value;
                                const bookingType = document.querySelector('input[name="booking_type"]:checked').value;

                                let sessionOrSlots = '';
                                if (bookingType === 'session') {
                                    sessionOrSlots = document.querySelector('input[name="session_choice"]:checked')?.value || '';
                                } else if (bookingType === 'slot') {
                                    const selectedSlots = Array.from(document.querySelectorAll('input[name="slots[]"]:checked'))
                                        .map(slot => slot.value);
                                    sessionOrSlots = selectedSlots.join(',');
                                }

                                if (BookingId && startDate && endDate && bookingType && sessionOrSlots) {
                                    const xhr = new XMLHttpRequest();
                                    xhr.open('POST', 'check_availability_edit.php', true);
                                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                                    xhr.onload = function () {
                                        if (xhr.status === 200) {
                                            const response = JSON.parse(xhr.responseText);
                                            const messageDiv = document.getElementById('availability_message');
                                            if (response.available) {
                                                messageDiv.innerHTML = `<span class="text-success">${response.message}</span>`;
                                            } else {
                                                messageDiv.innerHTML = `<span class="text-danger">${response.message}</span>`;
                                            }
                                        } else {
                                            console.error('Request failed with status:', xhr.status);
                                        }
                                    };

                                    xhr.send(`booking_id=${BookingId}&hall_id=${hallId}&start_date=${startDate}&end_date=${endDate}&booking_type=${bookingType}&session_or_slots=${sessionOrSlots}`);
                                }
                            }
                            window.onload = function () {
                                var bookingType = "<?php echo $booking['slot_or_session']; ?>";

                                // Convert bookingType to an array of slots
                                var slots = bookingType.split(',').map(function (slot) {
                                    return parseInt(slot.trim(), 10);
                                });

                                // Determine booking type and select corresponding options
                                if (slots.length === 4 && slots.every(slot => [1, 2, 3, 4].includes(slot))) {
                                    // If exactly 4 valid slots (1-4), select forenoon session
                                    document.querySelector('input[name="booking_type"][value="session"]').checked = true;
                                    showSessionOptions();
                                    document.querySelector('input[name="session_choice"][value="fn"]').checked = true; // Select Forenoon
                                } else if (slots.length === 4 && slots.every(slot => [5, 6, 7, 8].includes(slot))) {
                                    // If exactly 4 valid slots (5-8), select afternoon session
                                    document.querySelector('input[name="booking_type"][value="session"]').checked = true;
                                    showSessionOptions();
                                    document.querySelector('input[name="session_choice"][value="an"]').checked = true; // Select Afternoon
                                } else if (slots.length === 8 && slots.every(slot => slot >= 1 && slot <= 8)) {
                                    // If exactly 8 valid slots (1-8), select both sessions
                                    document.querySelector('input[name="booking_type"][value="session"]').checked = true;
                                    showSessionOptions();
                                    document.querySelector('input[name="session_choice"][value="both"]').checked = true; // Select Both
                                } else {
                                    // If any other valid slots, select slot options
                                    document.querySelector('input[name="booking_type"][value="slot"]').checked = true;
                                    showSlotOptions();
                                    slots.forEach(function (slot) {
                                        // Check selected slots
                                        if (slot >= 1 && slot <= 8) { // Ensure slot is valid
                                            document.querySelector(`input[name="slots[]"][value="${slot}"]`).checked = true; // Check selected slots
                                        }
                                    });
                                }
                            };

                            function suggestDepartments(query) {
                                if (query.length === 0) {
                                    document.getElementById("suggestions").style.display = "none";
                                    return;
                                }

                                fetch('get_departments.php?query=' + encodeURIComponent(query))
                                    .then(response => response.json())
                                    .then(data => {
                                        const suggestionsDiv = document.getElementById("suggestions");
                                        suggestionsDiv.innerHTML = '';

                                        if (data.length > 0) {
                                            const fragment = document.createDocumentFragment();
                                            data.forEach(department => {
                                                const suggestion = document.createElement('div');
                                                suggestion.textContent = department;
                                                suggestion.className = 'suggestion-item';
                                                suggestion.onclick = function () {
                                                    document.getElementById('organiser_department').value = department;
                                                    suggestionsDiv.style.display = 'none';
                                                };
                                                fragment.appendChild(suggestion);
                                            });
                                            suggestionsDiv.appendChild(fragment);
                                            suggestionsDiv.style.display = 'block';
                                        } else {
                                            suggestionsDiv.style.display = 'none';
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error fetching department suggestions:', error);
                                    });
                            }

                            document.addEventListener("DOMContentLoaded", function () {
                                const startDateInput = document.getElementById("start_date");
                                const endDateInput = document.getElementById("end_date");
                                const bookingTypeInputs = document.querySelectorAll('input[name="booking_type"]');
                                const sessionChoiceInputs = document.querySelectorAll('input[name="session_choice"]');
                                const slotInputs = document.querySelectorAll('input[name="slots[]"]');

                                startDateInput.addEventListener("change", checkAvailability);
                                endDateInput.addEventListener("change", checkAvailability);

                                bookingTypeInputs.forEach(input => {
                                    input.addEventListener("change", checkAvailability);
                                });

                                sessionChoiceInputs.forEach(input => {
                                    input.addEventListener("change", checkAvailability);
                                });

                                slotInputs.forEach(input => {
                                    input.addEventListener("change", checkAvailability);
                                });
                                // Disable past dates
                                const today = new Date().toISOString().split('T')[0];
                                startDateInput.setAttribute('min', today);
                                endDateInput.setAttribute('min', today);

                                startDateInput.addEventListener("input", function () {
                                    // Get the selected start date
                                    const startDate = new Date(startDateInput.value);
                                    // Set the end date to the start date if it's empty or less than start date
                                    if (!endDateInput.value || new Date(endDateInput.value) < startDate) {
                                        endDateInput.value = startDateInput.value;
                                    }
                                    // Update the min attribute of end date to ensure it can't be before the start date
                                    endDateInput.setAttribute('min', startDateInput.value);
                                });

                                endDateInput.addEventListener("input", function () {
                                    // If the selected end date is before the start date, reset it
                                    if (new Date(endDateInput.value) < new Date(startDateInput.value)) {
                                        endDateInput.value = startDateInput.value;
                                    }
                                });




                                const eventRadio = document.getElementById('purpose_event');
                                const classRadio = document.getElementById('purpose_class');
                                const eventTypeGroup = document.getElementById('event-type-group');

                                // Function to toggle the visibility of the Event Type group
                                function toggleEventType() {
                                    eventTypeGroup.style.display = eventRadio.checked ? 'block' : 'none';
                                }

                                // Check the saved state and display accordingly
                                toggleEventType();

                                // Add event listeners for changes
                                eventRadio.addEventListener('change', toggleEventType);
                                classRadio.addEventListener('change', toggleEventType);
                            });

                            document.querySelectorAll('input[name="slots[]"]').forEach((checkbox) => {
                                checkbox.addEventListener('change', () => {
                                    autoSelectSlots();
                                });
                            });

                            function autoSelectSlots() {
                                // Get all slot checkboxes
                                const slots = document.querySelectorAll('input[name="slots[]"]');
                                const selectedSlots = [];

                                // Find selected slots
                                slots.forEach((slot, index) => {
                                    if (slot.checked) {
                                        selectedSlots.push(index);
                                    }
                                });

                                if (selectedSlots.length >= 2) {
                                    // Automatically select intermediate slots
                                    const start = Math.min(...selectedSlots);
                                    const end = Math.max(...selectedSlots);

                                    for (let i = start; i <= end; i++) {
                                        slots[i].checked = true;
                                    }
                                }
                            }


                        </script>

</html>