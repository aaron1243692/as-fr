<?php
(session_status() == PHP_SESSION_NONE) ? session_start() : '';
include 'config/connection.php';
include 'config/face-auth.php';
face_auth_ensure_schema($conn);

if (!empty($_SESSION['message'])) {
    echo "
        <script>
            alert('" . $_SESSION['message'] . "');
        </script>
    ";
    unset($_SESSION['message']);
}

if (empty($_SESSION['id'])) {
    header('location: sign-in.php');
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

if (face_auth_is_pending() && $currentPage !== 'face-recognition.php' && $currentPage !== 'logout.php') {
    header('location: face-recognition.php');
    exit;
}

if (!face_auth_is_pending() && face_auth_user_has_registered_face($conn, (int) $_SESSION['id']) && empty($_SESSION['face_verified_at'])) {
    face_auth_begin_pending([
        'id' => (int) $_SESSION['id'],
        'role' => $_SESSION['role'] ?? 'student',
        'name' => ''
    ]);
    $_SESSION['message'] = 'Complete face verification to continue.';
    header('location: face-recognition.php');
    exit;
}
?>
