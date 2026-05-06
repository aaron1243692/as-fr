<?php
    include 'connection.php';
    session_start();

    $id       = $_POST['id'];
    $current  = $_POST['current'];
    $new      = $_POST['new'];
    $confirm  = $_POST['confirm'];

    $get = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $get->bind_param("i", $id);
    $get->execute();
    $res = $get->get_result();

    if ($res->num_rows == 0) {
        echo "<script>alert('User not found'); window.history.back();</script>";
        exit;
    }

    $row = $res->fetch_assoc();

    if (!password_verify($current, $row['password'])) {
        echo "<script>alert('Current password is incorrect'); window.history.back();</script>";
        exit;
    }

    if ($new !== $confirm) {
        echo "<script>alert('Password do not match'); window.history.back();</script>";
        exit;
    }

    $newHashed = password_hash($new, PASSWORD_DEFAULT);

    $up = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $up->bind_param("si", $newHashed, $id);
    $up->execute();

    $_SESSION['message'] = "Password updated successfully!";
    header("Location: ../password.php");
    exit;
?>
