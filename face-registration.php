<?php
    include 'include/session.php';

    $enrollments = face_auth_get_stored_samples($conn, (int) $_SESSION['id']);
    $savedViews = array_map(
        static fn(array $sample): string => (string) ($sample['sample_label'] ?? 'sample'),
        $enrollments
    );
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

<body class="vh-100 bg-gray-200 flex flex-row justify-content-center">
    <?php include 'include/aside.php'; ?>

    <main class="flex flex-1 flex-col h-full align-center gap-2 p-2">
        <?php include 'include/header.php'; ?>

        <section class="flex flex-1 flex-col gap-4 rounded-4 bg-white p-4 shadow">
            <div class="flex flex-col gap-2">
                <h2 class="text-2xl font-semibold">Face Registration</h2>
                <p class="text-sm text-gray-600">Capture one clear front view of your face. The system stores a front-face embedding and reference image for later verification.</p>
                <p class="text-sm text-gray-500">Current saved views: <?= count($savedViews) ? htmlspecialchars(implode(', ', $savedViews)) : 'none' ?></p>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1.2fr_1fr]">
                <div class="rounded-4 border bg-black p-3">
                    <video id="video" autoplay playsinline muted class="h-[24rem] w-full rounded-3 object-cover"></video>
                </div>

                <div class="flex flex-col gap-3">
                    <div id="statusBox" class="rounded-3 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                        Loading camera and face models...
                    </div>

                    <div class="grid grid-cols-1 gap-3">
                        <div class="rounded-3 border p-3">
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="font-semibold">Front</h3>
                                <span id="badge-front" class="rounded-full bg-gray-200 px-3 py-1 text-xs text-gray-700">Pending</span>
                            </div>
                            <p class="text-sm text-gray-600">Look straight at the camera.</p>
                            <img id="preview-front" class="mt-3 hidden h-28 w-full rounded-3 object-cover" alt="Front preview">
                            <button type="button" class="capture-btn mt-3 rounded-3 bg-slate-900 px-4 py-2 text-sm font-semibold text-white" data-angle="front">Capture Front</button>
                        </div>
                    </div>
                </div>
            </div>

            <form id="registrationForm" action="config/face-registration.php" method="post" class="flex items-center justify-between gap-3">
                <input type="hidden" name="face_payload" id="face_payload">
                <p class="text-sm text-gray-500">One front-facing sample is required before you can save.</p>
                <button type="submit" id="saveBtn" class="rounded-3 bg-blue-600 px-5 py-2 font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50" disabled>Save Face Registration</button>
            </form>
        </section>
    </main>

<script>
const video = document.getElementById('video');
const statusBox = document.getElementById('statusBox');
const saveBtn = document.getElementById('saveBtn');
const payloadInput = document.getElementById('face_payload');
const captureButtons = document.querySelectorAll('.capture-btn');
const captures = {};
let modelsLoaded = false;
let streamStarted = false;

const angleRules = {
    front: { min: -0.03, max: 0.03 }
};

function setStatus(message, type = 'info') {
    const classes = {
        info: 'bg-blue-50 text-blue-700',
        success: 'bg-green-50 text-green-700',
        warning: 'bg-amber-50 text-amber-700',
        error: 'bg-red-50 text-red-700'
    };
    statusBox.className = `rounded-3 px-4 py-3 text-sm ${classes[type] || classes.info}`;
    statusBox.textContent = message;
}

function updateSaveState() {
    const ready = Boolean(captures.front);
    saveBtn.disabled = !ready;
    payloadInput.value = ready ? JSON.stringify(captures) : '';
}

function getYaw(landmarks) {
    const jaw = landmarks.getJawOutline();
    const nose = landmarks.getNose();
    const left = jaw[0].x;
    const right = jaw[16].x;
    const center = (left + right) / 2;
    const width = Math.max(1, right - left);
    return (nose[3].x - center) / width;
}

async function loadModels() {
    if (modelsLoaded) {
        return;
    }

    await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri('models'),
        faceapi.nets.faceLandmark68Net.loadFromUri('models'),
        faceapi.nets.faceRecognitionNet.loadFromUri('models')
    ]);

    modelsLoaded = true;
    setStatus('Models loaded. Capture one front-facing face view.', 'success');
}

async function startCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        setStatus('No camera was detected. Face registration cannot continue.', 'error');
        return;
    }

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'user'
            },
            audio: false
        });

        video.srcObject = stream;
        streamStarted = true;
    } catch (error) {
        setStatus('Camera access is required for face registration.', 'error');
    }
}

async function captureAngle(angle) {
    if (!streamStarted || !modelsLoaded) {
        setStatus('The camera or models are still loading. Please wait a moment.', 'warning');
        return;
    }

    const detection = await faceapi
        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptor();

    if (!detection) {
        setStatus('No face detected. Center yourself in the frame and try again.', 'error');
        return;
    }

    const yaw = getYaw(detection.landmarks);
    const rule = angleRules[angle];
    if (yaw < rule.min || yaw > rule.max) {
        setStatus('Pose does not match a front-facing capture yet. Look straight at the camera and try again.', 'warning');
        return;
    }

    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    const context = canvas.getContext('2d');
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    const image = canvas.toDataURL('image/png');

    captures[angle] = {
        descriptor: Array.from(detection.descriptor),
        image
    };

    const preview = document.getElementById(`preview-${angle}`);
    const badge = document.getElementById(`badge-${angle}`);
    preview.src = image;
    preview.classList.remove('hidden');
    badge.textContent = 'Captured';
    badge.className = 'rounded-full bg-green-100 px-3 py-1 text-xs text-green-700';
    setStatus(`${angle.charAt(0).toUpperCase() + angle.slice(1)} view captured.`, 'success');
    updateSaveState();
}

captureButtons.forEach((button) => {
    button.addEventListener('click', async () => {
        try {
            await captureAngle(button.dataset.angle);
        } catch (error) {
            setStatus('Unable to capture a valid face sample. Please try again.', 'error');
        }
    });
});

document.getElementById('registrationForm').addEventListener('submit', (event) => {
    updateSaveState();
    if (!payloadInput.value) {
        event.preventDefault();
        setStatus('A front-facing face sample is required before saving.', 'warning');
    }
});

window.addEventListener('load', async () => {
    try {
        await startCamera();
        await loadModels();
    } catch (error) {
        setStatus('Unable to initialize face registration.', 'error');
    }
});
</script>
</body>
</html>
