<?php

const FACE_AUTH_MAX_ATTEMPTS = 5;
const FACE_AUTH_MATCH_THRESHOLD = 0.52;

function face_auth_start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function face_auth_ensure_schema(mysqli $conn): void
{
    static $schemaReady = false;

    if ($schemaReady) {
        return;
    }

    $schemaReady = true;

    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM user_faces");

    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = $row;
        }
    }

    if (!isset($columns['sample_label'])) {
        $conn->query("ALTER TABLE user_faces ADD COLUMN sample_label VARCHAR(20) DEFAULT NULL AFTER user_id");
    }

    if (isset($columns['face_data']) && strtoupper((string) $columns['face_data']['Null']) === 'NO') {
        $conn->query("ALTER TABLE user_faces MODIFY face_data LONGBLOB NULL");
    }

    if (!isset($columns['image_path'])) {
        $conn->query("ALTER TABLE user_faces ADD COLUMN image_path VARCHAR(255) DEFAULT NULL");
    }

    if (!isset($columns['descriptor'])) {
        $conn->query("ALTER TABLE user_faces ADD COLUMN descriptor LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL");
    }

    $conn->query(
        "CREATE TABLE IF NOT EXISTS face_auth_logs (
            id BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT(20) NOT NULL,
            event_type ENUM('success','failure','locked','missing_camera') NOT NULL,
            reason VARCHAR(255) DEFAULT NULL,
            match_distance DECIMAL(8,6) DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP(),
            KEY idx_face_auth_logs_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
}

function face_auth_user_has_registered_face(mysqli $conn, int $userId): bool
{
    face_auth_ensure_schema($conn);

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
        FROM user_faces
        WHERE user_id = ?
          AND descriptor IS NOT NULL
          AND descriptor <> ''"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) ($result['total'] ?? 0) > 0;
}

function face_auth_begin_pending(array $user): void
{
    face_auth_start_session();
    $_SESSION['id'] = (int) $user['id'];
    $_SESSION['role'] = (string) $user['role'];
    $_SESSION['face_required'] = true;
    $_SESSION['face_verified_at'] = null;
    $_SESSION['pending_face_user_id'] = (int) $user['id'];
    $_SESSION['face_failed_attempts'] = 0;
}

function face_auth_complete_pending(): bool
{
    face_auth_start_session();

    if (empty($_SESSION['pending_face_user_id'])) {
        return false;
    }

    $_SESSION['face_required'] = true;
    $_SESSION['face_verified_at'] = time();
    $_SESSION['face_failed_attempts'] = 0;
    $_SESSION['pending_face_user_id'] = null;

    return true;
}

function face_auth_clear_pending(): void
{
    unset(
        $_SESSION['pending_face_user_id'],
        $_SESSION['face_required'],
        $_SESSION['face_verified_at'],
        $_SESSION['face_failed_attempts']
    );
}

function face_auth_is_pending(): bool
{
    face_auth_start_session();

    return !empty($_SESSION['id'])
        && !empty($_SESSION['face_required'])
        && empty($_SESSION['face_verified_at'])
        && !empty($_SESSION['pending_face_user_id']);
}

function face_auth_get_stored_samples(mysqli $conn, int $userId): array
{
    face_auth_ensure_schema($conn);

    $stmt = $conn->prepare(
        "SELECT sample_label, descriptor, image_path
        FROM user_faces
        WHERE user_id = ?
          AND descriptor IS NOT NULL
          AND descriptor <> ''
        ORDER BY created_at ASC, id ASC"
    );

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $samples = [];

    while ($row = $result->fetch_assoc()) {
        $descriptor = json_decode((string) $row['descriptor'], true);
        if (is_array($descriptor) && count($descriptor) === 128) {
            $samples[] = [
                'sample_label' => $row['sample_label'] ?: 'sample',
                'descriptor' => array_map('floatval', $descriptor),
                'image_path' => $row['image_path'],
            ];
        }
    }

    $stmt->close();

    return $samples;
}

function face_auth_compare_descriptor_sets(array $probe, array $storedSamples): ?float
{
    if (count($probe) !== 128) {
        return null;
    }

    $best = null;

    foreach ($storedSamples as $sample) {
        $stored = $sample['descriptor'] ?? [];
        if (!is_array($stored) || count($stored) !== 128) {
            continue;
        }

        $sum = 0.0;
        for ($i = 0; $i < 128; $i++) {
            $delta = ((float) $probe[$i]) - ((float) $stored[$i]);
            $sum += $delta * $delta;
        }

        $distance = sqrt($sum);
        if ($best === null || $distance < $best) {
            $best = $distance;
        }
    }

    return $best;
}

function face_auth_count_recent_failures(mysqli $conn, int $userId, int $minutes = 15): int
{
    face_auth_ensure_schema($conn);

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
        FROM face_auth_logs
        WHERE user_id = ?
          AND event_type IN ('failure', 'locked', 'missing_camera')
          AND created_at >= (NOW() - INTERVAL ? MINUTE)"
    );

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('ii', $userId, $minutes);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) ($result['total'] ?? 0);
}

function face_auth_is_blocked(mysqli $conn, int $userId): bool
{
    return face_auth_count_recent_failures($conn, $userId) >= FACE_AUTH_MAX_ATTEMPTS;
}

function face_auth_log_attempt(mysqli $conn, int $userId, string $eventType, string $reason = '', ?float $distance = null): void
{
    face_auth_ensure_schema($conn);

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $conn->prepare(
        "INSERT INTO face_auth_logs (user_id, event_type, reason, match_distance, ip_address)
        VALUES (?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return;
    }

    $stmt->bind_param('issds', $userId, $eventType, $reason, $distance, $ipAddress);
    $stmt->execute();
    $stmt->close();
}

function face_auth_logout(): void
{
    face_auth_start_session();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}
