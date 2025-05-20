<?php
// session_start();
include '../assets/conn.php';
include '../assets/header_1.php';
// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$query_user = $conn->prepare("SELECT role, school_id, department_id FROM users WHERE user_id = ?");
$query_user->bind_param("i", $user_id);
$query_user->execute();
$user = $query_user->get_result()->fetch_assoc();

$role = $user['role'];
$school_id = $user['school_id'];
$department_id = $user['department_id'];

// Base Query
$query = "SELECT 
    hd.*, 
    ht.type_name, 
    s.school_name, 
    d.department_name, 
    sec.section_name,
    COALESCE(d.incharge_name, s.incharge_name ) AS incharge_name,
    COALESCE(d.designation, s.designation ) AS designation,
    COALESCE(d.incharge_intercom, s.incharge_intercom) AS incharge_intercom,
    COALESCE(d.incharge_email, s.incharge_email) AS incharge_email
FROM hall_details hd
JOIN hall_type ht ON hd.type_id = ht.type_id
LEFT JOIN schools s ON hd.school_id = s.school_id
LEFT JOIN departments d ON hd.department_id = d.department_id
LEFT JOIN section sec ON hd.section_id = sec.section_id";

// Apply role-based filtering
$conditions = [];
// if ($role == 'dean') {
//     $conditions[] = "hd.school_id = '$school_id'";
// } elseif ($role == 'hod') {
//     $conditions[] = "hd.department_id = '$department_id'";
// }

// Apply additional filters
$filters = ['hallType' => 'ht.type_name', 'hallName' => 'hd.hall_name', 'capacity' => 'hd.capacity', 'floor' => 'hd.floor', 'zone' => 'hd.zone'];
foreach ($filters as $param => $column) {
    if (!empty($_GET[$param])) {
        $conditions[] = "$column LIKE '%" . $conn->real_escape_string($_GET[$param]) . "%'";
    }
}

// Append conditions to the query
if (!empty($conditions)) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}
$query .= " ORDER BY hd.hall_id DESC";

$result = $conn->query($query);
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
    <link rel="stylesheet" href="../assets/design1.css" />
    <style>
        .modal-header {
            background-color: #007bff;
            color: white;
        }

        .table-wrapper {
            overflow-x: auto;
            max-width: 100%;
        }

        thead th {
            text-align: center;
            background-color: #f4f4f4;
            padding: 10px;
        }

        .container1 {
            width: calc(100%);
            max-width: none;
            justify-content: space-between;
            align-items: center;
            padding: 0 2%;
        }

        .card {
            border: none;
        }

        .filter-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .filter-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            width: 300px;
            position: relative;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 18px;
            cursor: pointer;
        }

        input[type=text],
        input[type=email],
        input[type=number],
        select,
        textarea {
            width: 100%;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            margin-top: 4px;
            margin-bottom: 4px;
            resize: vertical;
        }

        /* icon button  */
        .icon-button {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            background-color: transparent;
            border: 2px solid;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .icon-button i {
            font-size: 16px;
        }

        /* Blue Button */
        .blue-button {
            color: #007bff;
            border-color: #007bff;
        }

        .blue-button:hover {
            background-color: #007bff;
            color: white;
        }
    </style>
    <title>Admin Home</title>
</head>

<body>
    <div id="main">
        <div class="container1 mt-3">
            <div class="table-wrapper">
                <div class="card">
                    <div class="card-body">
                        <center>
                            <h1 style="color:#170098;">Arhived Hall Details</h1>
                            <!-- <button class="btn btn-success" onclick="clearForm()" style="margin-left: 85%; margin-bottom: 10px;">Clear All Filters</button> -->
                        </center>

                        <div class="row">
                            <div class="col-8">
                                <div style="display: flex; align-items: center; gap: 10px; margin: 20px;">
                                    <!-- active  -->
                                    <button onclick="updateMultipleStatus()" class="icon-button blue-button">
                                        <i class="fa-solid fa-pen-to-square"></i> Active
                                    </button>
                                </div>
                            </div>
                            <div class="col-4">
                                <!-- Toggle Button -->
                                <div style="margin: 30px 0 0 150px; display: flex; align-items: center; gap: 10px;">
                                    <label for="multiSelectToggle">Multiple Selection:</label>
                                    <button id="multiSelectToggle" onclick="toggleMultiSelect()" style="padding: 5px 10px; border: none; border-radius: 5px; background-color: #ccc; cursor: pointer;">
                                        Off
                                    </button>
                                </div>

                            </div>
                        </div>



                        <!-- Table -->
                        <div class="table-container" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-bordered" id="bookingTable">
                                <thead>
                                    <tr>
                                        <th> Select
                                        </th>
                                        <th style="width: 105px;">Date </th>
                                        <th style="width: 190px;">Hall Details 
                                            <!-- <img src="image/filter_1.jpeg" class="filter" style="margin-left:10px; height: 17px; cursor: pointer;" alt="Room Image" onclick="openFilterPopup()" /> -->
                                         </th>
                                        <th>Belongs to 
                                            <!-- <img src="image/filter_1.jpeg" class="filter" style="margin-left:10px; height: 17px; cursor: pointer;" alt="Room Image" onclick="openFilterPopup1()" />  -->
                                        </th>
                                        <th>Features 
                                            <!-- <img src="image/filter_1.jpeg" class="filter" style="margin-left:10px; height: 17px; cursor: pointer;" alt="Room Image" onclick="openFilterPopup2()" />  -->
                                        </th>
                                        <th>Incharge Details
                                            <!-- <img src="image/filter_1.jpeg" class="filter" style="margin-left:10px; height: 17px; cursor: pointer;" alt="Room Image" onclick="openFilterPopup3()" />  -->
                                        </th>
                                        <!-- <th style="width:20%;">Availability <img src="image/filter_1.jpeg" class="filter" style="margin-left:10px; height: 17px; cursor: pointer;" alt="Room Image" onclick="openFilterPopup4()" /> </th> -->
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $features = array_filter([
                                                $row['wifi'],
                                                $row['ac'],
                                                $row['projector'],
                                                $row['smart_board'],
                                                $row['computer'],
                                                $row['audio_system'],
                                                $row['podium'],
                                                $row['white_board'],
                                                $row['blackboard'],
                                                $row['lift'],
                                                $row['ramp']
                                            ], fn($feature) => $feature != 'No');
                                            $features_string = implode(', ', $features);
                                            if ($row['status'] == "Archived") {
                                    ?>
                                                <tr>
                                                    <td>
                                                    <input type="checkbox" style="width: 20px; height: 20px; margin: 100% 30%;" class="hall-checkbox" value="<?php echo $row['hall_id']; ?>" onclick="handleCheckbox(this)">
                                                    </td>
                                                    <td><?php echo $row['from_date']; ?></td>
                                                    <td>
                                                        <b style="color: #007bff;"><?php echo $row['type_name']; ?></b><br>
                                                        <?php echo $row['hall_name']; ?><br>
                                                        <?php echo $row['capacity']; ?><br>
                                                        <?php echo $row['floor']; ?><br>
                                                        <?php echo $row['zone']; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (empty($row['section_id'])) { ?>
                                                            <b style="color: #007bff;"><?php echo $row['school_name']; ?></b><br>
                                                            <?php echo $row['department_name']; ?><br>
                                                        <?php } else {
                                                            echo $row['section_name'];
                                                        } ?>
                                                    </td>
                                                    <td><?php echo $features_string; ?></td>
                                                    <td><b style="color: #007bff;"><?= $row['incharge_name']; ?></b><br><?= $row['designation']; ?><br><?= $row['incharge_email']; ?><br><?= $row['incharge_intercom']; ?></td>

                                                    <!-- <td>
                                                    <center>
                                                        ?php
                                                        echo ucwords($row['availability']);
                                                        if (!empty($row['start_date']) && !empty($row['to_date'])) {
                                                            echo "<br><b style='color:#007bff;'>From: </b>" . $row['start_date'] . "<br><b style='color:#007bff;'>To: </b> " . ($row['to_date']);
                                                        }
                                                        ?>
                                                    </center>
                                                </td> -->
                                                    <td><b style="color: red; "> <?php echo ucwords($row['status']); ?></b><br>
                                                        <?php echo ucwords($row['availability']); ?>
                                                    </td>
                                                </tr>
                                    <?php }
                                        }
                                    } else {
                                        echo "<tr><td colspan='8'>No results found</td></tr>";
                                    } ?>
                                </tbody>
                            </table>
                        </div>



                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- belongs To   -->
    <script>
        function toggleBelongsTo(value) {
            if (value === 'Department') {
                document.getElementById('departmentFields').style.display = 'block';
                document.getElementById('schoolField').style.display = 'none';
                document.getElementById('sectionField').style.display = 'none';
            } else if (value === 'School') {
                document.getElementById('schoolField').style.display = 'block';
                document.getElementById('departmentFields').style.display = 'none';
                document.getElementById('sectionField').style.display = 'none';
            } else if (value === 'Administration') {
                document.getElementById('sectionField').style.display = 'block';
                document.getElementById('departmentFields').style.display = 'none';
                document.getElementById('schoolField').style.display = 'none';
            }
        }
    </script>

    <script>
        // Toggle all checkboxes
        function toggleAll(source) {
            document.querySelectorAll('.hall-checkbox').forEach(checkbox => {
                checkbox.checked = source.checked;
            });
        }

        // Function to achieve selected halls
        function updateMultipleStatus() {
            const selectedHalls = Array.from(document.querySelectorAll('.hall-checkbox:checked'))
                .map(checkbox => checkbox.value);

            if (selectedHalls.length === 0) {
                alert('Please select at least one hall to archive.');
                return;
            }

            if (!confirm('Are you sure you want to active the selected halls?')) {
                return;
            }

            fetch('remove_archived_halls.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        hall_ids: selectedHalls,
                        status: 'Active'

                    }) // Sending status
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Halls active successfully!');
                        location.reload(); // Refresh page to show updates
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An unexpected error occurred.');
                });
        }

        // Function to modify a selected hall
    </script>



    <script>
        $(document).ready(function() {
            $('#school_name').on('change', function() {
                var school_id = $(this).val();

                $('#department_name').html('<option value="">Select Department</option>');

                if (school_id) {
                    $.ajax({
                        type: 'POST',
                        url: 'fetch_department.php',
                        data: {
                            school_id: school_id
                        },
                        success: function(response) {
                            $('#department_name').html(response);
                        },
                        error: function(xhr, status, error) {
                            console.log("An error occurred: " + error);
                        }
                    });
                }
            });
        });
    </script>

    <!--handle multiple & single selection at a time  -->
    <script>
        let isMultiSelectEnabled = false; // Default to single selection

        // Function to toggle between single and multiple selection
        function toggleMultiSelect() {
            const toggleButton = document.getElementById('multiSelectToggle');
            isMultiSelectEnabled = !isMultiSelectEnabled; // Toggle the state

            // Update button text and style
            if (isMultiSelectEnabled) {
                toggleButton.textContent = 'On';
                toggleButton.style.backgroundColor = '#4CAF50'; // Green for "On"
                toggleButton.style.color = '#fff';
            } else {
                toggleButton.textContent = 'Off';
                toggleButton.style.backgroundColor = '#ccc'; // Gray for "Off"
                toggleButton.style.color = '#000';
            }

            // Reset all checkboxes when toggling
            const checkboxes = document.querySelectorAll('.hall-checkbox');
            checkboxes.forEach((cb) => {
                cb.checked = false; // Uncheck all checkboxes
                cb.disabled = false; // Enable all checkboxes
            });
        }

        // Function to handle checkbox clicks
        function handleCheckbox(checkbox) {
            const checkboxes = document.querySelectorAll('.hall-checkbox');

            if (!isMultiSelectEnabled) {
                if (checkbox.checked) {
                    checkboxes.forEach((cb) => {
                        if (cb !== checkbox) {
                            cb.disabled = true; // Disable other checkboxes
                        }
                    });
                } else {
                    checkboxes.forEach((cb) => {
                        cb.disabled = false; // Re-enable all checkboxes
                    });
                }
            }
        }
    </script>

</body>

</html>