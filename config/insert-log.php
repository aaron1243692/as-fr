<?php
include 'connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tag = trim($_POST['tag']);
    $action = $_POST['action'];

    if (!in_array($action, ['in', 'out'])) {
        $_SESSION['message'] = "Invalid action.";
        header('location: ../dashboard.php');
        exit;
    }

    $query = $conn->prepare("SELECT id FROM users WHERE tag=?");
    $query->bind_param("s", $tag);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];

        $insert = $conn->prepare("INSERT INTO logs (user_id, status) VALUES (?, ?)");
        $insert->bind_param("is", $user_id, $action);

        if ($insert->execute()) {
            $_SESSION['message'] = "Log recorded successfully!";
        } else {
            $_SESSION['message'] = "Failed to record log.";
        }
    } else {
        $_SESSION['message'] = "Student ID not found.";
    }

    header('location: ../dashboard.php');
    exit;
}
?>