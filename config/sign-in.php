<?php
    include "connection.php";
    include "face-auth.php";
    session_start();

    face_auth_ensure_schema($conn);

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        echo "<script>alert('Email and password are required'); window.history.back();</script>";
        exit;
    }

    face_auth_clear_pending();
    unset($_SESSION['face_verified_at']);

    $user = $conn->prepare("
        SELECT id, name, role, email, password
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $user->bind_param('s', $email);
    $user->execute();
    $result = $user->get_result();

    if ($result->num_rows === 0) {
        echo "<script>alert('Email not found'); window.history.back();</script>";
        exit;
    }

    $row = $result->fetch_assoc();
    if (!password_verify($password, $row['password'])) {
        echo "<script>alert('Incorrect password'); window.history.back();</script>";
        exit;
    }

    if (face_auth_user_has_registered_face($conn, (int) $row['id'])) {
        face_auth_begin_pending($row);
        $_SESSION['message'] = 'Password accepted. Complete face verification to continue.';
        header("location: ../face-recognition.php");
        exit;
    }

    $_SESSION['id'] = (int) $row['id'];
    $_SESSION['role'] = $row['role'];
    $_SESSION['face_verified_at'] = time();
    $_SESSION['message'] = "Login successfully";

    header("location: ../dashboard.php");
    exit;
?>
