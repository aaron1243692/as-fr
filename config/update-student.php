<?php
    include 'connection.php';
    session_start();

    $id = $_POST['id'];
    $tag = $_POST['tag'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $pass_input = $_POST['password']; 

    $check = $conn->prepare("
        SELECT id FROM users
        WHERE email = ? AND id != ?
    ");
    $check->bind_param("si", $email, $id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        echo "
            <script>
                alert('Email is already taken');
                window.history.back();
            </script>
        ";
        exit;
    }


    if (empty($pass_input)) {

        $up = $conn->prepare("
            UPDATE users
            SET tag = ?, name = ?, email = ?, contact = ?
            WHERE id = ?
        ");
        $up->bind_param("ssssi", $tag, $name, $email, $contact, $id);

    } 
    
    else {

        $hashed = password_hash($pass_input, PASSWORD_DEFAULT);

        $up = $conn->prepare("
            UPDATE users
            SET tag = ?, name = ?, email = ?, contact = ?, password = ?
            WHERE id = ?
        ");
        $up->bind_param("sssssi", $tag, $name, $email, $contact, $hashed, $id);
    }

    $up->execute();

    $_SESSION['message'] = "Account updated successfully";
    header('location: ../students.php');
    exit;

?>
