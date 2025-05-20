<?php
include 'conn.php';
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch user details from session
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$department_id = $_SESSION['department_id'];
$school_id = $_SESSION['school_id'];
$user_role = $_SESSION['role'];

$school_name1 = '';
$department_name1 = '';

// Fetch school name for Dean
if ($user_role == 'dean') {
    $sql_school = "SELECT school_name FROM schools WHERE school_id = ?";
    $stmt = $conn->prepare($sql_school);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result_school = $stmt->get_result();
    if ($row = $result_school->fetch_assoc()) {
        $school_name1 = $row['school_name'];
    }
}

// Fetch department name for HOD
if ($user_role == 'hod') {
    $sql_department = "SELECT department_name FROM departments WHERE department_id = ?";
    $stmt = $conn->prepare($sql_department);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result_department = $stmt->get_result();
    if ($row = $result_department->fetch_assoc()) {
        $department_name1 = $row['department_name'];
    }
}
// First SQL query: Count pending bookings
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

// Get the department ID of the logged-in user
$query = "SELECT department_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id); // Assuming $user_id is passed
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_department_id = $user['department_id'] ?? null; // Get department_id of the logged-in user

// Second SQL query: Count allowed bookings for specific department
$sql_count = "
SELECT COUNT(*) AS pending_count1 
FROM bookings b 
WHERE b.status = 'allow' 
  AND b.start_date >= CURDATE()  -- Add the date condition here
  AND EXISTS (SELECT 1 FROM users u WHERE u.user_id = b.user_id AND u.department_id = ?)";

if ($user_role === 'hod' && $user_department_id !== null) {
    // Filter HOD role bookings where the hall is NOT in their department
    $sql_count .= " AND b.hall_id NOT IN (SELECT hall_id FROM hall_details WHERE department_id = ?)";
}
$stmt = $conn->prepare($sql_count);

if ($user_role === 'hod') {
    $stmt->bind_param("ii", $user_department_id, $user_department_id);
} else {
    $stmt->bind_param("i", $user_department_id);
}

$stmt->execute();
$result1 = $stmt->get_result();
$row = $result1->fetch_assoc();
$pending_count1 = $row['pending_count1'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>University Hall Booking System</title>
    <style>
        body {
            font-family: "Lato", sans-serif;
            margin: 0;
            padding-top: 0;
        }

        .navbar {
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 10;
            background-color: #007bff;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .navbar img {
            height: 40px;
            margin-left: 10px;
        }

        .navbar h3 {
            color: white;
            font-size: 20px;
            margin: 0;
            text-align: center;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .navbar-user-info {
            color: white;
            margin-right: 10px;
        }

        .sidenav {
            height: 100%;
            width: 250px;
            position: fixed;
            z-index: 1;
            top: 0;
            left: 0;
            background-color: #283745;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 100px;
        }

        .sidenav a,
        .sidenav button {
            padding: 15px 20px;
            text-decoration: none;
            font-size: 18px;
            color: #ecf0f1;
            display: flex;
            align-items: center;
            border: none;
            background: none;
            outline: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .sidenav a:hover,
        .sidenav button:hover {
            background-color: #34495e;
        }

        .dropdown-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dropdown-btn i {
            transition: transform 0.3s ease;
        }

        .dropdown-btn.collapsed i {
            transform: rotate(90deg);
        }

        .sidenav a.active,
        .sidenav button.active {
            background-color: #3786dc;
            color: white;
        }

        .dropdown-btn.active {
            background-color: steelblue;
            color: white;
        }

        .dropdown-container {
            display: none;
            background-color: #34495e;
            /* Submenu background */
            padding: 10px 0;
        }

        .dropdown-container a {
            display: block;
            padding: 10px 30px;
            text-decoration: none;
            color: #ecf0f1;
            transition: background-color 0.3s ease;
        }

        .dropdown-container a:hover {
            background-color: #3d566e;
        }

        .dropdown-container a.active {
            background-color: #007bff;
        }

        .dropdown-container {
            display: none;
            background-color: rgb(61, 61, 61);
            padding: 10px;
        }

        .dropdown-container.active {
            display: block;
        }

        .dropdown-container a {
            display: block;
            padding: 8px 15px;
            text-decoration: none;
        }

        .dropdown-container a.active {
            background-color: rgb(102, 102, 102);
        }

        .dropdown-container a:hover {
            background-color: rgb(97, 97, 97);
        }

        .dropdown-btn.collapsed i {
            transform: rotate(90deg);
        }

        .toggle-btn {
            position: fixed;
            top: 80px;
            left: 200px;
            background-color: #2c3e50;
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 20px;
            cursor: pointer;
            border-radius: 0 5px 5px 0;
            z-index: 2;
            transition: 0.5s;
        }

        .toggle-btn.open {
            left: 0px;
        }
    </style>
</head>

<body>
    <?php
    $currentPage = basename($_SERVER['PHP_SELF']);
    ?>

    <nav class="navbar">
        <img src="../image/logo/PU_Logo_Full.png" alt="Pondicherry University Logo">
        <h3>UNIVERSITY HALL BOOKING SYSTEM</h3>
        <div class="navbar-user-info">
            <?php if (isset($username)): ?>
                <?php if ($user_role == 'dean'): ?>
                    <?= htmlspecialchars($school_name1); ?>
                <?php elseif ($user_role == 'hod'): ?>
                    <?= htmlspecialchars($department_name1); ?>
                <?php endif; ?>
                <br>
                Hi, <?= htmlspecialchars($username); ?> | <a style="color: white;" href="logout.php">Logout</a>
            <?php else: ?>
                <a href="../index.php" style="color: white;">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div id="mySidenav" class="sidenav">
        <a href="../home.php" class="<?= ($currentPage == '../home.php') ? 'active' : '' ?>">
            <i style="margin: 0 5px 5px 0;" class="fa fa-home"></i>Dashboard
        </a>
        <button class="dropdown-btn">
            Hall Details
            <i style="margin-left:20px;" class="fa fa-chevron-right"></i>
        </button>
        <div class="dropdown-container">
            <?php if ($user_role == 'admin'): ?>
                <a href="add_hall.php" class="<?= ($currentPage == 'add_hall.php') ? 'active' : '' ?>">Add Hall</a>
            <?php endif; ?>
            <a href="view_modify_hall.php" class="<?= ($currentPage == 'view_modify_hall.php') ? 'active' : '' ?>">View/Modify Hall</a>
            <?php if ($user_role == 'admin'): ?>
            <a href="view_modify_achived_hall.php" class="<?= ($currentPage == 'view_modify_achived_hall.php') ? 'active' : '' ?>">Archive Halls</a>
            <?php endif; ?>
        </div>
        <button class="dropdown-btn">
            School/Departments
            <i style="margin-left:20px;" class="fa fa-chevron-right"></i>
        </button>
        <div class="dropdown-container">
            <?php if ($user_role == 'admin'): ?>
                <a href="add_school_dept.php" class="<?= ($currentPage == 'add_school_dept.php') ? 'active' : '' ?>">Add School/Department</a>
            <?php endif; ?>
            <a href="view_school.php" class="<?= ($currentPage == 'view_school.php') ? 'active' : '' ?>">View/Modify School</a>
        </div>
        <button class="dropdown-btn">
            Employee Details
            <i style="margin-left:20px;" class="fa fa-chevron-right"></i>
        </button>
        <div class="dropdown-container">
            <?php if ($user_role == 'admin'): ?>
                <a href="add_employees.php" class="<?= ($currentPage == 'add_employees.php') ? 'active' : '' ?>">Add Employee</a>
            <?php endif; ?>

            <a href="view_employees.php" class="<?= ($currentPage == 'view_employees.php') ? 'active' : '' ?>">View Employees</a>
        </div>

        <button class="dropdown-btn">
            Book the hall
            <i style="margin-left:20px;" class="fa fa-chevron-right"></i>
        </button>
        <div class="dropdown-container">
            <a href="../find_halls.php" class="<?= ($currentPage == 'find_halls.php') ? 'active' : '' ?>">Browse & Book Hall</a>
            <?php if ($user_role == 'hod'): ?>
                <a href="../sem_booking.php" class="<?= ($currentPage == 'sem_booking.php') ? 'active' : '' ?>">Semester Booking</a>
            <?php endif; ?>
            <a href="../view_modify_booking.php" class="<?= ($currentPage == 'view_modify_booking.php') ? 'active' : '' ?>">My Bookings</a>
        </div>
        <?php if ($user_role == 'hod'): ?>
            <button class="dropdown-btn">
                <p style="margin-bottom:0px;" class="me-2">Approve Bookings <?= ($pending_count > 0) ? "<span class='badge bg-light text-dark me-2'>$pending_count</span>" : "" ?>
                    <i class="fa fa-chevron-right"></i>
                </p>
            </button>
            <div class="dropdown-container">
                <a href="../conflict_bookings.php" class="<?= ($currentPage == 'conflict_bookings.php') ? 'active' : '' ?>">Bookings with conflicts</a>
                <a href="../no_conflict_bookings.php" class="<?= ($currentPage == 'no_conflict_bookings.php') ? 'active' : '' ?>">Bookings without conflicts</a>
            </div>
        <?php endif; ?>
        <?php if ($user_role == 'hod'): ?>
            <button class="dropdown-btn">
                Manage Bookings
                <i style="margin-left:20px;" class="fa fa-chevron-right"></i>
            </button>
            <div class="dropdown-container">
                <a href="../forward_bookings.php" class="<?= ($currentPage == 'forward_bookings.php') ? 'active' : '' ?>">Forward Bookings <?= ($pending_count1 > 0) ? "<span class='badge bg-light text-dark'>$pending_count1</span>" : "" ?></a>
                <a href="../view_bookings.php" class="<?= ($currentPage == 'view_bookings.php') ? 'active' : '' ?>">View Bookings</a>
            </div>
        <?php endif; ?>
    </div>

    <button class="toggle-btn" onclick="toggleNav()">☰</button>

    <script>
        function toggleNav() {
            const sidenav = document.getElementById("mySidenav");
            const toggleBtn = document.querySelector(".toggle-btn");

            if (sidenav.style.width === "250px") {
                sidenav.style.width = "0"; // Close sidebar
                toggleBtn.style.left = "0"; // Move button to the left
                document.getElementById("main").style.marginLeft = "0";
                toggleBtn.classList.add("open");
            } else {
                sidenav.style.width = "250px";
                toggleBtn.style.left = "200px";
                document.getElementById("main").style.marginLeft = "250px";
                toggleBtn.classList.remove("open");
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownBtns = document.querySelectorAll('.dropdown-btn');
            const dropdownContainers = document.querySelectorAll('.dropdown-container');
            const dropdownLinks = document.querySelectorAll('.dropdown-container a'); // Select all dropdown links
            const dashboardLink = document.querySelector('.sidenav a[href="home.php"]'); // Select the Dashboard link

            const activeMenuIndex = sessionStorage.getItem('activeMenuIndex');

            if (activeMenuIndex !== null) {
                dropdownBtns[activeMenuIndex].classList.add('active');
                dropdownContainers[activeMenuIndex].classList.add('active');
            }

            // Handle Dashboard link click
            if (dashboardLink) {
                dashboardLink.addEventListener('click', function() {
                    // Remove active state from all dropdown menus
                    dropdownContainers.forEach((container) => container.classList.remove('active'));
                    dropdownBtns.forEach((btn) => btn.classList.remove('collapsed'));

                    // Remove active state from all dropdown links
                    dropdownLinks.forEach(link => link.classList.remove('active'));

                    // Clear the active menu index from session storage
                    sessionStorage.removeItem('activeMenuIndex');
                });
            }

            // Handle dropdown button clicks
            dropdownBtns.forEach((btn, index) => {
                const dropdown = btn.nextElementSibling;

                if (btn.classList.contains('active')) {
                    dropdown.classList.add('active');
                    btn.classList.add('collapsed');
                }

                btn.addEventListener('click', function() {
                    const isActive = dropdown.classList.contains('active');
                    if (isActive) {
                        dropdown.classList.remove('active');
                        btn.classList.remove('collapsed');
                        sessionStorage.removeItem('activeMenuIndex');
                    } else {
                        dropdownContainers.forEach((container) => container.classList.remove('active'));
                        dropdownBtns.forEach((btn) => btn.classList.remove('collapsed'));

                        dropdown.classList.add('active');
                        btn.classList.add('collapsed');
                        sessionStorage.setItem('activeMenuIndex', index);
                    }
                });
            });

            // Handle dropdown link clicks
            dropdownLinks.forEach(link => {
                link.addEventListener('click', function() {
                    dropdownLinks.forEach(link => link.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });


        const dashboardLink = document.querySelector('.sidenav a[href="home.php"]');
        if (dashboardLink) {
            dashboardLink.addEventListener('click', function() {
                // Remove active state from all dropdown menus
                dropdownContainers.forEach((container) => container.classList.remove('active'));
                dropdownBtns.forEach((btn) => btn.classList.remove('collapsed'));

                // Remove active state from all dropdown links
                dropdownLinks.forEach(link => link.classList.remove('active'));

                // Clear the active menu index from session storage
                sessionStorage.removeItem('activeMenuIndex');
            });
        }
    </script>
</body>

</html>