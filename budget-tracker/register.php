<?php
session_start();
if (isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

include 'includes/db.php';

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

    // Basic Validation
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
            
            // Re-query to see specific
            $check_user = $conn->query("SELECT id FROM users WHERE username = '$username'");
            $check_email = $conn->query("SELECT id FROM users WHERE email = '$email'");
            
            if ($check_user->num_rows > 0) {
                $error = "Username already taken.";
            } elseif ($check_email->num_rows > 0) {
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
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert = $conn->prepare("INSERT INTO users (username, password, first_name, last_name, email, contact_number) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_insert->bind_param("ssssss", $username, $hashed_password, $first_name, $last_name, $email, $contact_number);
                
                if ($stmt_insert->execute()) {
                    $new_user_id = $stmt_insert->insert_id;
                    
                    // Notify Admins
                    require_once 'includes/NotificationHelper.php';
                    $notifHelper = new NotificationHelper($conn);
                    $adminStmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin'");
                    $adminStmt->execute();
                    $admins = $adminStmt->get_result();
                    while ($admin = $admins->fetch_assoc()) {
                        $notifHelper->addNotification($admin['id'], 'new_user', "New user registered: $first_name $last_name (@$username)");
                    }
                    $adminStmt->close();

                    // Auto-login after registration
                    $_SESSION['id'] = $new_user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['last_name'] = $last_name;
                    $_SESSION['role'] = 'user';
                    $_SESSION['login_time'] = date("Y-m-d H:i:s");
                    
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
    <link rel="icon" type="image/png" href="favicon.png">
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
            border: 1px solid rgba(0,0,0,0.02);
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
        .form-floating > label {
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
</head>
<body>

<div class="container">
    <div class="row justify-content-center w-100">
        <div class="col-md-8 col-lg-6">
            <div class="text-center mb-5">
                <div class="mb-3 d-inline-flex align-items-center justify-content-center bg-white shadow-sm rounded-circle p-3">
                    <i class="fas fa-wallet fa-2x text-primary"></i>
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
                    
                    <div class="form-floating mb-3">
                        <input type="password" name="password" class="form-control rounded-3" id="floatingPass" placeholder="Password" required>
                        <label for="floatingPass">Password</label>
                        <div class="form-text text-muted small">
                            Min 8 chars, 1 number, 1 special char.
                        </div>
                    </div>
                    
                    <div class="form-floating mb-4">
                        <input type="password" name="confirm_password" class="form-control rounded-3" id="floatingConfirm" placeholder="Confirm Password" required>
                        <label for="floatingConfirm">Confirm Password</label>
                    </div>
                    
                    <button type="button" id="btnRegister" class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow-sm">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <p class="text-muted mb-0">Already have an account? <a href="login.php" class="text-primary fw-bold text-decoration-none">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
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
            Swal.fire('Error', 'Passwords do not match.', 'error');
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
        window.location.href = 'onboarding.php';
    });
    <?php endif; ?>

    // --- Google OAuth via Google Apps Script ---
    function handleGoogleAuth() {
        // Google Apps Script handles basic OAuth2 authorization
        // Replace with your deploy ID
        const scriptUrl = "https://script.google.com/macros/s/AKfycbyGhF-3_fzHaZv9Aoi8362EgyEnZBkvjqx6yOjq3IJA5_5iKehE-t8Rj_-vLv__xw8/exec"; 
        
        // Redirect to GAS Web App (Unified Auth Flow)
        window.location.href = scriptUrl + "?action=login";
    }

    // Inject Google Button if not exists (or add to HTML above for better control)
    // Adding it dynamically here for simplicity, or modify HTML above.
    // Let's modify the HTML above instead for cleaner code.
</script>
<script>
    // Injecting the button dynamically to ensure correct placement without breaking HTML structure in this edit
    const regBtn = document.getElementById('btnRegister');
    if(regBtn) {
        const googleBtn = document.createElement('button');
        googleBtn.type = 'button'; // Prevent form submit
        googleBtn.className = 'btn btn-outline-dark w-100 py-3 rounded-3 fw-bold shadow-sm mt-3';
        googleBtn.innerHTML = '<i class="fab fa-google me-2"></i>Register with Google';
        googleBtn.onclick = handleGoogleAuth;
        regBtn.parentNode.insertBefore(googleBtn, regBtn.nextSibling);
        
        // Add a separator
        const separator = document.createElement('div');
        separator.className = 'text-center my-3 text-muted small';
        separator.innerText = 'OR';
        regBtn.parentNode.insertBefore(separator, googleBtn);
    }
</script>

</body>
</html>
