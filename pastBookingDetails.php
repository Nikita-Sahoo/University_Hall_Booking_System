<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall Booking Feedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <link
    href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css"
    rel="stylesheet"
/>
    <style>
        .rating label {
            cursor: pointer;
            margin-right: 10px;
            
        }
        strong{
            color:#212f3d;
        } 
    </style>
</head>
<div class="container mt-3">
    <h1 class="text-center p-2">Past Bookings Details</h1>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Hall ID</th>
                <th>Booking Date</th>
               
                <th>Organiser Name</th>
                <th>Organiser Department</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
include 'assets/conn.php'; // Include your database connection file

$query = "SELECT `booking_id`, `hall_id`, `booking_date`, `organiser_name`, `organiser_department` 
          FROM `bookings` 
          WHERE `booking_date` < CURDATE()";

$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $_GET['department Name']=$row['organiser_department'];
    echo "<tr>
            <td>{$row['booking_id']}</td>
            <td>{$row['hall_id']}</td>
            <td>{$row['booking_date']}</td>
            <td>{$row['organiser_name']}</td>
            <td>{$row['organiser_department']}</td>
            <td>
                <button onclick='openFeedbackModal(\"" . $row['booking_id'] . "\")' class='btn btn-primary'>Feedback</button>
            </td>
          </tr>";
}
?>

                    </tbody>
    </table>
</div>

<!-- Feedback Modal -->
<div id="feedbackModal" class="modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered 	modal-xl">
        <div class="modal-content">
            <div class="modal-header">
            <h3 class="modal-title">Feedback-<?php echo $_GET['department Name']?></h3>
            <button type="button" class="btn-close" onclick="closeFeedbackModal()"></button>
            </div>
            <div class="modal-body">
            <form action="submit_feedback.php" method="POST">
    <input type="hidden" id="bookingIdInput" name="booking_id"> <!-- Hidden field for booking ID -->
     <h5></h5>
    <table class="table">
        <tbody>
            <tr>
                <td><strong>Overall Rating:</strong></td>
                <td>
                    <input type="radio" name="overall" value="Not Good" required> Not Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="overall" value="Good"> Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="overall" value="Very Good"> Very Good
                </td>
            </tr>
            <tr>
                <td><strong>Cleanliness & Maintenance:</strong></td>
                <td>
                    <input type="radio" name="cleanliness" value="Not Good" required> Not Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="cleanliness" value="Good"> Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="cleanliness" value="Very Good"> Very Good
                </td>
            </tr>
            <tr>
                <td><strong>Seating & Space Availability:</strong></td>
                <td>
                    <input type="radio" name="seating" value="Not Good" required> Not Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="seating" value="Good"> Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="seating" value="Very Good"> Very Good
                </td>
            </tr>
            <tr>
                <td><strong>Lighting & Ambience:</strong></td>
                <td>
                    <input type="radio" name="lighting" value="Not Good" required> Not Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="lighting" value="Good"> Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="lighting" value="Very Good"> Very Good
                </td>
            </tr>
            <tr>
                <td><strong>Audio/Visual Equipment Quality:</strong></td>
                <td>
                    <input type="radio" name="audio" value="Not Good" required> Not Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="audio" value="Good"> Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="audio" value="Very Good"> Very Good
                </td>
            </tr>
            <tr>
                <td><strong>Air Conditioning/Ventilation:</strong></td>
                <td>
                    <input type="radio" name="ac" value="Not Good" required> Not Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="ac" value="Good"> Good&nbsp;&nbsp;&nbsp;
                    <input type="radio" name="ac" value="Very Good"> Very Good
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Additional Feedback -->
    <div class="mb-3">
        <label class="form-label">Do you find any other problem?</label>
        <textarea class="form-control" name="additional_feedback" placeholder="Describe Your Problem"></textarea>
    </div>

    <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Submit Feedback</button>
    </div>
</form>

            </div>
            
        </div>
    </div>
</div>

<!-- JavaScript for Modal -->
<script>
    function openFeedbackModal(bookingId) {
        document.getElementById('bookingIdInput').value = bookingId;
        document.getElementById('feedbackModal').style.display = 'block';
    }
    function closeFeedbackModal() {
        document.getElementById('feedbackModal').style.display = 'none';
    }
</script>

<style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1050;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }
    .modal-dialog {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
</style>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    

    </body>
    </html>
