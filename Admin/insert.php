<?php 
include '../assets/conn.php';


if(isset($_POST['submit']) && $_SERVER['REQUEST_METHOD'] == 'POST'){
    $school = $_POST['school_id'];
    $dept = $_POST['departmen_id'];
   

    $sql = 'INSERT INTO `school_department` (`school_name`,`department_name`) VALUES 
    (?,?)';
    // echo $sql;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }else{
        $stmt->bind_param("ss", $school, $dept);
        $stmt->execute();

    }

   
    if($conn->affected_rows>0){
         echo "<script>window.location.assign('add_dept.php')</script>";
        echo "Data inserted successfully";

      }
    else{
        echo "Data not inserted";
    }

}


else if (isset($_POST['submitroom']) && $_SERVER["REQUEST_METHOD"] == "POST") {

    $type_id = $_POST['hall_id'];
    $hallname = $_POST['room_name'];
    $capacity = $_POST['capacity'];

    $ac = isset($_POST['ac']) ? 'AC' : 'No';
    $projector = isset($_POST['preference']) && in_array('Projector', $_POST['preference']) ? 'Projector' : 'No';
    $wifi = isset($_POST['preference']) && in_array('WiFi', $_POST['preference']) ? 'WiFi' : 'No';
    $smartboard = isset($_POST['preference']) && in_array('Smartboard', $_POST['preference']) ? 'Smartboard' : 'No';
    $computer = isset($_POST['preference']) && in_array('Computer', $_POST['preference']) ? 'Computer' : 'No';
    $audio_system = isset($_POST['preference']) && in_array('AudioSystem', $_POST['preference']) ? 'AudioSystem' : 'No';
    $podium = isset($_POST['preference']) && in_array('Podium', $_POST['preference']) ? 'Podium' : 'No';
    $whiteboard = isset($_POST['preference']) && in_array('Whiteboard', $_POST['preference']) ? 'Whiteboard' : 'No';
    $blackboard = isset($_POST['preference']) && in_array('Blackboard', $_POST['preference']) ? 'Blackboard' : 'No';
    $lift = isset($_POST['preference']) && in_array('Lift', $_POST['preference']) ? 'Lift' : 'No';
    $ramp = isset($_POST['preference']) && in_array('Ramp', $_POST['preference']) ? 'Ramp' : 'No';

    $floor = $_POST['floor'];
    $zone = $_POST['zone'];
    $cost = $_POST['cost'];

    // Image upload handling
    $image = '';
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $image = 'image/' . basename($_FILES['file']['name']);
        move_uploaded_file($_FILES['file']['tmp_name'], $image);
    }

    if ($_POST['availability'] == "Yes") {
        $availability = "Yes";
    } else {
        $availability = $_POST['reason-input'];
    }

    // Initialize variables
    $school = null;
    $department = null;
    $section = null;

    // Handle 'belongs_to' logic
    if ($_POST['belongs_to'] == "Department") {
        $belongs_to = 'Department';
        $school = isset($_POST['school_name']) ? $_POST['school_name'] : null;
        $department = isset($_POST['department_name']) ? $_POST['department_name'] : null;
    } else if ($_POST['belongs_to'] == "School") {
        $belongs_to = 'School';
        $school = isset($_POST['school_name_school']) ? $_POST['school_name_school'] : null;
    } else if ($_POST['belongs_to'] == "Administration") {
        $belongs_to = "Administration";
        $section = isset($_POST['section']) ? $_POST['section'] : null;
    }

    // $incharge_name = $_POST['incharge_name'];
    // $designation = $_POST['designation'];
    // $incharge_email = $_POST['incharge_email'];
    // $phone = $_POST['phone'];

    // Insert query with validation
    $sql = "INSERT INTO hall_details (
                type_id, hall_name, capacity, wifi, ac, projector, computer, audio_system, 
                podium, ramp, smart_board, lift, white_board, blackboard, floor, zone, cost, 
                image, availability, status, belongs_to, department_id, school_id, section_id, 
                 from_date
            ) VALUES (
                '$type_id', '$hallname', '$capacity', '$wifi', '$ac', '$projector', '$computer', 
                '$audio_system', '$podium', '$ramp', '$smartboard', '$lift', '$whiteboard', '$blackboard', 
                '$floor', '$zone', '$cost', '$image', '$availability', 'Active', '$belongs_to', 
                " . ($department ? "'$department'" : "NULL") . ", 
                " . ($school ? "'$school'" : "NULL") . ", 
                " . ($section ? "'$section'" : "NULL") . ",  CURDATE())";

    // Execute the query
    if (mysqli_query($conn, $sql)) {
        echo "";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?><script>window.location.assign('view_modify_hall.php')</script>