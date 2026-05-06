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
                    <p class="text-gray-600 mb-0">Your password is correct. Complete the live face check before entering the system.</p>
                </div>
                <a href="logout.php" class="px-4 py-2 rounded bg-gray-800 text-white hover:bg-black transition">Cancel</a>
            </div>

            <div class="grid lg:grid-cols-2 gap-4 items-start">
                <div class="bg-gray-900 rounded-xl overflow-hidden aspect-square flex items-center justify-center">
                    <video id="video" autoplay muted playsinline class="w-full h-full object-cover"></video>
                </div>

                <div class="flex flex-col gap-4">
                    <div id="statusBox" class="rounded border border-blue-200 bg-blue-50 text-blue-700 px-4 py-3">
                        Preparing camera and face verification...
                    </div>

                    <div class="rounded border p-4 bg-gray-50">
                        <p class="font-semibold text-gray-800 mb-2">Liveness checklist</p>
                        <div class="grid gap-2 text-sm">
                            <div id="check-front" class="text-gray-600">Hold a front-facing pose</div>
                            <div id="check-blink" class="text-gray-600">Blink once</div>
                        </div>
                    </div>

                    <div class="rounded border p-4">
                        <p class="font-semibold text-gray-800 mb-1">Attempts remaining</p>
                        <p id="attemptsBox" class="text-lg text-gray-700"><?php echo $attemptsRemaining; ?> of <?php echo FACE_AUTH_MAX_ATTEMPTS; ?></p>
                        <p class="text-sm text-gray-500 mb-0">Failed checks are limited and logged.</p>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" id="verifyBtn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Verify Face
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
const verifyBtn = document.getElementById('verifyBtn');
const attemptsBox = document.getElementById('attemptsBox');

const checks = {
    front: document.getElementById('check-front'),
    blink: document.getElementById('check-blink')
};

let modelsLoaded = false;
let cameraReady = false;
let currentDescriptor = null;
let blinkClosed = false;
let verificationBusy = false;
let attemptsRemaining = <?php echo $attemptsRemaining; ?>;
const liveness = { front: false, blink: false };

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
    checks.front.className = liveness.front ? 'text-green-600 font-semibold' : 'text-gray-600';
    checks.blink.className = liveness.blink ? 'text-green-600 font-semibold' : 'text-gray-600';
    verifyBtn.disabled = !(modelsLoaded && cameraReady && currentDescriptor && liveness.front && liveness.blink) || verificationBusy || attemptsRemaining <= 0;
}

function updateAttempts() {
    attemptsBox.textContent = `${attemptsRemaining} of ${MAX_ATTEMPTS}`;
}

function distance(pointA, pointB) {
    return Math.hypot(pointA.x - pointB.x, pointA.y - pointB.y);
}

function eyeAspectRatio(points) {
    const vertical1 = distance(points[1], points[5]);
    const vertical2 = distance(points[2], points[4]);
    const horizontal = distance(points[0], points[3]);
    return (vertical1 + vertical2) / (2.0 * Math.max(horizontal, 1));
}

function getYawRatio(detection) {
    const jaw = detection.landmarks.getJawOutline();
    const nose = detection.landmarks.getNose();
    const faceLeft = jaw[0].x;
    const faceRight = jaw[16].x;
    const faceCenter = (faceLeft + faceRight) / 2;
    const noseCenter = nose[3].x;
    const faceWidth = Math.max(faceRight - faceLeft, 1);
    return (noseCenter - faceCenter) / faceWidth;
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
        setStatus('Face not detected. Keep your face centered in the camera.', 'warn');
        updateChecks();
        return;
    }

    currentDescriptor = Array.from(detection.descriptor);

    const yaw = getYawRatio(detection);
    liveness.front = yaw >= -0.05 && yaw <= 0.05;

    const leftEAR = eyeAspectRatio(detection.landmarks.getLeftEye());
    const rightEAR = eyeAspectRatio(detection.landmarks.getRightEye());
    const averageEAR = (leftEAR + rightEAR) / 2;

    if (averageEAR < 0.21) {
        blinkClosed = true;
    }
    if (blinkClosed && averageEAR > 0.26) {
        liveness.blink = true;
        blinkClosed = false;
    }

    if (liveness.front && liveness.blink) {
        setStatus('Front-face and blink checks completed. You can verify your face now.', 'success');
    } else {
        setStatus('Look straight at the camera and blink once to unlock verification.', 'info');
    }

    updateChecks();
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

        setStatus(result.message || 'Face verification failed.', 'error');
    } catch (error) {
        setStatus('Connection error while verifying your face.', 'error');
    } finally {
        verificationBusy = false;
        updateChecks();
    }
}

verifyBtn.addEventListener('click', verifyFace);

window.addEventListener('load', async () => {
    updateAttempts();
    updateChecks();
    await startCamera();
    if (!cameraReady) {
        return;
    }

    try {
        await loadModels();
        setStatus('Camera ready. Look straight at the camera and blink once.', 'info');
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
