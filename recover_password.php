<?php session_start() ?>

<link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<!------ Include the above in your HEAD tag ---------->

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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
        }

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

        .navbar {
            background-color:  #007bff;
            z-index: 100;
            position: fixed;
            width: 100%;
            top: 0;
        }

        .navbar .logo {
            margin: 10px 50px;
        }

        .navbar h5 {
            color: white;
            margin-right: 50px;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle i {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-light" style="position: fixed; width: 100%; top: 0; z-index: 10; background-color: #007bff; height: 80px;">
    <div class="container-fluid d-flex justify-content-between align-items-center" style="height: 100%;">
        <!-- Left side: Logo -->
        <div class="navbar-logo">
            <img src="image/logo/PU_Logo_Full.png" alt="Pondicherry University Logo" style="height: 40px; margin-left: 10px;">
        </div>

        <!-- Centered Title -->
        <div class="navbar-title" style="position: absolute; left: 52%; transform: translateX(-60%); text-align: center;">
            <h4 class="text-white m-0" style="font-size: 20px;">UNIVERSITY HALL BOOKING SYSTEM</h4>
        </div>

    </div>
</nav>
<br><br><br>
<br><br><br>

<div class="main-content">

<main class="login-form">
    <div class="login-container">
        <form action="#" method="POST" name="recover_psw">
            <h3>Recover Password</h3>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email_address" name="email_address" required autofocus>
            </div>
            <div class="col-md-6 offset-md-3 text-center">
                <input class="btn btn-primary w-100" type="submit" value="Recover" name="recover">
            </div>
        </form>
    </div>
</main>

</main>
</body>
</html>

<?php 
    if(isset($_POST["recover"])){
        include('assets/conn.php');
        $email = $_POST["email_address"];

        $sql = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        $query = mysqli_num_rows($sql);
  	    $fetch = mysqli_fetch_assoc($sql);

        if(mysqli_num_rows($sql) <= 0){
            ?>
            <script>
                alert("<?php  echo "Sorry, no emails exists "?>");
            </script>
            <?php
        }else{
            // generate token by binaryhexa 
            $token = bin2hex(random_bytes(50));

            session_start ();
            $_SESSION['token'] = $token;
            $_SESSION['email'] = $email;
            require "login/Mail/phpmailer/PHPMailerAutoload.php";
            $mail = new PHPMailer;

            $mail->isSMTP();
            $mail->Host='smtp.gmail.com';
            $mail->Port=587;
            $mail->SMTPAuth=true;
            $mail->SMTPSecure='tls';

            // h-hotel account
            $mail->Username='23352070@pondiuni.ac.in';
            $mail->Password='uviozkjvnurovgrx';

            // send by h-hotel email
            $mail->setFrom('23352070@pondiuni.ac.in', 'Password Reset');
            // get email from input
            $mail->addAddress($_POST["email_address"]);
            //$mail->addReplyTo('lamkaizhe16@gmail.com');

            // HTML body
            $mail->isHTML(true);
            $mail->Subject="Recover your password";
            $mail->Body="<b>Dear User</b>
            <h3>We received a request to reset your password.</h3>
            <p>Kindly click the below link to reset your password</p>
           <a href='http://localhost/demo/reset_password.php?token=$token&email=$email'>Reset Password</a>
            <br><br>
            <p>With regrads,</p>
            <b>HBS-Team,Pondicherry University</b>";

            if(!$mail->send()){
                ?>
                    <script>
                        alert("<?php echo " Invalid Email "?>");
                    </script>
                <?php
            }else{
                ?>
                                <script>
                    alert("Password reset email has been sent successfully. Please check your email.");
                    window.location.replace("index.php");
                </script>
                <?php

            }
        }
    }


?>
