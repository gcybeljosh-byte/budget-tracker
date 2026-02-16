<?php
$pageTitle = 'Settings';
$pageHeader = 'User Settings';
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
                    
                    <!-- Settings Navigation Buttons -->
                    <div class="row g-3 mb-4 text-center">
                        <div class="col-6">
                            <button class="btn btn-primary w-100 py-3 rounded-4 shadow-sm fw-bold border-0 d-flex flex-column align-items-center justify-content-center h-100 transition-all hover-lift" id="btnSecurity">
                                <i class="fas fa-shield-alt mb-2 fs-3"></i>
                                <span class="small">Security</span>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-light w-100 py-3 rounded-4 shadow-sm fw-bold border-0 d-flex flex-column align-items-center justify-content-center h-100 transition-all toggle-btn-inactive hover-lift" id="btnAbout">
                                <i class="fas fa-info-circle mb-2 fs-3"></i>
                                <span class="small">About</span>
                            </button>
                        </div>
                    </div>

                    <style>
                        .transition-all { transition: all 0.3s ease; }
                        .hover-lift:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
                        .settings-section { animation: fadeIn 0.4s ease-out; }
                        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
                        .toggle-btn-inactive { background-color: #f8f9fa !important; color: #6c757d !important; }
                        .toggle-btn-inactive:hover { background-color: #e9ecef !important; }
                    </style>

                    <!-- Account Security Section -->
                    <div id="securitySection" class="settings-section">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0 fw-bold"><i class="fas fa-shield-alt me-2 text-primary"></i>Account Security</h5>
                                    <p class="text-muted small mb-0">Update your credentials and identity information.</p>
                                </div>
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" id="btnEditSecurity">
                                    <i class="fas fa-lock me-1"></i> Edit
                                </button>
                            </div>
                            <div class="card-body p-4">
                                <!-- Username Identity -->
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-3 small text-uppercase text-secondary">Account Identity</h6>
                                    <form id="usernameForm">
                                        <div class="mb-3">
                                            <label class="form-label text-secondary small text-uppercase fw-bold">Username</label>
                                            <div class="input-group shadow-sm rounded-3 overflow-hidden border">
                                                <span class="input-group-text bg-light border-0 text-secondary">@</span>
                                                <input type="text" class="form-control border-0" id="usernameInput" name="username" required disabled>
                                                <input type="hidden" name="action" value="username">
                                                <button class="btn btn-primary px-4 fw-bold" type="submit" disabled>Update</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <hr class="my-4 text-secondary opacity-25">

                                <!-- Password Update -->
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-3 small text-uppercase text-secondary">Update Password</h6>
                                    <form id="passwordForm">
                                        <div class="mb-3">
                                            <label class="form-label text-secondary small text-uppercase fw-bold">Current Password</label>
                                            <input type="password" class="form-control rounded-3" name="current_password" required disabled>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-secondary small text-uppercase fw-bold">New Password</label>
                                            <input type="password" class="form-control rounded-3" name="new_password" required minlength="6" disabled>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-secondary small text-uppercase fw-bold">Confirm New Password</label>
                                            <input type="password" class="form-control rounded-3" name="confirm_password" required minlength="6" disabled>
                                        </div>
                                        <button type="submit" class="btn btn-outline-primary w-100 py-2 fw-bold rounded-pill" disabled>
                                            <i class="fas fa-save me-2"></i>Change Password
                                        </button>
                                    </form>
                                </div>

                                <hr class="my-4 text-secondary opacity-25">

                                <!-- Security Recovery -->
                                <div>
                                    <h6 class="fw-bold mb-3 small text-uppercase text-secondary">Account Recovery</h6>
                                    <p class="text-muted small mb-3">Set a security question to help you recover your account if you forget your password.</p>
                                    <form id="securityQuestionForm">
                                        <div class="mb-3">
                                            <label class="form-label text-secondary small text-uppercase fw-bold">Security Question</label>
                                            <select class="form-select rounded-3" name="security_question" id="securityQuestionSelect" required disabled>
                                                <option value="" disabled selected>Select a question</option>
                                                <option value="What was the name of your first pet?">What was the name of your first pet?</option>
                                                <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                                <option value="What was the name of your first school?">What was the name of your first school?</option>
                                                <option value="In what city were you born?">In what city were you born?</option>
                                                <option value="What is your favorite book?">What is your favorite book?</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-secondary small text-uppercase fw-bold">Your Answer</label>
                                            <input type="text" class="form-control rounded-3" name="security_answer" id="securityAnswerInput" placeholder="Answer (case-insensitive)" required disabled>
                                        </div>
                                        <button type="submit" class="btn btn-outline-info w-100 py-2 fw-bold rounded-pill" disabled>
                                            <i class="fas fa-key me-2"></i>Save Security Question
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- About System Section -->
                    <div id="aboutSection" class="settings-section">
                        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                            <div class="card-header bg-gradient-primary text-white border-0 p-4">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-white bg-opacity-25 rounded-circle p-2 me-3">
                                        <i class="fas fa-wallet fa-2x"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0 fw-bold">Budget Tracking System</h4>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-white bg-opacity-25 small">v1.0.0</span>
                                            <span class="text-white-50" style="font-size: 0.7rem;">• Powered by Gemini 2.5 Flash</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4 pt-5">
                                <div class="mb-5 text-center">
                                    <h5 class="fw-bold brand-text mb-3">Master Your Personal Finances</h5>
                                    <p class="text-secondary">The Budget Tracking System is a premium, all-in-one financial management platform designed to help you organize your daily allowance, track expenses, and grow your savings with ease and intelligence.</p>
                                </div>

                                <div class="row g-4 mb-5">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold mb-3 text-primary"><i class="fas fa-star me-2"></i>Major Features</h6>
                                        <ul class="list-unstyled small text-secondary">
                                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><strong>Dynamic Dashboard</strong>: Real-time overview of your net balance and spending trends.</li>
                                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><strong>Smart AI Assistant</strong>: Professional AI help desk that can perform actions and offer advice.</li>
                                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><strong>Global Currency Engine</strong>: Full support for PHP, USD, and EUR with locale-aware formatting.</li>
                                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><strong>Savvy Savings Tracker</strong>: Lifetime cumulative savings with monthly/yearly analytics.</li>
                                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><strong>Advanced Auth</strong>: Secure login options including Google OAuth synchronization.</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-bold mb-3 text-secondary"><i class="fas fa-plus-circle me-2"></i>More Highlights</h6>
                                        <ul class="list-unstyled small text-secondary">
                                            <li class="mb-2"><i class="fas fa-check text-primary me-2"></i><strong>Security Guardian</strong>: Session inactivity timer & real-time security indicator.</li>
                                            <li class="mb-2"><i class="fas fa-check text-primary me-2"></i><strong>Multi-Source Tracking</strong>: Manage funds from Cash, Banks, or E-Wallets.</li>
                                            <li class="mb-2"><i class="fas fa-check text-primary me-2"></i><strong>Automated Alerts</strong>: Low balance notifications and scheduled budget reminders.</li>
                                            <li class="mb-2"><i class="fas fa-check text-primary me-2"></i><strong>Lightning Search</strong>: High-speed filtering for all transaction records.</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="bg-light rounded-4 p-4 text-center border">
                                    <p class="text-secondary small mb-1">Designed & Developed by</p>
                                    <h5 class="fw-bold mb-1">Cybel Josh A. Gamido</h5>
                                    <p class="text-muted small mb-0">Full Stack Developer & Systems Designer</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    
    let isSecurityUnlocked = false; // Internal flag for current page session
    
    // Section Toggling Logic
    const btnSecurity = document.getElementById('btnSecurity');
    const btnAbout = document.getElementById('btnAbout');
    const securitySection = document.getElementById('securitySection');
    const aboutSection = document.getElementById('aboutSection');

    function setActiveSection(btn, section) {
        [securitySection, aboutSection].forEach(s => s.classList.add('d-none'));
        [btnSecurity, btnAbout].forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('toggle-btn-inactive');
        });

        section.classList.remove('d-none');
        btn.classList.remove('toggle-btn-inactive');
        btn.classList.add('btn-primary');
    }

    btnSecurity.addEventListener('click', () => {
        if (isSecurityUnlocked) {
            setActiveSection(btnSecurity, securitySection);
            return;
        }

        Swal.fire({
            title: 'Verify Password',
            text: 'Please enter your password to access security settings.',
            input: 'password',
            inputAttributes: {
                autocapitalize: 'off',
                autocorrect: 'off'
            },
            showCancelButton: true,
            confirmButtonText: 'Confirm',
            confirmButtonColor: '#6366f1',
            showLoaderOnConfirm: true,
            preConfirm: (password) => {
                const formData = new FormData();
                formData.append('action', 'verify_password');
                formData.append('password', password);
                
                return fetch('api/settings.php', { method: 'POST', body: formData })
                    .then(response => {
                        if (!response.ok) throw new Error(response.statusText);
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                if (result.value.success) {
                    isSecurityUnlocked = true;
                    setActiveSection(btnSecurity, securitySection);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Verification Failed',
                        text: result.value.message || 'Incorrect password. Please try again.',
                        confirmButtonColor: '#6366f1'
                    });
                }
            }
        });
    });

    btnAbout.addEventListener('click', () => setActiveSection(btnAbout, aboutSection));
    
    // 1. Initial Data Load (Profile + Preferences)
    fetch('api/profile.php')
        .then(res => res.json())
        .then(result => {
            if (result.success && result.data) {
                const user = result.data;
                document.getElementById('usernameInput').value = user.username;
                
                // Load Security Question
                if (user.security_question) {
                    document.getElementById('securityQuestionSelect').value = user.security_question;
                }
            }
        });

    // 1.1 Edit Mode Logic for Security
    const btnEditSecurity = document.getElementById('btnEditSecurity');
    let isEditMode = false;

    function toggleEditMode(enable) {
        isEditMode = enable;
        const securityFields = securitySection.querySelectorAll('input, select, button[type="submit"]');
        securityFields.forEach(field => field.disabled = !enable);

        if (enable) {
            btnEditSecurity.innerHTML = '<i class="fas fa-times me-1"></i> Cancel';
            btnEditSecurity.classList.replace('btn-outline-primary', 'btn-outline-danger');
        } else {
            btnEditSecurity.innerHTML = '<i class="fas fa-lock me-1"></i> Edit';
            btnEditSecurity.classList.replace('btn-outline-danger', 'btn-outline-primary');
        }
    }

    btnEditSecurity.addEventListener('click', () => toggleEditMode(!isEditMode));

    // 2. Username Update
    document.getElementById('usernameForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('api/profile.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    showAlert('Username updated!', 'success');
                    toggleEditMode(false); // Lock fields after success
                    const navName = document.getElementById('navbarProfileName');
                    if (navName && !navName.textContent.includes(' ')) {
                        navName.textContent = document.getElementById('usernameInput').value;
                    }
                } else {
                    showAlert(result.message, 'error');
                }
            });
    });

    // 3. Password Update
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('api/change_password.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    showAlert('Password changed successfully', 'success');
                    toggleEditMode(false); // Lock fields after success
                    this.reset();
                    // Re-disable fields that were reset
                    toggleEditMode(false); 
                } else {
                    showAlert(result.message, 'error');
                }
            });
    });

    // 5. Security Question Update
    document.getElementById('securityQuestionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'security_question');
        
        fetch('api/profile.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    showAlert('Security question saved!', 'success');
                    toggleEditMode(false); // Lock fields after success
                    document.getElementById('securityAnswerInput').value = ''; // Clear answer for security
                } else {
                    showAlert(result.message, 'error');
                }
            });
    });

    function showAlert(msg, icon) {
        Swal.fire({
            icon: icon,
            title: icon.charAt(0).toUpperCase() + icon.slice(1),
            text: msg,
            showConfirmButton: false,
            timer: 2000
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
