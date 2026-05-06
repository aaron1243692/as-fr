<?php
include 'connection.php';
include 'face-auth.php';
session_start();

face_auth_ensure_schema($conn);

if (empty($_SESSION['id'])) {
    $_SESSION['message'] = 'Sign in first before registering a face.';
    header("location: ../sign-in.php");
    exit;
}

$userId = (int) $_SESSION['id'];
$requiredAngles = ['front'];

$payloadRaw = $_POST['face_payload'] ?? '';
$payload = json_decode($payloadRaw, true);

if (!is_array($payload)) {
    $descriptorsRaw = $_POST['descriptors'] ?? '';
    $descriptors = json_decode($descriptorsRaw, true);
    $payload = [];

    if (is_array($descriptors)) {
        foreach ($descriptors as $angle => $descriptor) {
            $payload[$angle] = [
                'descriptor' => $descriptor,
                'image' => null
            ];
        }
    }
}

if (!is_array($payload)) {
    $_SESSION['message'] = "Invalid face registration payload.";
    header("location: ../face-registration.php");
    exit;
}

$samples = [];
$userUploadDir = dirname(__DIR__) . '/uploads/faces/' . $userId;
$relativeBaseDir = 'uploads/faces/' . $userId;

if (!is_dir($userUploadDir) && !mkdir($userUploadDir, 0775, true) && !is_dir($userUploadDir)) {
    $_SESSION['message'] = "Unable to prepare face storage directory.";
    header("location: ../face-registration.php");
    exit;
}

foreach ($requiredAngles as $angle) {
    $sample = $payload[$angle] ?? null;
    $descriptor = is_array($sample) ? ($sample['descriptor'] ?? null) : null;
    $image = is_array($sample) ? ($sample['image'] ?? null) : null;

    if (!is_array($descriptor) || count($descriptor) !== 128) {
        $_SESSION['message'] = "A front-facing sample with a valid embedding is required.";
        header("location: ../face-registration.php");
        exit;
    }

    $normalized = array_map('floatval', $descriptor);
    $descriptorJson = json_encode($normalized, JSON_UNESCAPED_SLASHES);

    if ($descriptorJson === false) {
        $_SESSION['message'] = "Unable to encode face embedding for {$angle}.";
        header("location: ../face-registration.php");
        exit;
    }

    $relativePath = null;
    $binaryImage = null;
    if (is_string($image) && preg_match('/^data:image\/png;base64,(.+)$/', $image, $matches)) {
        $binaryImage = base64_decode($matches[1], true);
        if ($binaryImage === false) {
            $_SESSION['message'] = "Invalid image capture received for {$angle}.";
            header("location: ../face-registration.php");
            exit;
        }

        $fileName = $angle . '-' . time() . '.png';
        $absolutePath = $userUploadDir . '/' . $fileName;
        if (file_put_contents($absolutePath, $binaryImage) === false) {
            $_SESSION['message'] = "Unable to store face reference image for {$angle}.";
            header("location: ../face-registration.php");
            exit;
        }

        $relativePath = $relativeBaseDir . '/' . $fileName;
    }

    $samples[] = [
        'label' => $angle,
        'descriptor' => $descriptorJson,
        'image_path' => $relativePath
    ];
}

$conn->begin_transaction();

try {
    $delete = $conn->prepare("DELETE FROM user_faces WHERE user_id = ?");
    if (!$delete) {
        throw new RuntimeException('Unable to reset existing face samples.');
    }

    $delete->bind_param('i', $userId);
    $delete->execute();
    $delete->close();

    $insert = $conn->prepare("
        INSERT INTO user_faces (user_id, sample_label, face_data, image_path, descriptor)
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$insert) {
        throw new RuntimeException('Unable to prepare face sample storage.');
    }

    foreach ($samples as $sample) {
        $label = $sample['label'];
        $faceData = null;
        $imagePath = $sample['image_path'];
        $descriptorJson = $sample['descriptor'];

        $insert->bind_param('issss', $userId, $label, $faceData, $imagePath, $descriptorJson);
        if (!$insert->execute()) {
            throw new RuntimeException('Unable to save one or more face samples.');
        }
    }

    $insert->close();
    $conn->commit();

    $_SESSION['face_verified_at'] = time();
    $_SESSION['message'] = "Face registered successfully.";
} catch (Throwable $exception) {
    $conn->rollback();
    $_SESSION['message'] = $exception->getMessage();
}

header("location: ../face-registration.php");
exit;
?>
