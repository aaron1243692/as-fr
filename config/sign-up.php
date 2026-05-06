<?php
    include 'connection.php';
    session_start();

    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $emailInput = $_POST['email'];
    $contact = $_POST['contact'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if ($password !== $confirm) {
        echo "<script>
                alert('Password do not match');
                window.history.back();
            </script>";
        exit;
    }

    $emailStmt = $conn->prepare("
        SELECT * FROM users
        WHERE email=?
    ");
    $emailStmt->bind_param('s', $emailInput);
    $emailStmt->execute();
    $res = $emailStmt->get_result();
    if ($res->num_rows) {
        echo "<script>
                alert('Email is already taken');
                window.history.back();
            </script>";
        exit;
    }

    $idStmt = $conn->prepare("
        SELECT * FROM users
        WHERE tag=?
    ");
    $idStmt->bind_param('s', $student_id);
    $idStmt->execute();
    $res = $idStmt->get_result();
    if ($res->num_rows) {
        echo "<script>
                alert('Student ID/Tag is already taken');
                window.history.back();
            </script>";
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $insert = $conn->prepare("
        INSERT INTO users(tag, name, email, contact, password)
        VALUES(?,?,?,?,?)
    ");
    $insert->bind_param('sssss', $student_id, $name, $emailInput, $contact, $hash);
    $insert->execute();

    $_SESSION['message'] = 'successfully registered';
    header('location: ../index.php');
    exit;
?>
