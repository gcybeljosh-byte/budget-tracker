<?php
$pageTitle = 'Profile';
$pageHeader = 'My Profile';
include '../includes/header.php';
?>
<!-- Cropper.js -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">

<?php include '../includes/sidebar.php'; ?>

<!-- Page Content -->
<div id="page-content-wrapper">

    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div id="alertContainer"></div>

        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8 col-xl-7">
                <!-- Personal Information Card -->
                <div class="card border-0 shadow-sm rounded-4 position-relative">
                    <button type="button" id="btnToggleEdit" class="btn btn-sm btn-light text-primary position-absolute top-0 end-0 m-3 rounded-pill shadow-sm fw-bold">
                        <i class="fas fa-pen me-1"></i>Edit Profile
                    </button>
                    <div class="card-header bg-white border-0 pt-4 pb-0 text-center">
                        <div class="position-relative mx-auto mb-3" style="width: 100px; height: 100px;">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center overflow-hidden h-100 w-100">
                                <img id="profileImagePreview" src="" alt="Profile" class="d-none" style="width: 100%; height: 100%; object-fit: cover;">
                                <i id="defaultProfileIcon" class="fas fa-user" style="font-size: 2.5rem;"></i>
                            </div>
                            <label for="profileUpload" id="profileUploadLabel" class="position-absolute bottom-0 end-0 bg-white rounded-circle shadow p-2 d-none" style="cursor: pointer; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-camera text-primary small"></i>
                            </label>
                            <input type="file" id="profileUpload" name="profile_picture" class="d-none" accept="image/*">
                        </div>
                        <h4 class="mb-0 fw-bold" id="displayFullName">Loading...</h4>
                        <p class="text-muted" id="displayUsername">@loading</p>
                    </div>
                    <div class="card-body p-4">
                        <form id="profileForm">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small text-uppercase fw-bold">First Name</label>
                                    <input type="text" class="form-control" id="firstName" name="first_name" required disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small text-uppercase fw-bold">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" name="last_name" required disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small text-uppercase fw-bold">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-secondary small text-uppercase fw-bold">Contact Number</label>
                                    <input type="text" class="form-control" id="contactNumber" name="contact_number" required disabled>
                                </div>
                                <div class="col-12 mt-4 d-none" id="actionButtons">
                                    <div class="d-flex gap-2">
                                        <button type="button" id="btnCancelEdit" class="btn btn-outline-secondary w-50 py-2 fw-bold">Cancel</button>
                                        <button type="submit" class="btn btn-primary w-50 py-2 fw-bold shadow-sm">
                                            <i class="fas fa-save me-2"></i>Save Changes
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</div>

<!-- Cropper.js Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-labelledby="cropModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="cropModalLabel">Crop Your Profile Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="img-container mb-3" style="max-height: 500px; overflow: hidden;">
                    <img id="imageToCrop" src="" alt="Sizing..." style="max-width: 100%;">
                </div>
                <p class="text-muted small mb-0"><i class="fas fa-info-circle me-1"></i> Drag to move, scroll to zoom. We'll save the square crop for your profile.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="btnCropAndSave" class="btn btn-primary rounded-pill px-4 fw-bold">Crop & Save</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const profileForm = document.getElementById('profileForm');

        const btnToggleEdit = document.getElementById('btnToggleEdit');
        const btnCancelEdit = document.getElementById('btnCancelEdit');
        const actionButtons = document.getElementById('actionButtons');
        const profileUploadLabel = document.getElementById('profileUploadLabel');
        const formInputs = profileForm.querySelectorAll('input');

        let originalData = {};

        function toggleEditMode(enable) {
            profileForm.querySelectorAll('input').forEach(input => {
                if (input.type !== 'file') input.disabled = !enable;
            });

            if (enable) {
                // Store original data to restore on cancel
                profileForm.querySelectorAll('input').forEach(input => originalData[input.id] = input.value);

                actionButtons.classList.remove('d-none');
                profileUploadLabel.classList.remove('d-none');
                btnToggleEdit.classList.add('d-none');
            } else {
                actionButtons.classList.add('d-none');
                profileUploadLabel.classList.add('d-none');
                btnToggleEdit.classList.remove('d-none');
            }
        }

        btnToggleEdit.addEventListener('click', () => toggleEditMode(true));

        btnCancelEdit.addEventListener('click', () => {
            toggleEditMode(false);
            // Restore original values
            for (const [id, value] of Object.entries(originalData)) {
                const el = document.getElementById(id);
                if (el) el.value = value;
            }
        });

        // Fetch current profile data
        fetch('<?php echo SITE_URL; ?>api/profile.php')
            .then(response => response.json())
            .then(result => {
                if (result.success && result.data) {
                    const user = result.data;
                    document.getElementById('firstName').value = user.first_name || '';
                    document.getElementById('lastName').value = user.last_name || '';
                    document.getElementById('email').value = user.email || '';
                    document.getElementById('contactNumber').value = user.contact_number || '';
                    document.getElementById('displayFullName').textContent = `${user.first_name || ''} ${user.last_name || ''}`;
                    document.getElementById('displayUsername').textContent = `@${user.username}`;

                    // Save initial state for cancel
                    originalData = {
                        firstName: user.first_name,
                        lastName: user.last_name,
                        email: user.email,
                        contactNumber: user.contact_number
                    };

                    if (user.profile_picture) {
                        document.getElementById('profileImagePreview').src = '<?php echo SITE_URL; ?>' + user.profile_picture;
                        document.getElementById('profileImagePreview').classList.remove('d-none');
                        document.getElementById('defaultProfileIcon').classList.add('d-none');
                    }
                }
            })
            .catch(err => console.error('Error fetching profile:', err));

        // Live Preview & Cropping on File Select
        const profileUpload = document.getElementById('profileUpload');
        const cropModal = new bootstrap.Modal(document.getElementById('cropModal'));
        const imageToCrop = document.getElementById('imageToCrop');
        let cropper;
        let croppedBlob = null;

        profileUpload.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const reader = new FileReader();
                reader.onload = function(event) {
                    imageToCrop.src = event.target.result;
                    cropModal.show();
                }
                reader.readAsDataURL(file);
            }
        });

        // Initialize Cropper when modal is shown
        document.getElementById('cropModal').addEventListener('shown.bs.modal', function() {
            cropper = new Cropper(imageToCrop, {
                aspectRatio: 1,
                viewMode: 2,
                dragMode: 'move',
                autoCropArea: 1,
                restore: false,
                guides: false,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
            });
        });

        // Destroy Cropper when modal is hidden
        document.getElementById('cropModal').addEventListener('hidden.bs.modal', function() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            // Clear file input if no blob saved
            if (!croppedBlob) {
                profileUpload.value = '';
            }
        });

        document.getElementById('btnCropAndSave').addEventListener('click', function() {
            if (!cropper) return;

            const canvas = cropper.getCroppedCanvas({
                width: 500,
                height: 500
            });

            canvas.toBlob((blob) => {
                croppedBlob = blob;

                // Update Preview
                const previewUrl = URL.createObjectURL(blob);
                const profilePreview = document.getElementById('profileImagePreview');
                const defaultIcon = document.getElementById('defaultProfileIcon');

                profilePreview.src = previewUrl;
                profilePreview.classList.remove('d-none');
                defaultIcon.classList.add('d-none');

                cropModal.hide();
            }, 'image/jpeg');
        });

        // Update profile (Personal Info)
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'info');

            // Use cropped blob if available
            if (croppedBlob) {
                formData.append('profile_picture', croppedBlob, 'profile_pic.jpg');
            }

            fetch('<?php echo SITE_URL; ?>api/profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showAlert(result.message, 'success');
                        // Update display name
                        const fName = document.getElementById('firstName').value;
                        const lName = document.getElementById('lastName').value;
                        document.getElementById('displayFullName').textContent = `${fName} ${lName}`;

                        // Update profile picture preview
                        if (result.profile_picture) {
                            const profilePreview = document.getElementById('profileImagePreview');
                            const defaultIcon = document.getElementById('defaultProfileIcon');
                            if (profilePreview && defaultIcon) {
                                profilePreview.src = '<?php echo SITE_URL; ?>' + result.profile_picture;
                                profilePreview.classList.remove('d-none');
                                defaultIcon.classList.add('d-none');
                            }

                            // Update Navbar Image
                            const navbarPicContainer = document.getElementById('navbarProfilePicContainer');
                            if (navbarPicContainer) {
                                navbarPicContainer.innerHTML = `<img src="<?php echo SITE_URL; ?>${result.profile_picture}" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">`;
                            }
                        }

                        // Update Navbar Name
                        const navbarName = document.getElementById('navbarProfileName');
                        if (navbarName) {
                            navbarName.textContent = `${fName} ${lName}`;
                        }

                        toggleEditMode(false);
                        croppedBlob = null; // Clear after success
                    } else {
                        showAlert(result.message, 'danger');
                    }
                })
                .catch(err => console.error('Error updating profile:', err));
        });

        function showAlert(message, type) {
            Swal.fire({
                icon: type === 'danger' ? 'error' : 'success',
                title: type === 'danger' ? 'Error' : 'Success',
                text: message,
                showConfirmButton: false,
                confirmButtonColor: '#6366f1',
                timer: 2000
            });
        }

        // --- Page Tutorial ---
        <?php if (!isset($seen_tutorials['profile.php'])): ?>
            if (!(window.seenTutorials && window.seenTutorials['profile.php'])) {
                const steps = [{
                        title: '👤 Your Profile',
                        text: 'This is your personal profile page. You can update your name, email, contact number, and profile picture here.'
                    },
                    {
                        title: '✏️ Edit Mode',
                        text: 'Click the "Edit Profile" button in the top-right of the card to unlock all fields for editing.',
                        target: '#btnToggleEdit'
                    },
                    {
                        title: '📷 Profile Picture',
                        text: 'In edit mode, click the camera icon on your avatar to upload and crop a new profile photo. It will appear across the app instantly.',
                        target: '#profileImagePreview, #defaultProfileIcon'
                    },
                    {
                        title: '💾 Save Your Changes',
                        text: 'After editing, click "Save Changes" to apply updates. Your name will refresh in the navbar automatically. Click "Cancel" to revert.',
                        target: '#profileForm'
                    }
                ];

                if (!document.getElementById('tutorial-styles')) {
                    const style = document.createElement('style');
                    style.id = 'tutorial-styles';
                    style.textContent = `.tutorial-highlight { outline: 4px solid #6366f1; outline-offset: 4px; border-radius: 12px; transition: outline 0.3s ease; z-index: 9999; position: relative; }`;
                    document.head.appendChild(style);
                }

                function showStep(index) {
                    if (index >= steps.length) {
                        markPageTutorialSeen('profile.php');
                        return;
                    }
                    const step = steps[index];
                    Swal.fire({
                        title: step.title,
                        text: step.text,
                        icon: 'info',
                        confirmButtonText: index === steps.length - 1 ? '🎉 Got it!' : 'Next →',
                        confirmButtonColor: '#6366f1',
                        showCancelButton: true,
                        cancelButtonText: 'Skip Tour',
                        reverseButtons: true,
                        allowOutsideClick: false,
                        didOpen: () => {
                            if (step.target) {
                                const el = document.querySelector(step.target);
                                if (el) {
                                    el.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'center'
                                    });
                                    el.classList.add('tutorial-highlight');
                                    setTimeout(() => el.classList.remove('tutorial-highlight'), 2500);
                                }
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) showStep(index + 1);
                        else if (result.dismiss === Swal.DismissReason.cancel) markPageTutorialSeen('profile.php');
                    });
                }

                setTimeout(() => showStep(0), 1000);
            }
        <?php endif; ?>
    });
</script>

<?php include '../includes/footer.php'; ?>