<?php
session_start();
include '../include/db.php';

$user_id = $_SESSION['id'];

$stmt = $conn->prepare("SELECT face_descriptor FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result()->fetch_assoc();

if(!$result || !$result['face_descriptor']){
    echo json_encode(["success" => false]);
    exit;
}

echo json_encode([
    "success" => true,
    "descriptor" => json_decode($result['face_descriptor'])
]);