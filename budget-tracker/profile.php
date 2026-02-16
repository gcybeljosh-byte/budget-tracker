<?php
$pageTitle = 'Profile';
$pageHeader = 'My Profile';
include 'includes/header.php';
?>

    <?php include 'includes/sidebar.php'; ?>

    <!-- Page Content -->
    <div id="page-content-wrapper">

        <?php include 'includes/navbar.php'; ?>

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
    fetch('api/profile.php')
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
                    document.getElementById('profileImagePreview').src = user.profile_picture;
                    document.getElementById('profileImagePreview').classList.remove('d-none');
                    document.getElementById('defaultProfileIcon').classList.add('d-none');
                }
            }
        })
        .catch(err => console.error('Error fetching profile:', err));

    // Live Preview on File Select
    const profileUpload = document.getElementById('profileUpload');
    profileUpload.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const profilePreview = document.getElementById('profileImagePreview');
                const defaultIcon = document.getElementById('defaultProfileIcon');
                if (profilePreview && defaultIcon) {
                    profilePreview.src = e.target.result;
                    profilePreview.classList.remove('d-none');
                    defaultIcon.classList.add('d-none');
                }
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Update profile (Personal Info)
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'info');
        
        // Manual append profile pic
        const fileInput = document.getElementById('profileUpload');
        if (fileInput.files.length > 0) {
            formData.append('profile_picture', fileInput.files[0]);
        }
        
        fetch('api/profile.php', {
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
                        profilePreview.src = result.profile_picture;
                        profilePreview.classList.remove('d-none');
                        defaultIcon.classList.add('d-none');
                    }

                    // Update Navbar Image
                    const navbarPicContainer = document.getElementById('navbarProfilePicContainer');
                    if (navbarPicContainer) {
                        navbarPicContainer.innerHTML = `<img src="${result.profile_picture}" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">`;
                    }
                }

                // Update Navbar Name
                const navbarName = document.getElementById('navbarProfileName');
                if (navbarName) {
                    navbarName.textContent = `${fName} ${lName}`;
                }
                
                toggleEditMode(false);
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
            timer: 2000
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
