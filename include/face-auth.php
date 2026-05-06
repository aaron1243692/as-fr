<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function face_auth_ensure_schema(mysqli $conn): void
{
    static $schemaReady = false;

    if ($schemaReady) {
        return;
    }

    $requiredColumns = [
        "image_path" => "ALTER TABLE user_faces ADD COLUMN image_path VARCHAR(255) DEFAULT NULL",
        "descriptor" => "ALTER TABLE user_faces ADD COLUMN descriptor LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL",
        "failed_attempts" => "ALTER TABLE user_faces ADD COLUMN failed_attempts INT NOT NULL DEFAULT 0",
        "locked_until" => "ALTER TABLE user_faces ADD COLUMN locked_until DATETIME DEFAULT NULL",
        "last_attempt_at" => "ALTER TABLE user_faces ADD COLUMN last_attempt_at DATETIME DEFAULT NULL",
        "last_verified_at" => "ALTER TABLE user_faces ADD COLUMN last_verified_at DATETIME DEFAULT NULL",
    ];

    foreach ($requiredColumns as $column => $sql) {
        $safeColumn = $conn->real_escape_string($column);
        $result = $conn->query("SHOW COLUMNS FROM user_faces LIKE '{$safeColumn}'");

        if ($result && $result->num_rows === 0) {
            $conn->query($sql);
        }
    }

    $conn->query(
        "CREATE TABLE IF NOT EXISTS face_auth_logs (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) NOT NULL,
            status ENUM('success', 'failure', 'camera_error', 'blocked') NOT NULL,
            reason VARCHAR(255) DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_face_auth_logs_user (user_id),
            CONSTRAINT fk_face_auth_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    $index = $conn->query("SHOW INDEX FROM user_faces WHERE Key_name = 'uniq_user_face'");
    if ($index && $index->num_rows === 0) {
        $conn->query("ALTER TABLE user_faces ADD UNIQUE KEY uniq_user_face (user_id)");
    }

    $schemaReady = true;
}

function face_auth_decode_samples(?string $descriptorJson): array
{
    if (!$descriptorJson) {
        return [];
    }

    $decoded = json_decode($descriptorJson, true);
    if (!is_array($decoded)) {
        return [];
    }

    $valid = [];
    foreach ($decoded as $sample) {
        if (!is_array($sample)) {
            continue;
        }

        if (
            empty($sample['angle']) ||
            empty($sample['descriptor']) ||
            !is_array($sample['descriptor'])
        ) {
            continue;
        }

        $valid[] = [
            'angle' => (string) $sample['angle'],
            'descriptor' => array_map('floatval', $sample['descriptor']),
            'image' => isset($sample['image']) ? (string) $sample['image'] : null,
        ];
    }

    return $valid;
}

function face_auth_get_profile(mysqli $conn, int $userId): ?array
{
    face_auth_ensure_schema($conn);

    $stmt = $conn->prepare(
        "SELECT user_id, descriptor, failed_attempts, locked_until, last_attempt_at, last_verified_at
         FROM user_faces
         WHERE user_id = ?"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    $row['samples'] = face_auth_decode_samples($row['descriptor'] ?? null);
    return $row;
}

function face_auth_has_registered_face(mysqli $conn, int $userId): bool
{
    $profile = face_auth_get_profile($conn, $userId);
    return $profile !== null && count($profile['samples']) > 0;
}

function face_auth_begin_pending(int $userId, string $role): void
{
    $_SESSION['pending_face_user_id'] = $userId;
    $_SESSION['pending_face_role'] = $role;
    $_SESSION['face_verified'] = false;
    unset($_SESSION['id'], $_SESSION['role']);
}

function face_auth_complete(int $userId, string $role): void
{
    $_SESSION['id'] = $userId;
    $_SESSION['role'] = $role;
    $_SESSION['face_verified'] = true;
    $_SESSION['face_verified_at'] = date('Y-m-d H:i:s');
    unset($_SESSION['pending_face_user_id'], $_SESSION['pending_face_role']);
}

function face_auth_clear_state(): void
{
    unset(
        $_SESSION['id'],
        $_SESSION['role'],
        $_SESSION['face_verified'],
        $_SESSION['face_verified_at'],
        $_SESSION['pending_face_user_id'],
        $_SESSION['pending_face_role']
    );
}

function face_auth_is_pending(): bool
{
    return !empty($_SESSION['pending_face_user_id']) && empty($_SESSION['id']);
}

function face_auth_log(mysqli $conn, int $userId, string $status, ?string $reason = null): void
{
    face_auth_ensure_schema($conn);

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $conn->prepare(
        "INSERT INTO face_auth_logs (user_id, status, reason, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return;
    }

    $stmt->bind_param('issss', $userId, $status, $reason, $ip, $userAgent);
    $stmt->execute();
    $stmt->close();
}

function face_auth_register_failure(mysqli $conn, int $userId, string $reason, int $maxAttempts = 5, int $lockMinutes = 10): array
{
    face_auth_ensure_schema($conn);
    face_auth_log($conn, $userId, 'failure', $reason);

    $stmt = $conn->prepare(
        "UPDATE user_faces
         SET failed_attempts = failed_attempts + 1,
             last_attempt_at = NOW(),
             locked_until = CASE
                 WHEN failed_attempts + 1 >= ? THEN DATE_ADD(NOW(), INTERVAL ? MINUTE)
                 ELSE locked_until
             END
         WHERE user_id = ?"
    );

    $stmt->bind_param('iii', $maxAttempts, $lockMinutes, $userId);
    $stmt->execute();
    $stmt->close();

    $profile = face_auth_get_profile($conn, $userId);
    $attempts = (int) ($profile['failed_attempts'] ?? 0);
    $lockedUntil = $profile['locked_until'] ?? null;

    return [
        'attempts' => $attempts,
        'remaining' => max(0, $maxAttempts - $attempts),
        'locked_until' => $lockedUntil,
    ];
}

function face_auth_reset_failures(mysqli $conn, int $userId): void
{
    face_auth_ensure_schema($conn);

    $stmt = $conn->prepare(
        "UPDATE user_faces
         SET failed_attempts = 0,
             locked_until = NULL,
             last_attempt_at = NOW(),
             last_verified_at = NOW()
         WHERE user_id = ?"
    );

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    face_auth_log($conn, $userId, 'success', 'Face verification passed');
}

function face_auth_is_locked(?array $profile): bool
{
    if (!$profile || empty($profile['locked_until'])) {
        return false;
    }

    return strtotime((string) $profile['locked_until']) > time();
}

function face_auth_enforce_page_access(): void
{
    $page = basename($_SERVER['PHP_SELF'] ?? '');

    if (face_auth_is_pending() && $page !== 'face-recognition.php') {
        header('Location: face-recognition.php');
        exit;
    }

    if (empty($_SESSION['id'])) {
        header('Location: index.php');
        exit;
    }
}
