@extends('layouts.app')

@section('page_title', 'My Profile')

@push('styles')
<style>
    .camera-surface {
        width: 100%;
        max-width: 320px;
        aspect-ratio: 1 / 1;
        border-radius: 1rem;
        background: #f3f6fb;
        border: 1px dashed #b9c6db;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .camera-surface video,
    .camera-surface canvas,
    .camera-surface img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>
@endpush

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center p-4">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-light border" style="width: 132px; height: 132px; overflow: hidden;">
                    @if($user->profilePhotoUrl())
                        <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <span class="fw-bold fs-2 text-secondary">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    @endif
                </div>
                <h2 class="h4 mb-1">{{ $user->name }}</h2>
                <p class="text-muted mb-2">{{ $user->role }}</p>
                <p class="text-muted small mb-0">Keep your contact details and profile photo current so account recovery and team identification stay accurate.</p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="mb-4">
                    <h2 class="h4 mb-1">Update Profile Information</h2>
                    <p class="text-muted mb-0">Edit your personal details and upload a profile picture.</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="captured_profile_photo" id="captured_profile_photo" value="{{ old('captured_profile_photo') }}">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name</label>
                            <input id="name" type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="username" class="form-label">Username</label>
                            <input id="username" type="text" name="username" class="form-control" value="{{ old('username', $user->username) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input id="email" type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" placeholder="user@example.com">
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input id="phone" type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="+211...">
                        </div>

                        <div class="col-12">
                            <label for="profile_photo" class="form-label">Profile Picture</label>
                            <input id="profile_photo" type="file" name="profile_photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            <div class="form-text">Accepted formats: JPG, PNG, or WEBP. Max size: 2MB.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Capture with Camera</label>
                            <div class="camera-surface mb-3">
                                <video id="cameraPreview" class="d-none" autoplay playsinline muted></video>
                                <canvas id="cameraCanvas" class="d-none"></canvas>
                                <img
                                    id="capturedPreview"
                                    class="{{ old('captured_profile_photo') ? '' : 'd-none' }}"
                                    src="{{ old('captured_profile_photo') ?: '' }}"
                                    alt="Captured profile preview"
                                >
                                <span id="cameraPlaceholder" class="text-muted text-center px-3 {{ old('captured_profile_photo') ? 'd-none' : '' }}">
                                    Start the camera to take a selfie from this device.
                                </span>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-primary" id="startCameraButton">Start Camera</button>
                                <button type="button" class="btn btn-primary d-none" id="capturePhotoButton">Take Selfie</button>
                                <button type="button" class="btn btn-outline-secondary d-none" id="retakePhotoButton">Retake</button>
                                <button type="button" class="btn btn-outline-danger d-none" id="stopCameraButton">Stop Camera</button>
                            </div>
                            <div class="form-text">You can upload an existing picture or capture one directly from your phone or laptop camera.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="{{ $user->role }}" disabled>
                            <div class="form-text">Role changes remain controlled through user management.</div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save Profile</button>
                        <a href="{{ route('password.change.edit') }}" class="btn btn-outline-secondary">Change Password</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var startButton = document.getElementById('startCameraButton');
        var captureButton = document.getElementById('capturePhotoButton');
        var retakeButton = document.getElementById('retakePhotoButton');
        var stopButton = document.getElementById('stopCameraButton');
        var video = document.getElementById('cameraPreview');
        var canvas = document.getElementById('cameraCanvas');
        var preview = document.getElementById('capturedPreview');
        var placeholder = document.getElementById('cameraPlaceholder');
        var hiddenInput = document.getElementById('captured_profile_photo');
        var fileInput = document.getElementById('profile_photo');
        var stream = null;

        if (!startButton || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            if (startButton) {
                startButton.disabled = true;
                startButton.textContent = 'Camera Not Available';
            }
            return;
        }

        function showPlaceholder() {
            placeholder.classList.remove('d-none');
            video.classList.add('d-none');
            canvas.classList.add('d-none');
            preview.classList.add('d-none');
        }

        async function startCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user' },
                    audio: false
                });

                video.srcObject = stream;
                video.classList.remove('d-none');
                preview.classList.add('d-none');
                canvas.classList.add('d-none');
                placeholder.classList.add('d-none');
                captureButton.classList.remove('d-none');
                stopButton.classList.remove('d-none');
                retakeButton.classList.add('d-none');
            } catch (error) {
                placeholder.textContent = 'Camera access was blocked or is unavailable on this device.';
                showPlaceholder();
            }
        }

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(function (track) {
                    track.stop();
                });
                stream = null;
            }

            video.srcObject = null;
            captureButton.classList.add('d-none');
            stopButton.classList.add('d-none');
        }

        function capturePhoto() {
            if (!video.srcObject) {
                return;
            }

            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 640;

            var context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            var dataUrl = canvas.toDataURL('image/jpeg', 0.92);
            hiddenInput.value = dataUrl;
            preview.src = dataUrl;
            preview.classList.remove('d-none');
            video.classList.add('d-none');
            canvas.classList.add('d-none');
            placeholder.classList.add('d-none');
            retakeButton.classList.remove('d-none');
            fileInput.value = '';
            stopCamera();
        }

        startButton.addEventListener('click', startCamera);
        captureButton.addEventListener('click', capturePhoto);
        stopButton.addEventListener('click', function () {
            stopCamera();
            if (!hiddenInput.value) {
                showPlaceholder();
            }
        });
        retakeButton.addEventListener('click', function () {
            hiddenInput.value = '';
            preview.src = '';
            retakeButton.classList.add('d-none');
            startCamera();
        });
        fileInput.addEventListener('change', function () {
            if (fileInput.files.length > 0) {
                hiddenInput.value = '';
                preview.src = '';
                retakeButton.classList.add('d-none');
                stopCamera();
                showPlaceholder();
            }
        });

        if (!hiddenInput.value) {
            showPlaceholder();
        } else {
            retakeButton.classList.remove('d-none');
            placeholder.classList.add('d-none');
        }

        window.addEventListener('beforeunload', stopCamera);
    })();
</script>
@endpush
