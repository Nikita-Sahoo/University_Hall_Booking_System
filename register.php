<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$connname = "hbs";

$conn = new mysqli($servername, $username, $password, $connname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $department = $_POST['department'] ?? null; // Department is optional for some roles
    $password = $_POST['password'];

    // Validate input fields
    if (empty($username) || empty($email) || empty($role) || empty($password)) {
        echo "All fields are required!";
        exit();
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Prepare SQL based on role
    if ($role == 'admin' || $role == 'Registrar') {
        $sql = "INSERT INTO Users (username, email, password, role) VALUES (?, ?, ?, ?)";
    } else {
        // For roles with departments
        if (empty($department)) {
            echo "Department is required for Professors, Deans, and HODs!";
            exit();
        }
        $sql = "INSERT INTO Users (username, email, password, role, department_id) VALUES (?, ?, ?, ?, ?)";
    }

    $stmt = $conn->prepare($sql);
    
    // Check if prepare() was successful
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameters and execute query
    if ($role == 'admin' || $role == 'Registrar') {
        $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);
    } else {
        $stmt->bind_param("ssssi", $username, $email, $hashedPassword, $role, $department);
    }

    if ($stmt->execute()) {
        // Redirect to login page after successful registration
        header("Location: login.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - University Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            margin-top: 50px;
            max-width: 500px;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
        }
        .form-container h3 {
            margin-bottom: 20px;
            font-size: 28px;
        }
       
.navbar {
  background-color:  #007bff;
  z-index: 100;
  position: fixed;
  width: 100%;
  top: 0;
}

    </style>
</head>
<body>
    
<nav class="navbar navbar-light" style="position: fixed;">
        <img src="image/logo/PU_Logo_Full.png" alt="Pondicherry University Logo" style="margin: 10px 50px;" class="logo">
        <!-- <span class="ml-auto"> -->
            <h5 style="color: white; margin-right:50px;">UNIVERSITY HALL BOOKING SYSTEM</h5>
            </nav>
            <div class="main-content">
<br>
<br>
<br>
    <div class="container d-flex justify-content-center align-items-center">
        <div class="form-container">
            <form action="register.php" method="POST">
                <h3 class="text-center">Register</h3>

                <!-- Username -->
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <!-- Role -->
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="" disabled selected>Select Role</option>
                        <option value="prof">Professor</option>
                        <option value="dean">Dean</option>
                        <option value="hod">HOD</option>
                        <option value="admin">Admin</option>
                        <option value="registrar">Registrar</option>
                    </select>
                </div>

                <!-- Department (only visible for certain roles) -->
                <div class="mb-3" id="department-section" style="display: none;">
                    <label for="department" class="form-label">Department</label>
                    <select class="form-select" id="department" name="department">
                        <option value="">Select Department</option>
                        <?php
                        // Fetch departments from the database
                        $sql = "SELECT department_id, department_name FROM Departments";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . $row['department_id'] . '">' . $row['department_name'] . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary w-100">Register</button>

                <p class="text-center mt-3">Already have an account? <a href="login.php">Login here</a>.</p>
            </form>
        </div>
    </div>

    <script>
        // Show/Hide department section based on the role
        document.getElementById('role').addEventListener('change', function() {
            var role = this.value;
            var departmentSection = document.getElementById('department-section');
            if (role === 'prof' || role === 'dean' || role === 'hod') {
                departmentSection.style.display = 'block';
            } else {
                departmentSection.style.display = 'none';
            }
        });
    </script>
</body>
</html>
