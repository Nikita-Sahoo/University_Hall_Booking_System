<?php
include('smtp/PHPMailerAutoload.php');
include('assets/conn.php'); // Include database connection file

$user_id = $_GET['user_id'];
$hall_id = $_GET['hall_id'];
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];
$purpose = $_GET['purpose'];
$event_type = $_GET['event_type'];
$purpose_name = $_GET['purpose_name'];
$students_count = $_GET['students_count'];
$organiser_name = $_GET['organiser_name'];
$organiser_department = $_GET['organiser_department'];
$organiser_mobile = $_GET['organiser_mobile'];
$organiser_email = $_GET['organiser_email'];
$slot_or_session = $_GET['slot_or_session'];
$booking_date = $_GET['booking_date'];
$status = $_GET['status'];

// Fetch username from the database
$query = "SELECT username, email FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// Get the user's email and username
$username = $user_data['username'];
$user_email = $user_data['email'];
// Define slot start times
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

if (is_string($slot_or_session)) {
    $slot_or_session = explode(',', $slot_or_session);  // Assuming the slots are comma-separated
}

// Capitalize the first letter of the status
    if ($status == 'pending') {
        $status = 'Pending to approve';
    } elseif ($status == 'allow') {
        $status = 'Pending to forward';
    }
// Initialize session type and slot_time
$session_type = ""; 
$slot_time = ""; 

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
if ($start_date == $end_date) {
    $date_info = "<b>$start_date</b>";
} else {
    $date_info = "from <b>$start_date</b> to <b>$end_date</b>";
}

// Construct message based on session type or slots
if ($session_type) {
    $msg = "
        <h2>Booking Submitted</h2>
        <p>Dear $username,</p>
        <p>Your booking has been successfully submitted. Below are the details:</p>
        <p><b>Date:</b> $date_info</p>
        <p><b>Status:</b> " . ucfirst($status) . "</p>
        <p><b>Session:</b> $session_type</p>
        <p><b>Slot(s):</b> $slot_time</p>
        <p>Thank you for booking with us!</p>
        <br>
        <b>Regards,<br>
        HBS - Pondicherry University</b>
    ";
} else {
    $msg = "
        <h2>Booking Submitted</h2>
        <p>Dear $username,</p>
        <p>Your booking has been successfully submitted. Below are the details:</p>
        <p><b>Date:</b> $date_info</p>
        <p><b>Status:</b> " . ucfirst($status) . "</p>
        <p><b>Slot(s):</b> $slot_time</p>
        <p>Thank you for booking with us!</p>
        <br>
        <b>Regards,<br>
        HBS - Pondicherry University</b>
    ";
}

// Send the email using the SMTP mailer function
// echo smtp_mailer($organiser_email, 'Booking Status', $msg);
echo smtp_mailer('23352070@pondiuni.ac.in', 'Booking Status', $msg);
// echo smtp_mailer($user_email, 'Booking Successful', $msg);

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
    $mail->Password = "uviozkjvnurovgrx";
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
        window.location.href = 'view_modify_booking.php';
    </script>";

    
    }
}
?>
