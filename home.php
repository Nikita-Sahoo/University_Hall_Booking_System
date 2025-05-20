<?php
include 'assets/conn.php';
include 'assets/header.php';

$userRole = $_SESSION['role']; // Can be Admin, HOD, or Dean
$userId = $_SESSION['user_id'];
if ($userRole == 'admin') {

// Queries to fetch the counts of each hall type
$seminarHalls = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE type_id = 1");
$auditoriums = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE type_id = 2");
$lectureHalls = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE type_id = 3");
$conferenceHalls = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE type_id = 4");

// Total hall count
$totalHalls = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details");

$users = mysqli_query($conn, "SELECT COUNT(*) AS count FROM users");
$schools = mysqli_query($conn, "SELECT COUNT(*) AS count FROM schools");
$departments = mysqli_query($conn, "SELECT COUNT(*) AS count FROM departments");
$sections = mysqli_query($conn, "SELECT COUNT(*) AS count FROM section");

$seminarHallCount = mysqli_fetch_assoc($seminarHalls)['count'];
$auditoriumCount = mysqli_fetch_assoc($auditoriums)['count'];
$lectureHallCount = mysqli_fetch_assoc($lectureHalls)['count'];
$conferenceHallCount = mysqli_fetch_assoc($conferenceHalls)['count'];
$totalHallCount = mysqli_fetch_assoc($totalHalls)['count'];

$userCount = mysqli_fetch_assoc($users)['count'];
$schoolCount = mysqli_fetch_assoc($schools)['count'];
$departmentCount = mysqli_fetch_assoc($departments)['count'];
$sectionCount = mysqli_fetch_assoc($sections)['count'];
}
elseif ($userRole == 'hod') {
    // Fetch the department ID for the HOD
    $hodDepartmentQuery = mysqli_query($conn, "SELECT department_id FROM users WHERE user_id = '$userId'");
    $hodDepartment = mysqli_fetch_assoc($hodDepartmentQuery)['department_id'];

    // Fetch halls under HOD's department (this can be seminar halls, lecture halls, etc.)
    $departmentHallsQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE department_id = '$hodDepartment'");
    $departmentHallCount = mysqli_fetch_assoc($departmentHallsQuery)['count'];

    // Fetch bookings made by the HOD (either within or outside the department)
    $hodBookingsQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE user_id = '$userId'");
    $hodBookingsCount = mysqli_fetch_assoc($hodBookingsQuery)['count'];

    // Count total seminar halls in HOD's department
    $seminarHallQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE department_id = '$hodDepartment' AND type_id = 1");
    $seminarHallCount = mysqli_fetch_assoc($seminarHallQuery)['count'];

    // Count total lecture halls in HOD's department
    $lectureHallQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE department_id = '$hodDepartment' AND type_id = 3");
    $lectureHallCount = mysqli_fetch_assoc($lectureHallQuery)['count'];

    // Fetch bookings for halls under HOD's department
    $departmentHallBookingsQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE department_id = '$hodDepartment')");
    $departmentHallBookingsCount = mysqli_fetch_assoc($departmentHallBookingsQuery)['count'];

    // Fetch Pending, Approved, and Rejected Bookings for HOD's Department
    $pendingBookingsQuery = mysqli_query($conn, "SELECT * FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE department_id = '$hodDepartment') AND status = 'pending'");
    $pendingBookingsCount = mysqli_num_rows($pendingBookingsQuery);

    $approvedBookingsQuery = mysqli_query($conn, "SELECT * FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE department_id = '$hodDepartment') AND status = 'approved'");
    $approvedBookingsCount = mysqli_num_rows($approvedBookingsQuery);

    $rejectedBookingsQuery = mysqli_query($conn, "SELECT * FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE department_id = '$hodDepartment') AND status = 'rejected'");
    $rejectedBookingsCount = mysqli_num_rows($rejectedBookingsQuery);

    // Fetch bookings made for halls under HOD's control (this assumes the HOD is assigned specific halls)
    // If the HOD controls specific halls, fetch bookings only for those halls
    $controlHallBookingsQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE user_id = '$userId')");
    $controlHallBookingsCount = mysqli_fetch_assoc($controlHallBookingsQuery)['count'];

    // Fetch approved bookings for halls under HOD's control
    $controlHallApprovedQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE user_id = '$userId') AND status = 'approved'");
    $controlHallApprovedCount = mysqli_fetch_assoc($controlHallApprovedQuery)['count'];

    // Fetch rejected bookings for halls under HOD's control
    $controlHallRejectedQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE user_id = '$userId') AND status = 'rejected'");
    $controlHallRejectedCount = mysqli_fetch_assoc($controlHallRejectedQuery)['count'];
}



elseif ($userRole == 'dean') {
    // Fetch the department ID for the HOD
    $deanDepartmentQuery = mysqli_query($conn, "SELECT school_id FROM users WHERE user_id = '$userId'");
    $deanDepartment = mysqli_fetch_assoc($deanDepartmentQuery)['school_id'];

    // Fetch halls under HOD's department (this can be seminar halls, lecture halls, etc.)
    $departmentHallsQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE school_id = '$deanDepartment'");
    $departmentHallCount = mysqli_fetch_assoc($departmentHallsQuery)['count'];

    // Fetch bookings made by the HOD (either within or outside the department)
    $hodBookingsQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE user_id = '$userId'");
    $hodBookingsCount = mysqli_fetch_assoc($hodBookingsQuery)['count'];

    // Count total seminar halls in HOD's department
    $seminarHallQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE school_id = '$deanDepartment' AND type_id = 1");
    $seminarHallCount = mysqli_fetch_assoc($seminarHallQuery)['count'];

    // Count total lecture halls in HOD's department
    $lectureHallQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE school_id = '$deanDepartment' AND type_id = 3");
    $lectureHallCount = mysqli_fetch_assoc($lectureHallQuery)['count'];

    // Fetch bookings for halls under HOD's department
    $departmentHallBookingsQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE school_id = '$deanDepartment')");
    $departmentHallBookingsCount = mysqli_fetch_assoc($departmentHallBookingsQuery)['count'];

    // Fetch Pending, Approved, and Rejected Bookings for HOD's Department
    $controlHallBookingsQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE user_id = '$userId')");
    $controlHallBookingsCount = mysqli_fetch_assoc($controlHallBookingsQuery)['count'];

    // Fetch approved bookings for halls under HOD's control
    $controlHallApprovedQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE user_id = '$userId') AND status = 'approved'");
    $controlHallApprovedCount = mysqli_fetch_assoc($controlHallApprovedQuery)['count'];

    // Fetch rejected bookings for halls under HOD's control
    $controlHallRejectedQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE user_id = '$userId') AND status = 'rejected'");
    $controlHallRejectedCount = mysqli_fetch_assoc($controlHallRejectedQuery)['count'];
}
$sql_count = "
SELECT COUNT(*) AS pending_count 
FROM bookings b 
WHERE b.status = 'pending' 
  AND b.end_date >= CURDATE()";

if ($user_role == 'hod') {
    // Filter by department for HOD role
    $sql_count .= " AND b.hall_id IN (SELECT hall_id FROM hall_details WHERE department_id = ?)";
    $stmt = $conn->prepare($sql_count);
    $stmt->bind_param("i", $department_id); // Assuming $department_id is passed
} elseif ($user_role == 'dean') {
    // Filter by school_id for Dean role
    $sql_count .= " AND b.hall_id IN (SELECT hall_id FROM hall_details WHERE department_id IN (SELECT department_id FROM departments WHERE school_id = ?))";
    $stmt = $conn->prepare($sql_count);
    $stmt->bind_param("i", $school_id); // Assuming $school_id is passed
} else {
    // No additional filters for other roles
    $stmt = $conn->prepare($sql_count);
}

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$pending_count = $row['pending_count'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/design.css" />
    <style>
        .card {
            transition: transform 0.3s ease-in-out;
            height: 200px;
        }

        .card:hover {
            transform: scale(1.05);
        }
        .section-card,
.school-card,
.department-card {
            transition: transform 0.3s ease-in-out;
        }

        .section-card:hover {
            transform: scale(1.05);
        }
.school-card:hover {
            transform: scale(1.05);
        }
.department-card:hover {
            transform: scale(1.05);
        }
        .fade-in {
            opacity: 0;
            animation: fadeIn 1s forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

.card-body {
    text-align: center;
}

.card-title {
    font-size: 1.5rem;
    margin-bottom: 20px;
}

.card-count {
    font-size: 2rem;
    font-weight: bold;
    color: #007bff;
}

/* Layout Styling */
.container {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
}

.left-column,
.middle-column,
.right-column {
    flex: 1;
}

.middle-column .card{
    width: 150px;
    border: 2px solid #007bff;

}
/* Cards for Sections, Schools, Departments */
.left-column .section-card,
.left-column .school-card,
.left-column .department-card {
    margin-bottom: 15px;
}

.section-card,
.school-card,
.department-card {
    width: 300px;
    height: 150px;
    background-color: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    color: #333;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    border: 2px solid #007bff;
}

/* Specific background colors for these cards */
.section-card {
    background-color: #e1f5fe;
}

.school-card {
    background-color: #f3e5f5;
}

.department-card {
    background-color: #fce4ec;
}

/* Circle Layout for Hall Types */
.hall-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    justify-content: center;
}

/* Circle Hall Cards */
.hall-card {
    background-color: #a2d5f2; /* Default Light Blue */
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #333;
    font-size: 1.1rem; /* Slightly smaller font */
    font-weight: bold;
    padding: 15px; /* Reduced padding */
    transition: transform 0.3s ease-in-out;
    width: 200px; /* Smaller width */
    height: 200px; /* Smaller height */
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    border: 2px solid #007bff;

}

.hall-card:hover {
    transform: scale(1.1); /* Slight increase on hover */
    cursor: pointer;
}

/* Specific colors for each hall type */
.hall-container .hall-card:nth-child(1) {
    background-color: #ffccbc; /* Light Coral */
}

.hall-container .hall-card:nth-child(2) {
    background-color: #d1c4e9; /* Light Purple */
}

.hall-container .hall-card:nth-child(3) {
    background-color: #c8e6c9; /* Light Green */
}

.hall-container .hall-card:nth-child(4) {
    background-color: #ffecb3; /* Light Yellow */
}


.card-notify {
    position: relative;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: linear-gradient(45deg, transparent, transparent 40%, #ff0000);
    display: flex;
    justify-content: center;
    align-items: center;
    animation: rotate 1.5s linear infinite;
    box-shadow: 0 0 15px rgba(255, 0, 0, 0.5); /* Red glow */
    z-index: 0;
}

.card-notify:before {
    content: "";
    position: absolute;
    inset: 10px; /* Inner circle spacing */
    background: #fff; /* Background color for inner area */
    border-radius: inherit;
}

.card-notify p {
    font-size: 1.25rem; /* Font size for count */
    font-weight: bold;
    color: #ff0000; /* Red text for the count */
    margin: 0;
}
.card-count{
    z-index:10;
}
/* Rotating animation */
/* @keyframes rotate {
    100% {
        transform: rotate(360deg);
    }
} */

.card-title {
    text-align: center;
    font-size: 1.5rem;
    color: #ff0000; /* Red for the title text */
    margin-bottom: 10px;
}


/* Form Box */
.semester-form {
    background: white;
    margin: 25px;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
    width: 350px;
    text-align: center;
}

/* Heading */
.semester-form h3 {
    margin-bottom: 20px;
    color: #333;
    font-size: 22px;
    font-weight: bold;
}

/* Form Inputs */
.semester-form label {
    display: block;
    font-size: 14px;
    color: #666;
    margin: 10px 0 5px;
    text-align: left;
}

.semester-form input {
    width: 100%;
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 6px;
    outline: none;
    transition: 0.3s ease;
}

.semester-form input:focus {
    border-color: #4e54c8;
    box-shadow: 0 0 5px rgba(78, 84, 200, 0.5);
}

/* Submit Button */
.semester-form button {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    font-weight: bold;
    color: white;
    background: #4e54c8;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: 0.3s ease;
    margin-top: 15px;
}

.semester-form button:hover {
    background: #3c40c6;
}#main {
    margin:0 20%;
}
</style>
</head>

<body>
    <div id="main">
        <div class="container mt-5">
        <?php if ($userRole == 'admin'): ?>
            <!-- Left Column: Sections, Schools, and Departments -->
            <div class="left-column">
                <div class="section-card">
                    <div class="card-body">
                        <h5 class="card-title">Sections</h5>
                        <p class="card-count"><?= $sectionCount ?></p>
                    </div>
                </div>

                <div class="school-card">
                    <div class="card-body">
                        <h5 class="card-title">Schools</h5>
                        <p class="card-count"><?= $schoolCount ?></p>
                    </div>
                </div>

                <div class="department-card">
                    <div class="card-body">
                        <h5 class="card-title">Departments</h5>
                        <p class="card-count"><?= $departmentCount ?></p>
                    </div>
                </div>
            </div>
            <div class="right-column">
                <div class="hall-container">
                    <!-- Seminar Halls -->
                    <div class="hall-card">
                        <div class="card-body">
                            <h5 class="card-title">Seminar Halls</h5>
                            <p class="card-count"><?= $seminarHallCount ?></p>
                        </div>
                    </div>

                    <!-- Auditoriums -->
                    <div class="hall-card">
                        <div class="card-body">
                            <h5 class="card-title">Auditoriums</h5>
                            <p class="card-count"><?= $auditoriumCount ?></p>
                        </div>
                    </div>

                    <!-- Lecture Halls -->
                    <div class="hall-card">
                        <div class="card-body">
                            <h5 class="card-title">Lecture Halls</h5>
                            <p class="card-count"><?= $lectureHallCount ?></p>
                        </div>
                    </div>

                    <!-- Conference Halls -->
                    <div class="hall-card">
                        <div class="card-body">
                            <h5 class="card-title">Conference Halls</h5>
                            <p class="card-count"><?= $conferenceHallCount ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Middle Column: Users and Total Halls -->
            <!-- <div class="middle-column"> -->
                <!-- Users Card -->
                    <!-- <div class="card-body">
                        <h5 class="card-title">Users</h5>
                        <p class="card-count"><?= $userCount ?></p>
                    </div>
                </div> -->

                <!-- Total Halls Card -->
                <!-- <div class="card shadow" style="margin-top: 20px;">
                    <div class="card-body">
                        <h5 class="card-title">Total Halls</h5>
                        <p class="card-count"><?= $totalHallCount ?></p>
                    </div>
                </div> -->

                <?php
// Database Connection


// Fetch the last semester entry
$query1 = "SELECT * FROM semesters ORDER BY semester_id DESC LIMIT 1";
$result1 = mysqli_query($conn, $query1);
$latestSemester = mysqli_fetch_assoc($result1);
?>

<div class="semester-form-container">
    <div class="semester-form">
        <h3>Semester Time Period</h3>
        <form action="add_semester.php" method="POST">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?= $latestSemester['start_date'] ?? '' ?>" required onclick="this.showPicker()">

            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?= $latestSemester['end_date'] ?? '' ?>" required onclick="this.showPicker()">

            <button type="submit">Modify</button>
        </form>
    </div>
</div>



            </div>

            <!-- Right Column: Circle Layout for Hall Types -->
            

 
            <?php endif;?>
            <?php if ($userRole == 'hod'): ?>
<div class="row">

    <!-- Column 1: Halls under HOD's Control -->
    <div class="col-md-4">
        <div class="card shadow mb-3" style="background-color: #f0f8ff;">
            <div class="card-body">
                <h5 class="card-title text-primary">Halls under Your Control</h5>
                <p class="card-count text-dark"><?= $departmentHallCount ?></p>
            </div>
        </div>
        <div class="card shadow mb-3" style="background-color: #fff;">
            <div class="card-body">
                <h5 class="card-title text-warning">Seminar Hall Count</h5>
                <p class="card-count text-dark"><?= $seminarHallCount ?></p>
            </div>
        </div>
        <div class="card shadow mb-3" style="background-color: #fff;">
            <div class="card-body">
                <h5 class="card-title text-danger">Lecture Hall Count</h5>
                <p class="card-count text-dark"><?= $lectureHallCount ?></p>
            </div>
        </div>
    </div>

    <!-- Column 2: Bookings Made by the User -->
    <div class="col-md-4">
        <div class="card shadow mb-3" style="background-color: #f0f8ff;">
            <div class="card-body">
            <h5 class="card-title text-primary">Bookings Made By You</h5>
            <p class="card-count text-dark"><?= $hodBookingsCount ?></p>
            </div>
        </div>
        <div class="card shadow mb-3" style="background-color: #d4edda;">
            <div class="card-body">
            <h5 class="card-title text-primary">Approved :
            <span class="card-count"><?= $controlHallApprovedCount ?> </span></h5>
            </div>
        </div>
        <div class="card shadow mb-3" style="background-color: #f8d7da;">
            <div class="card-body">
            <h5 class="card-title text-primary">Rejected : 
            <span class="card-count"><?= $controlHallRejectedCount ?> </span></h5>
            </div>
        </div>
    </div>
<!-- Column 3: Bookings under HOD's Control -->
<div class="col-md-4 d-flex flex-column justify-content-center align-items-center">
    <h5 class="card-title">Pending Approvals</h5> <!-- Title above the circle -->
    <div class="card-notify shadow text-center">
        <p class="card-count"><?= $pending_count ?> Pending</p>
    </div>
</div>


</div>
<?php endif; ?>


<?php if ($userRole == 'dean'): ?>
<div class="row">
<div class="col-md-2"></div>

    <!-- Column 1: Halls under HOD's Control -->
    <div class="col-md-4">
        <div class="card shadow mb-3" style="background-color: #f0f8ff;">
            <div class="card-body">
                <h5 class="card-title text-primary">Halls under Your Control</h5>
                <p class="card-count text-dark"><?= $departmentHallCount ?></p>
            </div>
        </div>
        <div class="card shadow mb-3" style="background-color: #fff;">
            <div class="card-body">
                <h5 class="card-title text-warning">Seminar Hall Count</h5>
                <p class="card-count text-dark"><?= $seminarHallCount ?></p>
            </div>
        </div>
        <div class="card shadow mb-3" style="background-color: #fff;">
            <div class="card-body">
                <h5 class="card-title text-danger">Lecture Hall Count</h5>
                <p class="card-count text-dark"><?= $lectureHallCount ?></p>
            </div>
        </div>
    </div>

    <!-- Column 2: Bookings Made by the User -->
    <div class="col-md-6">
        <div class="card shadow mb-3" style="background-color: #f0f8ff;">
            <div class="card-body">
            <h5 class="card-title text-primary">Bookings Made By You</h5>
            <p class="card-count text-dark"><?= $hodBookingsCount ?></p>
            </div>
        </div>
        <div class="card shadow mb-3" style="background-color: #d4edda;">
            <div class="card-body">
            <h5 class="card-title text-primary">Approved :
            <span class="card-count"><?= $controlHallApprovedCount ?> </span></h5>
            </div>
        </div>
        <div class="card shadow mb-3" style="background-color: #f8d7da;">
            <div class="card-body">
            <h5 class="card-title text-primary">Rejected : 
                <span class="card-count"><?= $controlHallRejectedCount ?> </span></h5>
            </div>
        </div>
    </div>



</div>
<?php endif; ?>




            </div>
    </div>
</body>

    <script>
        // Optional: Add jQuery animations or interactions here if necessary
        $(document).ready(function () {
            $(".fade-in").each(function (index) {
                $(this).delay(index * 200).fadeIn(1000);
            });
        });
        // Get all the dropdown buttons
        var dropdownBtns = document.querySelectorAll(".dropdown-btn");

        // Loop through the buttons to add event listeners
        dropdownBtns.forEach(function(btn) {
            btn.addEventListener("click", function() {
                // Toggle between showing and hiding the active dropdown container
                this.classList.toggle("collapsed");
                var dropdownContainer = this.nextElementSibling;
                if (dropdownContainer.style.display === "block") {
                    dropdownContainer.style.display = "none";
                } else {
                    dropdownContainer.style.display = "block";
                }
            });
        });
    </script>
</body>

</html>
