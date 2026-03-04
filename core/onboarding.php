<?php
session_start();
require_once '../includes/config.php';
include '../includes/db.php';
require_once '../includes/CurrencyHelper.php';

// Auth guard
if (!isset($_SESSION['id'])) {
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}

$pageTitle = 'Welcome - Setup';

// Check if already completed
$user_id = $_SESSION['id'];
$stmt = $conn->prepare("SELECT onboarding_completed, first_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($onboarding_completed, $first_name);
$stmt->fetch();
$stmt->close();

if ($onboarding_completed == 1) {
    header("Location: " . SITE_URL . "core/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personalize Your Explorer - Budget Tracker</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>assets/images/favicon_rounded.png">
    <?php include '../includes/favicon_force.php'; ?>
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #a855f7;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s ease;
        }

        .onboarding-card {
            background: var(--card-bg);
            border-radius: 1.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .step-indicator {
            height: 6px;
            background: var(--border);
            display: flex;
        }

        .step-progress {
            height: 100%;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            transition: width 0.5s ease;
        }

        .step-content {
            padding: 3rem;
            display: none;
        }

        .step-content.active {
            display: block;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.4);
            color: white;
        }

        .category-chip {
            cursor: pointer;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            border-radius: 2rem;
            border: 2px solid var(--border);
            display: inline-block;
            transition: all 0.2s;
            font-size: 0.9rem;
            background: transparent;
            color: var(--text-main);
        }

        .category-chip.selected {
            background-color: rgba(99, 102, 241, 0.1);
            border-color: var(--primary);
            color: var(--primary);
            font-weight: 600;
        }

        .icon-box {
            width: 80px;
            height: 80px;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 2rem;
        }

        .form-select,
        .form-control {
            background-color: var(--card-bg);
            border-color: var(--border);
            color: var(--text-main);
        }

        .form-select:focus,
        .form-control:focus {
            background-color: var(--card-bg);
            border-color: var(--primary);
            color: var(--text-main);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25);
        }

        .input-group-text {
            background-color: var(--bg);
            border-color: var(--border);
            color: var(--text-muted);
        }

        .bg-light {
            background-color: var(--bg) !important;
            border-color: var(--border) !important;
        }

        .text-dark {
            color: var(--text-main) !important;
        }
    </style>
</head>

<body>

    <div class="onboarding-card shadow">
        <div class="step-indicator">
            <div id="progress" class="step-progress" style="width: 20%;"></div>
        </div>

        <form id="onboardingForm">
            <!-- Step 1: Welcome & Currency -->
            <div class="step-content active" id="step1">
                <div class="icon-box">
                    <i class="fas fa-hand-sparkles"></i>
                </div>
                <h2 class="text-center fw-bold mb-2">Welcome, <?php echo htmlspecialchars($first_name); ?>!</h2>
                <p class="text-center text-muted mb-4">Let's set up your budget tracker in just a few steps.</p>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Preferred Currency</label>
                    <select name="currency" class="form-select form-select-lg rounded-3">
                        <option value="PHP">Philippine Peso (₱)</option>
                        <option value="USD">US Dollar ($)</option>
                        <option value="EUR">Euro (€)</option>
                        <option value="JPY">Japanese Yen (¥)</option>
                    </select>
                </div>

                <div class="d-grid">
                    <button type="button" class="btn btn-gradient btn-lg next-step" data-next="2">Get Started</button>
                </div>
            </div>

            <!-- Step 2: Profile Customization -->
            <div class="step-content" id="step2">
                <div class="icon-box">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h3 class="fw-bold mb-2">Personalize Your Profile</h3>
                <p class="text-muted mb-4">Set a nickname and upload a profile picture to display in the system.</p>

                <div class="mb-4 text-center">
                    <div class="position-relative mx-auto mb-3" style="width: 120px; height: 120px;">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center overflow-hidden h-100 w-100 border border-4 border-white shadow-sm">
                            <img id="profilePreview" src="" alt="Profile" class="d-none rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
                            <i id="defaultIcon" class="fas fa-user" style="font-size: 3rem;"></i>
                        </div>
                        <label for="profile_upload" class="position-absolute bottom-0 end-0 bg-white rounded-circle shadow p-2" style="cursor: pointer; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-camera text-primary"></i>
                        </label>
                        <input type="file" name="profile_picture" id="profile_upload" class="d-none" accept="image/*">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Nickname</label>
                    <input type="text" name="nickname" class="form-control form-control-lg rounded-3" placeholder="How should we call you?" value="<?php echo htmlspecialchars($first_name); ?>">
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-link text-muted fw-semibold text-decoration-none prev-step" data-prev="1">Back</button>
                    <button type="button" class="btn btn-gradient next-step" data-next="3">Next Step</button>
                </div>
            </div>

            <!-- Step 2: Categories -->
            <div class="step-content" id="step2">
                <h3 class="fw-bold mb-2">Build Your Library</h3>
                <p class="text-muted mb-4">Select the categories you'll use most. You can add more later.</p>

                <div id="categoryContainer" class="mb-4">
                    <?php
                    $defaults = ['Food & Dining', 'Transportation', 'Rent & Utilities', 'Entertainment', 'Shopping', 'Healthcare', 'Education', 'Savings', 'Other'];
                    foreach ($defaults as $cat): ?>
                        <div class="category-chip" data-name="<?php echo $cat; ?>"><?php echo $cat; ?></div>
                    <?php endforeach; ?>
                    <input type="hidden" name="selected_categories" id="selected_categories">
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-link text-muted fw-semibold text-decoration-none prev-step" data-prev="2">Back</button>
                    <button type="button" class="btn btn-gradient next-step" data-next="4">Next Step</button>
                </div>
            </div>

            <!-- Step 4: Goals -->
            <div class="step-content" id="step4">
                <h3 class="fw-bold mb-2">Monthly Limit/Goal</h3>
                <p class="text-muted mb-4">Set a monthly spending limit to help you stay on track. This will be shown in your Journal.</p>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Monthly Limit</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?></span>
                        <input type="number" name="budget_goal" class="form-control border-start-0 ps-0" placeholder="0.00" value="5000">
                    </div>
                    <div class="form-text mt-2">We'll alert you if you're approaching this limit.</div>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-link text-muted fw-semibold text-decoration-none prev-step" data-prev="3">Back</button>
                    <button type="button" class="btn btn-gradient next-step" data-next="5">Last Step</button>
                </div>
            </div>

            <!-- Step 5: Credential Review -->
            <div class="step-content" id="step5">
                <h3 class="fw-bold mb-2">Review Your Account</h3>
                <p class="text-muted mb-4">Verification of your registered credentials.</p>

                <div class="p-3 bg-light rounded-4 border mb-4">
                    <div class="mb-3">
                        <label class="small text-muted d-block mb-1 fw-bold text-uppercase">Full Name</label>
                        <div class="fw-bold text-dark fs-5"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted d-block mb-1 fw-bold text-uppercase">Nickname</label>
                        <div id="reviewNickname" class="fw-bold text-primary fs-5">Pending...</div>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted d-block mb-1 fw-bold text-uppercase">Username</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-white fw-bold" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly id="onboardingUsername">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyValue('onboardingUsername', this)">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <?php
                    $pass_exists = isset($_SESSION['google_registered_password']) || isset($_SESSION['temp_registration_password']);
                    $pass = $_SESSION['google_registered_password'] ?? ($_SESSION['temp_registration_password'] ?? '********');
                    ?>
                    <div>
                        <label class="small text-muted d-block mb-1 fw-bold text-uppercase">Password</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-white fw-bold <?php echo !$pass_exists ? 'text-muted' : ''; ?>" value="<?php echo htmlspecialchars($pass); ?>" readonly id="onboardingPassword">
                            <?php if ($pass_exists): ?>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordDisplay('onboardingPassword', this)">
                                    <i class="fas fa-eye-slash"></i>
                                </button>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyValue('onboardingPassword', this)">
                                    <i class="fas fa-copy"></i>
                                </button>
                            <?php else: ?>
                                <div class="input-group-text bg-light small"><i class="fas fa-lock me-2"></i> Encrypted</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info py-2 small mb-4">
                    <i class="fas fa-info-circle me-2"></i> Please save these details. You can use them to sign in to your explorer anytime.
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-link text-muted fw-semibold text-decoration-none prev-step" data-prev="3">Back</button>
                    <button type="submit" class="btn btn-gradient">Complete Setup</button>
                </div>
            </div>
        </form>

        <div class="text-center pb-4">
            <hr class="mx-5 opacity-10">
            <p class="small text-muted mb-0">Not you? <a href="logout.php" class="text-primary text-decoration-none fw-semibold">Sign out & Start Over</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('onboardingForm');
            const progress = document.getElementById('progress');
            const nextButtons = document.querySelectorAll('.next-step');
            const prevButtons = document.querySelectorAll('.prev-step');
            const categoryChips = document.querySelectorAll('.category-chip');
            const selectedCatsInput = document.getElementById('selected_categories');

            // Chips Selection logic
            categoryChips.forEach(chip => {
                chip.addEventListener('click', () => {
                    chip.classList.toggle('selected');
                    updateSelectedCategories();
                });
            });

            // Initialize with all chips selected
            categoryChips.forEach(chip => chip.classList.add('selected'));
            updateSelectedCategories();

            function updateSelectedCategories() {
                const selected = Array.from(document.querySelectorAll('.category-chip.selected'))
                    .map(c => c.dataset.name);
                selectedCatsInput.value = JSON.stringify(selected);
            }

            // Stepper Navigation
            nextButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const nextId = btn.dataset.next;
                    showStep(nextId);
                });
            });

            prevButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const prevId = btn.dataset.prev;
                    showStep(prevId);
                });
            });

            function showStep(stepNum) {
                document.querySelectorAll('.step-content').forEach(s => s.classList.remove('active'));
                document.getElementById('step' + stepNum).classList.add('active');
                progress.style.width = (stepNum * 20) + '%';

                // Update review step
                if (stepNum == 5) {
                    const nickname = document.querySelector('input[name="nickname"]').value;
                    document.getElementById('reviewNickname').textContent = nickname || 'Not set';
                }
            }

            // Profile Upload Preview
            const profileUpload = document.getElementById('profile_upload');
            const profilePreview = document.getElementById('profilePreview');
            const defaultIcon = document.getElementById('defaultIcon');

            profileUpload.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profilePreview.src = e.target.result;
                        profilePreview.classList.remove('d-none');
                        defaultIcon.classList.add('d-none');
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });

            window.copyValue = function(elementId, btn) {
                const copyText = document.getElementById(elementId);
                const originalType = copyText.type;
                if (originalType === 'password') copyText.type = 'text';

                copyText.select();
                copyText.setSelectionRange(0, 99999);
                navigator.clipboard.writeText(copyText.value);

                if (originalType === 'password') copyText.type = 'password';

                const originalIcon = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check text-success"></i>';
                setTimeout(() => {
                    btn.innerHTML = originalIcon;
                }, 2000);
            };

            window.togglePasswordDisplay = function(elementId, btn) {
                const passInput = document.getElementById(elementId);
                if (passInput.type === 'password') {
                    passInput.type = 'text';
                    btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    passInput.type = 'password';
                    btn.innerHTML = '<i class="fas fa-eye"></i>';
                }
            };

            // Form Submit
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(form);

                Swal.fire({
                    title: 'Finalizing Setup',
                    text: 'Please wait while we prepare your dashboard...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('<?php echo SITE_URL; ?>api/onboarding.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(async response => {
                        const text = await response.text();
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Raw response:', text);
                            throw new Error('Invalid JSON response from server');
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'All Set!',
                                text: 'Welcome to your new financial dashboard.',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = '<?php echo SITE_URL; ?>core/dashboard.php';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Something went wrong',
                                confirmButtonColor: '#6366f1'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Setup Failed',
                            text: 'We couldn\'t finalize your setup. This might be due to a connection issue or a server error.',
                            confirmButtonColor: '#6366f1',
                            footer: '<small class="text-muted">Check the browser console for details or try again later.</small>'
                        });
                    });
            });
        });
    </script>

</body>

</html>