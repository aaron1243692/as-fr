<?php
session_start();
include 'config/connection.php';
include 'config/face-auth.php';

face_auth_ensure_schema($conn);

if (empty($_SESSION['id'])) {
    header('location: sign-in.php');
    exit;
}

if (!face_auth_is_pending()) {
    header('location: dashboard.php');
    exit;
}

$userId = (int) $_SESSION['id'];

if (!face_auth_user_has_registered_face($conn, $userId)) {
    face_auth_clear_pending();
    $_SESSION['message'] = 'No registered face was found for this account, so face recognition was skipped.';
    header('location: dashboard.php');
    exit;
}

$attemptsUsed = (int) ($_SESSION['face_failed_attempts'] ?? 0);
$attemptsRemaining = max(0, FACE_AUTH_MAX_ATTEMPTS - $attemptsUsed);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'include/title.php';?>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
</head>

<body class="min-h-screen bg-gray-200 flex items-center justify-center p-4">
    <main class="w-full max-w-6xl bg-white rounded-2xl shadow-lg p-4 md:p-6">
        <div class="flex flex-col gap-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Face Verification Required</h2>
                    <p class="text-gray-600 mb-0">Your password is correct. The system will capture and verify your face automatically as soon as it is detected.</p>
                </div>
                <a href="logout.php" class="px-4 py-2 rounded bg-gray-800 text-white hover:bg-black transition">Cancel</a>
            </div>

            <div class="grid lg:grid-cols-2 gap-4 items-start">
                <div class="bg-gray-900 rounded-xl overflow-hidden aspect-square flex items-center justify-center">
                    <video id="video" autoplay muted playsinline class="w-full h-full object-cover"></video>
                </div>

                <div class="flex flex-col gap-4">
                    <div id="statusBox" class="rounded border border-blue-200 bg-blue-50 text-blue-700 px-4 py-3">
                        Preparing camera and automatic face verification...
                    </div>

                    <div class="rounded border p-4 bg-gray-50">
                        <p class="font-semibold text-gray-800 mb-2">Detection status</p>
                        <div class="grid gap-2 text-sm">
                            <div id="check-face" class="text-gray-600">Face detected</div>
                        </div>
                    </div>

                    <div class="rounded border p-4">
                        <p class="font-semibold text-gray-800 mb-1">Attempts remaining</p>
                        <p id="attemptsBox" class="text-lg text-gray-700"><?php echo $attemptsRemaining; ?> of <?php echo FACE_AUTH_MAX_ATTEMPTS; ?></p>
                        <p class="text-sm text-gray-500 mb-0">Failed checks are limited and logged.</p>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" id="retryBtn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                            Retry Scan
                        </button>
                        <a href="logout.php" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition">Log Out</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

<script>
const MODEL_PATH = 'models';
const MAX_ATTEMPTS = <?php echo FACE_AUTH_MAX_ATTEMPTS; ?>;
const video = document.getElementById('video');
const statusBox = document.getElementById('statusBox');
const retryBtn = document.getElementById('retryBtn');
const attemptsBox = document.getElementById('attemptsBox');

const checks = {
    face: document.getElementById('check-face')
};

let modelsLoaded = false;
let cameraReady = false;
let currentDescriptor = null;
let verificationBusy = false;
let attemptsRemaining = <?php echo $attemptsRemaining; ?>;
let awaitingFaceReset = false;
const liveness = { front: true, blink: true };

function setStatus(message, tone = 'info') {
    const classMap = {
        info: 'border-blue-200 bg-blue-50 text-blue-700',
        success: 'border-green-200 bg-green-50 text-green-700',
        error: 'border-red-200 bg-red-50 text-red-700',
        warn: 'border-yellow-200 bg-yellow-50 text-yellow-700'
    };

    statusBox.className = `rounded border px-4 py-3 ${classMap[tone] || classMap.info}`;
    statusBox.textContent = message;
}

function updateChecks() {
    checks.face.className = currentDescriptor ? 'text-green-600 font-semibold' : 'text-gray-600';
    retryBtn.disabled = verificationBusy;
}

function updateAttempts() {
    attemptsBox.textContent = `${attemptsRemaining} of ${MAX_ATTEMPTS}`;
}

async function loadModels() {
    await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_PATH),
        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_PATH),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_PATH)
    ]);
    modelsLoaded = true;
}

async function notifyCameraError(reason) {
    try {
        const response = await fetch('config/face-login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'camera_error', reason })
        });
        const result = await response.json();
        if (result && result.redirect) {
            setTimeout(() => {
                window.location.href = result.redirect;
            }, 1800);
        }
    } catch (error) {
    }
}

async function startCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        setStatus('No camera was detected. Login is blocked until a camera is available.', 'error');
        await notifyCameraError('No camera detected by browser');
        return;
    }

    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 480, height: 480 } });
        video.srcObject = stream;
        cameraReady = true;
    } catch (error) {
        setStatus('Camera access was denied. Login is blocked until camera access is granted.', 'error');
        await notifyCameraError('Camera access denied');
    }
}

async function monitorLiveness() {
    if (!modelsLoaded || !cameraReady || verificationBusy) {
        return;
    }

    const detection = await faceapi
        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptor();

    if (!detection) {
        currentDescriptor = null;
        if (awaitingFaceReset) {
            awaitingFaceReset = false;
            setStatus('Face cleared. Return to the frame to retry.', 'info');
        } else {
            setStatus('Face not detected. Keep your face centered in the camera.', 'warn');
        }
        updateChecks();
        return;
    }

    currentDescriptor = Array.from(detection.descriptor);
    updateChecks();

    if (awaitingFaceReset || attemptsRemaining <= 0) {
        return;
    }

    setStatus('Face detected. Verifying automatically...', 'success');
    await verifyFace();
}

async function verifyFace() {
    if (!currentDescriptor || verificationBusy) {
        return;
    }

    verificationBusy = true;
    updateChecks();
    setStatus('Verifying face data...', 'info');

    try {
        const response = await fetch('config/face-login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'verify',
                descriptor: currentDescriptor,
                liveness
            })
        });

        const result = await response.json();
        attemptsRemaining = typeof result.attempts_remaining === 'number' ? result.attempts_remaining : attemptsRemaining;
        updateAttempts();

        if (result.success) {
            setStatus('Face verified. Redirecting...', 'success');
            window.location.href = result.redirect || 'dashboard.php';
            return;
        }

        if (result.locked) {
            setStatus(result.message || 'Face verification is locked.', 'error');
            setTimeout(() => {
                window.location.href = result.redirect || 'sign-in.php';
            }, 1500);
            return;
        }

        awaitingFaceReset = true;
        setStatus(result.message || 'Face verification failed.', 'error');
    } catch (error) {
        setStatus('Connection error while verifying your face.', 'error');
    } finally {
        verificationBusy = false;
        updateChecks();
    }
}

retryBtn.addEventListener('click', () => {
    currentDescriptor = null;
    awaitingFaceReset = false;
    updateChecks();
    setStatus('Retry ready. Look at the camera to scan again.', 'info');
});

window.addEventListener('load', async () => {
    updateAttempts();
    updateChecks();
    await startCamera();
    if (!cameraReady) {
        return;
    }

    try {
        await loadModels();
        setStatus('Camera ready. Look at the camera and the system will verify automatically.', 'info');
        updateChecks();
        setInterval(() => {
            monitorLiveness().catch(() => {
                setStatus('Unable to read the current camera frame.', 'error');
            });
        }, 500);
    } catch (error) {
        setStatus('Face recognition models could not be loaded.', 'error');
    }
});
</script>
</body>
</html>
