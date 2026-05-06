<?php
include 'config/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'];

    $query = "SELECT users.tag AS tag, users.name AS name,
                     CONCAT(UPPER(LEFT(logs.status,1)), LOWER(SUBSTRING(logs.status,2))) AS status,
                     logs.time AS time
              FROM logs
              JOIN users ON logs.user_id = users.id";

    if ($type === 'today') {
        $query .= " WHERE logs.time >= CURDATE()";
        $filename = "logs_today.csv";
    } else {
        $filename = "logs_all.csv";
    }

    $query .= " ORDER BY logs.time DESC, users.name ASC";

    $result = $conn->query($query);

    // CSV headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="'.$filename.'"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student ID', 'Name', 'Status', 'Time']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [$row['tag'], $row['name'], $row['status'], $row['time']]);
    }

    fclose($output);
    exit;
}
?>
