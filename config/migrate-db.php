<?php
$server = "127.0.0.1";
$user = "root";
$password = "";
$database = "attendance";

$conn = new mysqli($server, $user, $password, $database);
if(!$conn) die("Connection error: " . $conn->error);

// Check if image_path column exists
$result = $conn->query("SHOW COLUMNS FROM user_faces LIKE 'image_path'");

if($result->num_rows == 0) {
    // Add image_path column
    $sql = "ALTER TABLE user_faces ADD COLUMN image_path VARCHAR(255) DEFAULT NULL";
    if($conn->query($sql)) {
        echo "✓ image_path column added successfully";
    } else {
        echo "✗ Error adding image_path column: " . $conn->error;
    }
} else {
    echo "✓ image_path column already exists";
}

$conn->close();
?>
