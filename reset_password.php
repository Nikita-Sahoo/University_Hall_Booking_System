<?php session_start();
include('assets/conn.php');
?>
<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">

    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="Favicon.png">

    <!-- Bootstrap CSS -->
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" />
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <title>Recover Password</title>

    <style>
        .login-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            margin: 80px auto;
        }
        .login-container h3 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
        }
        .login-container .form-control {
            height: 45px;
            font-size: 14px;
        }
        .login-container p {
            margin-top: 15px;
            text-align: center;
        }
        .login-container p a {
            color: #007bff;
            text-decoration: none;
        }
        .login-container p a:hover {
            text-decoration: underline;
        }
        .alert {
            margin-top: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-light" style="position: fixed; width: 100%; top: 0; z-index: 10; background-color: #007bff; height: 80px;">
    <div class="container-fluid d-flex justify-content-between align-items-center" style="height: 100%;">
        <div class="navbar-logo">
            <img src="image/logo/PU_Logo_Full.png" alt="Pondicherry University Logo" style="height: 40px; margin-left: 10px;">
        </div>
        <div class="navbar-title" style="position: absolute; left: 52%; transform: translateX(-60%); text-align: center;">
            <h4 class="text-white m-0" style="font-size: 20px;">UNIVERSITY HALL BOOKING SYSTEM</h4>
        </div>
    </div>
</nav>
<br><br><br>

<main class="login-form">
    <div class="login-container">
        <form action="#" method="POST" name="recover_psw">
            <h3>Recover Password</h3>

            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                    <span class="input-group-text" id="togglePassword">
                        <i class="bi bi-eye-slash"></i>
                    </span>
                </div>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <span class="input-group-text" id="toggleConfirmPassword">
                        <i class="bi bi-eye-slash"></i>
                    </span>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <div class="col-md-6 offset-md-3 text-center">
                <input class="btn btn-primary w-100" type="submit" value="Reset" name="reset">
            </div>
        </form>
    </div>
</main>


<?php
// Handle password recovery
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || empty($confirm_password)) {
        $error_msg = "Both fields are required!";
    } else if ($new_password === $confirm_password) {
        if (isset($_POST["reset"])) {
            $psw = $_POST["new_password"];
            $token = $_GET['token'] ?? null;
            $email = $_GET['email'] ?? null;

            $hash = password_hash($psw, PASSWORD_DEFAULT);
            $sql = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
            $query = mysqli_num_rows($sql);

            if ($query > 0) {
                mysqli_query($conn, "UPDATE users SET password='$hash' WHERE email='$email'");
                $success_msg = "Your password has been successfully reset!";
                ?><script>
                window.location.replace("index.php");
                alert("<?php echo "your password has been succesful reset"?>");
            </script><?php
            } else {
                $error_msg = "Invalid token or email. Please try again.";
            }
        }
    } else {
        $error_msg = "Passwords do not match. Please try again.";
    }
}
?>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const newPassword = document.getElementById('new_password');
    togglePassword.addEventListener('click', function () {
        const type = newPassword.getAttribute('type') === 'password' ? 'text' : 'password';
        newPassword.setAttribute('type', type);
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });

    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPassword = document.getElementById('confirm_password');
    toggleConfirmPassword.addEventListener('click', function () {
        const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPassword.setAttribute('type', type);
        this.querySelector('i').classList.toggle('bi-eye');
        this.querySelector('i').classList.toggle('bi-eye-slash');
    });
</script>
</body>
</html>
