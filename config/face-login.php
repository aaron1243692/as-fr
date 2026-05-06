<?php
include 'connection.php';
include 'face-auth.php';

face_auth_start_session();
face_auth_ensure_schema($conn);

header('Content-Type: application/json');

if (empty($_SESSION['id']) || empty($_SESSION['pending_face_user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Face verification session not found.',
        'redirect' => 'sign-in.php'
    ]);
    exit;
}

$userId = (int) $_SESSION['id'];
$payload = json_decode(file_get_contents('php://input'), true);
$action = $payload['action'] ?? 'verify';

if ($action === 'camera_error') {
    face_auth_log_attempt($conn, $userId, 'missing_camera', (string) ($payload['reason'] ?? 'Camera unavailable'));
    face_auth_logout();
    echo json_encode([
        'success' => false,
        'message' => 'No camera is available. Login was blocked for security reasons.',
        'redirect' => 'sign-in.php'
    ]);
    exit;
}

if (face_auth_is_blocked($conn, $userId)) {
    face_auth_log_attempt($conn, $userId, 'locked', 'Blocked after too many failed verification attempts');
    face_auth_logout();
    echo json_encode([
        'success' => false,
        'locked' => true,
        'message' => 'Too many failed face verification attempts. Please try again later.',
        'attempts_remaining' => 0,
        'redirect' => 'sign-in.php'
    ]);
    exit;
}

$descriptor = $payload['descriptor'] ?? [];
$liveness = $payload['liveness'] ?? [];

if (!is_array($descriptor) || count($descriptor) !== 128) {
    echo json_encode([
        'success' => false,
        'message' => 'A valid captured face is required.',
        'attempts_remaining' => max(0, FACE_AUTH_MAX_ATTEMPTS - ((int) ($_SESSION['face_failed_attempts'] ?? 0)))
    ]);
    exit;
}

$requiredChecks = ['front', 'blink'];
foreach ($requiredChecks as $check) {
    if (empty($liveness[$check])) {
        echo json_encode([
            'success' => false,
            'message' => 'Complete the front-face and blink checks before verifying.',
            'attempts_remaining' => max(0, FACE_AUTH_MAX_ATTEMPTS - ((int) ($_SESSION['face_failed_attempts'] ?? 0)))
        ]);
        exit;
    }
}

$samples = face_auth_get_stored_samples($conn, $userId);

if (empty($samples)) {
    face_auth_clear_pending();
    $_SESSION['message'] = 'No registered face was found, so face verification was skipped.';
    echo json_encode([
        'success' => true,
        'redirect' => 'dashboard.php'
    ]);
    exit;
}

$distance = face_auth_compare_descriptor_sets(array_map('floatval', $descriptor), $samples);

if ($distance !== null && $distance <= FACE_AUTH_MATCH_THRESHOLD) {
    face_auth_complete_pending();
    face_auth_log_attempt($conn, $userId, 'success', 'Face verification passed', $distance);
    echo json_encode([
        'success' => true,
        'message' => 'Face verified successfully.',
        'attempts_remaining' => FACE_AUTH_MAX_ATTEMPTS,
        'redirect' => 'dashboard.php'
    ]);
    exit;
}

$_SESSION['face_failed_attempts'] = ((int) ($_SESSION['face_failed_attempts'] ?? 0)) + 1;
$attemptsRemaining = max(0, FACE_AUTH_MAX_ATTEMPTS - (int) $_SESSION['face_failed_attempts']);

face_auth_log_attempt($conn, $userId, 'failure', 'Face did not match enrolled samples', $distance);

if ($attemptsRemaining <= 0) {
    face_auth_log_attempt($conn, $userId, 'locked', 'Maximum face verification attempts reached', $distance);
    face_auth_logout();
    echo json_encode([
        'success' => false,
        'locked' => true,
        'message' => 'Maximum face verification attempts reached. Please sign in again later.',
        'attempts_remaining' => 0,
        'redirect' => 'sign-in.php'
    ]);
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Face did not match your registered samples.',
    'attempts_remaining' => $attemptsRemaining
]);
exit;
?>
