<?php
    include 'connection.php';
    session_start();

    $id = $_GET['id'];

    $del = $conn->prepare("
        DELETE FROM users 
        WHERE id=?
    ");
    $del->bind_param("i", $id);
    $del->execute();

    $_SESSION['message'] = 'Student successfully deleted';
    header('location: ../students.php');
    exit;
?>