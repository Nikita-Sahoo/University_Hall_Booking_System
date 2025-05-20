<?php
include('smtp/PHPMailerAutoload.php');
include('assets/conn.php'); // Include database connection file

// Check if form was submitted and required POST variables are set
if (isset($_GET['booking_id'], $_GET['new_status'])) {
    $booking_id = $_GET['booking_id'];
    $new_status = $_GET['new_status'];

    // Fetch booking details, including slot_or_session
    $query = "
    SELECT b.organiser_email, b.organiser_name, b.start_date, b.end_date, b.purpose, b.purpose_name, 
           b.status AS current_status, h.hall_name, d.department_name, u.username, u.email, b.slot_or_session
    FROM bookings b
    JOIN hall_details h ON b.hall_id = h.hall_id
    JOIN users u ON b.user_id = u.user_id
    JOIN departments d ON h.department_id = d.department_id
    WHERE b.booking_id = ?
    ";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die('Error preparing query: ' . $conn->error); // Display specific error message from MySQL
    }
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->bind_result($organiser_email, $organiser_name, $start_date, $end_date, $purpose, $purpose_name, $current_status, $hall_name, $department, $username, $user_email, $slot_or_session);
    $stmt->fetch();
    $stmt->close();

    // Update booking status in the database
    $update_query = "UPDATE bookings SET status = ? WHERE booking_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $new_status, $booking_id);
    $update_stmt->execute();
    $update_stmt->close();

    // Process the slot_or_session (explode if it's comma-separated, else use as-is)
    if (is_string($slot_or_session)) {
        $slot_or_session = explode(',', $slot_or_session);  // Assuming the slots are comma-separated
    }

    // Capitalize the first letter of the status
    $status = ucfirst($new_status);

    // Initialize session type and slot_time
    $session_type = ""; 
    $slot_time = ""; 

    $slot_start_times = [
        1 => '9:30 AM', 
        2 => '10:30 AM', 
        3 => '11:30 AM', 
        4 => '12:30 PM', 
        5 => '1:30 PM', 
        6 => '2:30 PM', 
        7 => '3:30 PM', 
        8 => '4:30 PM'
    ];

    // Check session type only if all the necessary slots are selected
    if (count($slot_or_session) == 8) {
        // Full Day session (all slots from 1 to 8 selected)
        $session_type = "Full Day"; 
        $slot_time = "9:30 AM to 4:30 PM";
    } elseif (count(array_intersect($slot_or_session, [1, 2, 3, 4])) == 4) {
        // Forenoon session (all slots from 1 to 4 selected)
        $session_type = "Forenoon"; 
        $slot_time = "9:30 AM to 12:30 PM";
    } elseif (count(array_intersect($slot_or_session, [5, 6, 7, 8])) == 4) {
        // Afternoon session (all slots from 5 to 8 selected)
        $session_type = "Afternoon"; 
        $slot_time = "1:30 PM to 4:30 PM";
    } else {
        // Show the selected slots without grouping into Forenoon, Afternoon, or Full Day
        $session_type = ""; 
        $slot_time = implode(", ", array_map(function($slot) use ($slot_start_times) {
            // Check if the slot exists in the $slot_start_times array
            return isset($slot_start_times[$slot]) ? $slot_start_times[$slot] : "Invalid Slot";
        }, $slot_or_session));
    }

    // Date information
    if ($start_date == $end_date) {
        $date_info = "<b>$start_date</b>";
    } else {
        $date_info = "from <b>$start_date</b> to <b>$end_date</b>";
    }

    // Set the status color based on new status
    $status_color = 'black';
    if ($new_status == 'approved') {
        $status_color = 'green';
    } elseif ($new_status == 'rejected') {
        $status_color = 'red';
    }

    // Construct email body message
    if ($session_type) {
        $msg = "
            <h2 style='color:$status_color;'>Booking Status ". ucfirst($new_status)."</h2>
            <p>Dear $organiser_name,</p>
            <p>Your booking for the hall <b>$hall_name</b> in <b>$department</b> has been updated. Below are the details:</p>
            <p><b>Hall Name:</b> $hall_name</p>
            <p><b>Department:</b> $department</p>
            <p><b>Date:</b> $date_info</p>
            <p><b>Session:</b> $session_type</p>
            <p><b>Slot(s):</b> $slot_time</p>
            <p><b>Status:</b> <span style='color:$status_color;'>" . ucfirst($new_status) . "</span></p>
            <p>Thank you for booking with us!</p>
            <br>
            <b>Regards,<br>
            HBS - Pondicherry University</b>
        ";
    } else {
        $msg = "
            <h2 style='color:$status_color;'>Booking Status ". ucfirst($new_status)."</h2>
            <p>Dear $organiser_name,</p>
            <p>Your booking for the hall <b>$hall_name</b> in <b>$department</b> has been updated. Below are the details:</p>
            <p><b>Hall Name:</b> $hall_name</p>
            <p><b>Department:</b> $department</p>
            <p><b>Date:</b> $date_info</p>
            <p><b>Slot(s):</b> $slot_time</p>
            <p><b>Status:</b> <span style='color:$status_color;'>" . ucfirst($new_status) . "</span></p>
            <p>Thank you for booking with us!</p>
            <br>
            <b>Regards,<br>
            HBS - Pondicherry University</b>
        ";
    }

    // Send email to the organiser
    echo smtp_mailer("23352070@pondiuni.ac.in", 'Booking Status Update', $msg);
 //   echo smtp_mailer($user_email, 'Booking Status Update', $msg);
}

// SMTP mailer function
function smtp_mailer($to, $subject, $msg)
{
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'tls';
    $mail->Host = "smtp.gmail.com";
    $mail->Port = 587;
    $mail->IsHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Username = "23352070@pondiuni.ac.in";
    $mail->Password = "uviozkjvnurovgrx"; // Your email password
    $mail->SetFrom("23352070@pondiuni.ac.in", "HBS - Pondicherry University");
    $mail->Subject = $subject;
    $mail->Body = $msg;
    $mail->AddAddress($to);
    $mail->SMTPOptions = array('ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => false
    ));
    
    if (!$mail->Send()) {
        echo $mail->ErrorInfo;
    } else {
        echo "<script>
            window.location.href = 'no_conflict_bookings.php';
        </script>";
    }
}
?>
