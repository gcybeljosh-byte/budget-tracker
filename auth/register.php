<?php
session_start();
include '../includes/config.php';
include '../includes/db.php';

if (isset($_SESSION['id'])) {
    // If logged in, check onboarding status before redirecting
    $stmt = $conn->prepare("SELECT onboarding_completed FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $stmt->bind_result($oncompleted);
    $stmt->fetch();
    $stmt->close();

    if ($oncompleted == 0 && ($_SESSION['role'] ?? 'user') !== 'admin') {
        header("Location: " . SITE_URL . "core/onboarding.php");
    } else {
        header("Location: " . SITE_URL . "core/dashboard.php");
    }
    exit;
}

// Migrations handled in includes/db.php
ensureColumnExists($conn, 'users', 'auth_method', "VARCHAR(20) DEFAULT 'Local'");

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    if (empty($first_name) || empty($last_name) || empty($email) || empty($contact_number) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8 || !preg_match("/[0-9]/", $password) || !preg_match("/[^a-zA-Z0-9]/", $password)) {
        $error = "Password must be at least 8 characters long and include at least one number and one special character.";
    } else {
        // Check for uniqueness
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Need to check which one matched to give specific error, or just generic
            // For simplicity, we can just say "Username or Email already taken" or do two queries.
            // Let's do a more specific check.
            $stmt->bind_result($existing_id);
            $stmt->fetch();

            // Re-query to determine which specifically exists for a better error message
            $stmt_u = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt_u->bind_param("s", $username);
            $stmt_u->execute();
            $stmt_u->store_result();
            $is_user_taken = $stmt_u->num_rows > 0;
            $stmt_u->close();

            $stmt_e = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt_e->bind_param("s", $email);
            $stmt_e->execute();
            $stmt_e->store_result();
            $is_email_taken = $stmt_e->num_rows > 0;
            $stmt_e->close();

            if ($is_user_taken) {
                $error = "Username already taken.";
            } elseif ($is_email_taken) {
                $error = "Email address already registered.";
            }
        } else {
            // Check for Full Name Uniqueness
            $stmt_name = $conn->prepare("SELECT id FROM users WHERE first_name = ? AND last_name = ?");
            $stmt_name->bind_param("ss", $first_name, $last_name);
            $stmt_name->execute();
            $stmt_name->store_result();

            if ($stmt_name->num_rows > 0) {
                $error = "A user with this full name already exists.";
            } else {
                // Insert new user
                $stmt_insert = $conn->prepare("INSERT INTO users (username, password, first_name, last_name, email, contact_number, auth_method, plaintext_password) VALUES (?, ?, ?, ?, ?, ?, 'Local', ?)");
                $stmt_insert->bind_param("sssssss", $username, $password, $first_name, $last_name, $email, $contact_number, $password);

                if ($stmt_insert->execute()) {
                    $new_user_id = $stmt_insert->insert_id;

                    // Notify Admins & Superadmins
                    require_once '../includes/NotificationHelper.php';
                    $notifHelper = new NotificationHelper($conn);
                    $adminStmt = $conn->prepare("SELECT id FROM users WHERE role IN ('admin', 'superadmin')");
                    $adminStmt->execute();
                    $admins = $adminStmt->get_result();
                    while ($admin = $admins->fetch_assoc()) {
                        $notifHelper->addNotification($admin['id'], 'new_user', "New user registered: $first_name $last_name (@$username)");
                    }
                    $adminStmt->close();

                    // Log Registration
                    logActivity($conn, $new_user_id, 'registration', "New user account created");

                    // Auto-login after registration
                    $_SESSION['id'] = $new_user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['last_name'] = $last_name;
                    $_SESSION['role'] = 'user';
                    $_SESSION['login_time'] = date("Y-m-d H:i:s");
                    $_SESSION['temp_registration_password'] = $password; // For onboarding display

                    $success = "Registration successful! Taking you through personalization...";
                } else {
                    $error = "Error: " . $conn->error;
                }
                $stmt_insert->close();
            }
            $stmt_name->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Budget Tracker</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>assets/images/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            padding: 2rem 0;
            color: #3f4756;
        }

        .auth-card {
            width: 100%;
            max-width: 550px;
            padding: 3rem;
            background: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.02);
        }

        @media (max-width: 576px) {
            .auth-card {
                padding: 1.5rem;
            }
        }

        .brand-text {
            color: #1e293b;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .form-control {
            background-color: #f8f9fa;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            font-weight: 500;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-floating>label {
            color: #64748b;
        }

        .btn-primary {
            background: #1e293b;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #0f172a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
        }
    </style>
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="text-center mb-5">
                    <div class="mb-3 d-inline-flex align-items-center justify-content-center">
                        <img src="<?php echo SITE_URL; ?>assets/images/favicon.png" alt="Logo" style="width: 48px; height: 48px; object-fit: contain;">
                    </div>
                    <h2 class="h4 fw-bold brand-text mb-1">Join Budget Tracker</h2>
                    <p class="text-muted small">Start your financial journey today.</p>
                </div>

                <div class="auth-card mx-auto">
                    <h5 class="text-center mb-4 fw-bold text-dark">Create Account</h5>

                    <form method="POST" id="registerForm">
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="first_name" class="form-control rounded-3" id="firstName" placeholder="First Name" required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                                    <label for="firstName">First Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="last_name" class="form-control rounded-3" id="lastName" placeholder="Last Name" required value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                                    <label for="lastName">Last Name</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="email" name="email" class="form-control rounded-3" id="email" placeholder="name@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <label for="email">Email Address</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" name="contact_number" class="form-control rounded-3" id="contactNumber" placeholder="Contact Number" required value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : ''; ?>">
                            <label for="contactNumber">Contact Number</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" name="username" class="form-control rounded-3" id="floatingUser" placeholder="Username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            <label for="floatingUser">Username</label>
                        </div>

                        <div class="form-floating mb-3 position-relative">
                            <input type="password" name="password" class="form-control rounded-3" id="floatingPass" placeholder="Password" required>
                            <label for="floatingPass">Password</label>
                            <button type="button" class="btn position-absolute top-50 end-0 translate-middle-y border-0 me-2" onclick="togglePassword('floatingPass', this)" style="z-index: 10;">
                                <i class="fas fa-eye text-muted"></i>
                            </button>
                            <div class="form-text text-muted small">
                                Min 8 chars, 1 number, 1 special char.
                            </div>
                        </div>

                        <div class="form-floating mb-4 position-relative">
                            <input type="password" name="confirm_password" class="form-control rounded-3" id="floatingConfirm" placeholder="Confirm Password" required>
                            <label for="floatingConfirm">Confirm Password</label>
                            <button type="button" class="btn position-absolute top-50 end-0 translate-middle-y border-0 me-2" onclick="togglePassword('floatingConfirm', this)" style="z-index: 10;">
                                <i class="fas fa-eye text-muted"></i>
                            </button>
                        </div>



                        <button type="button" id="btnRegister" class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow-sm">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>

                        <div class="text-center my-3 text-muted small">OR</div>

                        <div class="d-flex justify-content-center">
                            <div id="g_id_onload"
                                data-client_id="<?php echo GOOGLE_CLIENT_ID; ?>"
                                data-context="signup"
                                data-ux_mode="popup"
                                data-callback="handleCredentialResponse"
                                data-auto_prompt="false">
                            </div>
                            <div class="g_id_signin"
                                data-type="standard"
                                data-shape="rectangular"
                                data-theme="outline"
                                data-text="signup_with"
                                data-size="large"
                                data-logo_alignment="left">
                            </div>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="text-muted mb-0">Already have an account? <a href="<?php echo SITE_URL; ?>auth/login.php" class="text-primary fw-bold text-decoration-none">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        document.getElementById('btnRegister').addEventListener('click', function() {
            const firstName = document.getElementById('firstName').value;
            const lastName = document.getElementById('lastName').value;
            const email = document.getElementById('email').value;
            const contactNumber = document.getElementById('contactNumber').value;
            const username = document.getElementById('floatingUser').value;
            const password = document.getElementById('floatingPass').value;
            const confirmPassword = document.getElementById('floatingConfirm').value;

            // Basic Client-Side Validation
            if (!firstName || !lastName || !email || !contactNumber || !username || !password || !confirmPassword) {
                Swal.fire('Error', 'Please fill in all fields.', 'error');
                return;
            }

            if (password !== confirmPassword) {
                Swal.fire({
                    title: 'Error',
                    text: 'Passwords do not match.',
                    icon: 'error',
                    confirmButtonColor: '#6366f1'
                });
                return;
            }

            // Password Complexity Regex
            const passwordRegex = /^(?=.*[0-9])(?=.*[!@#$%^&*])[a-zA-Z0-9!@#$%^&*]{8,}$/;
            if (!passwordRegex.test(password)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Weak Password',
                    text: 'Password must be at least 8 characters long and include at least one number and one special character.',
                    confirmButtonColor: '#6366f1'
                });
                return;
            }

            // Confirmation Modal
            Swal.fire({
                title: 'Confirm Registration Details',
                html: `
                <div class="text-start">
                    <p><strong>Full Name:</strong> ${firstName} ${lastName}</p>
                    <p><strong>Email:</strong> ${email}</p>
                    <p><strong>Contact:</strong> ${contactNumber}</p>
                    <p><strong>Username:</strong> ${username}</p>
                </div>
            `,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#6366f1',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Register!',
                cancelButtonText: 'Edit'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('registerForm').submit();
                }
            });
        });

        const urlParams = new URLSearchParams(window.location.search);

        <?php if ($error): ?>
            Swal.fire({
                icon: 'error',
                title: 'Registration Failed',
                text: '<?php echo $error; ?>',
                confirmButtonColor: '#6366f1'
            });
        <?php endif; ?>

        <?php if ($success): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Registration successful! Let\'s personalize your experience...',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = '<?php echo SITE_URL; ?>core/onboarding.php';
            });
        <?php endif; ?>

        if (urlParams.get('error') === 'account_exists') {
            Swal.fire({
                icon: 'info',
                title: 'Account Already Exists',
                text: 'This Google account is already registered with Budget Tracker. Please go to the Sign In page to access your account.',
                confirmButtonText: 'Go to Sign In',
                confirmButtonColor: '#6366f1'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?php echo SITE_URL; ?>auth/login.php';
                }
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        <?php if (isset($_GET['google_success']) && isset($_SESSION['google_registered_password'])): ?>
            Swal.fire({
                title: 'Account Created Successfully!',
                html: `
            <div class="text-center p-3 mb-3 bg-light rounded-3 border">
                <p class="mb-2 text-muted small uppercase fw-bold">Your Login Credentials</p>
                <div class="mb-3">
                    <label class="small text-muted d-block mb-1 text-start">Username</label>
                    <div class="input-group">
                        <input type="text" class="form-control bg-white text-center fw-bold" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly id="copyUsername">
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('copyUsername', this)">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="small text-muted d-block mb-1 text-start">Password</label>
                    <div class="input-group">
                        <input type="text" class="form-control bg-white text-center fw-bold" value="<?php echo htmlspecialchars($_SESSION['google_registered_password']); ?>" readonly id="copyPassword">
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('copyPassword', this)">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="alert alert-info py-2 small mb-0 text-start">
                <i class="fas fa-info-circle me-2"></i> Please save these details. You can use them to sign in even if you don't have access to your Google account.
            </div>
        `,
                icon: 'success',
                confirmButtonText: 'Continue to Setup <i class="fas fa-arrow-right ms-2"></i>',
                confirmButtonColor: '#1e293b',
                allowOutsideClick: false,
                padding: '2rem'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?php echo SITE_URL; ?>core/onboarding.php';
                }
            });
        <?php endif; ?>

        window.copyToClipboard = function(elementId, btn) {
            const copyText = document.getElementById(elementId);
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);

            const originalIcon = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check text-success"></i>';
            setTimeout(() => {
                btn.innerHTML = originalIcon;
            }, 2000);
        };

        // --- Google OAuth (GSI) ---
        function handleCredentialResponse(response) {
            // Send the JWT credential to google_auth.php
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo SITE_URL; ?>auth/google_auth.php';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'credential';
            input.value = response.credential;

            const modeInput = document.createElement('input');
            modeInput.type = 'hidden';
            modeInput.name = 'auth_mode';
            modeInput.value = 'register';

            form.appendChild(input);
            form.appendChild(modeInput);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>

</body>

</html>