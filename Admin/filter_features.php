<?php
include '../assets/conn.php';

// Debugging Mode (Set to true to enable)
$debug = false;

// Initialize Conditions and Parameters
$conditions = [];
$featureFilters = [];

// Debugging: Print Received POST Data
if ($debug) {
    echo "<pre>POST Data: ";
    var_dump($_POST);
    echo "</pre>";
}

// Handle Feature Filters
if (!empty($_POST['features']) && is_array($_POST['features'])) {
    foreach ($_POST['features'] as $feature) {
        $feature = trim($feature);
        if (!empty($feature)) {
            // Exclude fields where value is 'NO'
            $featureFilters[] = "LOWER(hd." . $feature . ") != 'no'";
        }
    }
}

// Build Base SQL Query
$sql = "SELECT 
    hd.*, 
    ht.type_name, 
    s.school_name, 
    d.department_name, 
    sec.section_name,
    COALESCE(d.incharge_name, s.incharge_name) AS incharge_name,
    COALESCE(d.designation, s.designation) AS designation,
    COALESCE(d.incharge_intercom, s.incharge_intercom) AS incharge_intercom,
    COALESCE(d.incharge_email, s.incharge_email) AS incharge_email
FROM hall_details hd
JOIN hall_type ht ON hd.type_id = ht.type_id
LEFT JOIN schools s ON hd.school_id = s.school_id
LEFT JOIN departments d ON hd.department_id = d.department_id
LEFT JOIN section sec ON hd.section_id = sec.section_id";

// Apply Filters if Any
if (!empty($featureFilters)) {
    $sql .= " WHERE " . implode(" AND ", $featureFilters);
}

// Debugging: Print SQL Query
if ($debug) {
    echo "<pre>Final Query: " . $sql . "</pre>";
}

// Prepare and Execute Query
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();

// Display Results
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Extract Features Dynamically
        $featureKeys = ['wifi', 'ac', 'projector', 'smart_board', 'computer', 'audio_system', 'podium', 'white_board', 'blackboard', 'lift', 'ramp'];
        $features = [];

        foreach ($featureKeys as $key) {
            // Exclude features with value 'NO'
            if (!empty($row[$key]) && strtolower(trim($row[$key])) != 'no') {
                $features[] =  htmlspecialchars($row[$key]);
            }
        }

        $features_string = !empty($features) ? implode(', ', $features) : 'None';

        // Display Data
        echo "<tr>
            <td><input type='checkbox' class='hall-checkbox' value='" . htmlspecialchars($row['hall_id']) . "'></td>
            <td>" . htmlspecialchars($row['from_date']) . "</td>
            <td>
                <b style='color: #007bff;'>" . htmlspecialchars($row['type_name']) . "</b><br>
                " . htmlspecialchars($row['hall_name']) . "<br>
                " . htmlspecialchars($row['capacity']) . "<br>
                " . htmlspecialchars($row['floor']) . "<br>
                " . htmlspecialchars($row['zone']) . "
            </td>
            <td>" . (!empty($row['section_name']) ? htmlspecialchars($row['section_name']) : "<b style='color: #007bff;'>" . htmlspecialchars($row['school_name']) . "</b><br>" . htmlspecialchars($row['department_name'])) . "</td>
            <td>" . $features_string . "</td>
            <td>
                <b style='color: #007bff;'>" . htmlspecialchars($row['incharge_name']) . "</b><br>
                " . htmlspecialchars($row['designation']) . "<br>
                " . htmlspecialchars($row['incharge_email']) . "<br>
                " . htmlspecialchars($row['incharge_intercom']) . "
            </td>
            <td><center>" . ucwords(htmlspecialchars($row['availability'])) . 
                (!empty($row['start_date']) && !empty($row['to_date']) 
                    ? "<br><b style='color:#007bff;'>From: </b>" . htmlspecialchars($row['start_date']) . "<br><b style='color:#007bff;'>To: </b>" . htmlspecialchars($row['to_date']) 
                    : "") . 
            "</center></td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='7'>No results found</td></tr>";
}

// Close Statement and Connection
$stmt->close();
$conn->close();
?>
