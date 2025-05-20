<?php
include '../assets/conn.php';

$conditions = [];
$params = [];

if (!empty($_POST['availability'])) {
    $conditions[] = "hd.availability = ?";
    $params[] = $_POST['availability'];

    // echo $_POST['availability'];
    // exit;

$sql = "SELECT 
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

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $features = array_filter([
            $row['wifi'], $row['ac'], $row['projector'],
            $row['smart_board'], $row['computer'], $row['audio_system'],
            $row['podium'], $row['white_board'], $row['blackboard'],
            $row['lift'], $row['ramp']
        ], fn($feature) => $feature != 'No');
        $features_string = implode(', ', $features);

        echo "<tr>
            <td><input type='checkbox' class='hall-checkbox' style='width: 20px; height: 20px; margin: 100% 40%;' value='{$row['hall_id']}'></td>
            <td>{$row['from_date']}</td>
            <td><b style='color: #007bff;'>{$row['type_name']}</b><br>{$row['hall_name']}<br>{$row['capacity']}<br>{$row['floor']}<br>{$row['zone']}</td>
            <td>" . (!empty($row['section_id']) ? $row['section_name'] : "<b style='color: #007bff;'>{$row['school_name']}</b><br>{$row['department_name']}") . "</td>
            <td>{$features_string}</td>
            <td><b style='color: #007bff;'>{$row['incharge_name']}</b><br>{$row['designation']}<br>{$row['incharge_email']}<br>{$row['incharge_intercom']}</td>
            <td><center>" . ucwords($row['availability']) . 
                (!empty($row['start_date']) && !empty($row['to_date']) ? "<br><b style='color:#007bff;'>From: </b>{$row['start_date']}<br><b style='color:#007bff;'>To: </b>{$row['to_date']}" : "") .
            "</center></td>
        </tr>";
    
    }
} else {
    echo "<tr><td colspan='7'>No results found</td></tr>";
}
}
$stmt->close();
$conn->close();
?>