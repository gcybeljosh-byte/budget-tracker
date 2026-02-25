<?php
session_start();
if (isset($_SESSION['id'])) {
    header("Location: " . SITE_URL . "core/dashboard.php");
    exit;
}

include '../includes/db.php';

$step = 1;
$error = '';
$success = '';
$username = '';
$question = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $username = trim($_POST['username'] ?? '');

    if ($action === 'check_username') {
        if (empty($username)) {
            $error = "Please enter your username.";
        } else {
            $stmt = $conn->prepare("SELECT security_question FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($security_question);
                $stmt->fetch();

                if (empty($security_question)) {
                    $error = "No security question set for this account. Please contact the administrator.";
                } else {
                    $question = $security_question;
                    $step = 2;
                }
            } else {
                $error = "Username not found.";
            }
            $stmt->close();
        }
    } elseif ($action === 'verify_answer') {
        $answer = trim($_POST['answer'] ?? '');
        $step = 2; // Default to step 2 if verification fails

        $stmt = $conn->prepare("SELECT security_question, security_answer FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($question, $hashed_answer);
        $stmt->fetch();
        $stmt->close();

        if (password_verify(strtolower($answer), $hashed_answer)) {
            $step = 3;
        } else {
            $error = "Incorrect answer. Please try again.";
        }
    } elseif ($action === 'reset_password') {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $step = 3;

        if (empty($new_password) || strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET password = ?, plaintext_password = ? WHERE username = ?");
            $stmt->bind_param("sss", $new_password, $new_password, $username);

            if ($stmt->execute()) {
                $success = "Password reset successfully! You can now login with your new password.";
                $step = 4; // Final success step
            } else {
                $error = "Error updating password. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Budget Tracker</title>
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
            max-width: 450px;
            padding: 3rem;
            background: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.02);
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

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }

        .step-indicator::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }

        .step-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: white;
            border: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .step-dot.active {
            border-color: #6366f1;
            color: #6366f1;
            background: #f0f7ff;
        }

        .step-dot.completed {
            background: #6366f1;
            border-color: #6366f1;
            color: white;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="text-center mb-5">
                    <div class="mb-3 d-inline-flex align-items-center justify-content-center">
                        <img src="favicon.png" alt="Logo" style="width: 48px; height: 48px; object-fit: contain;">
                    </div>
                    <h2 class="h4 fw-bold brand-text mb-1">Budget Tracker</h2>
                    <p class="text-muted small">Manage your finances with ease.</p>
                </div>

                <div class="auth-card mx-auto">
                    <div class="mb-4">
                        <a href="<?php echo SITE_URL; ?>" class="text-secondary small text-decoration-none hover-primary flex items-center gap-2">
                            <i class="fas fa-arrow-left"></i> Back to Home
                        </a>
                    </div>
                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step-dot <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">1</div>
                        <div class="step-dot <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">2</div>
                        <div class="step-dot <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">3</div>
                    </div>

                    <?php if ($step == 1): ?>
                        <!-- Step 1: Username -->
                        <h5 class="fw-bold mb-3">Identify Account</h5>
                        <p class="text-muted small mb-4">Enter your username to begin the recovery process.</p>
                        <form method="POST">
                            <input type="hidden" name="action" value="check_username">
                            <div class="form-floating mb-4">
                                <input type="text" name="username" class="form-control rounded-3" id="userInp" placeholder="Username" required value="<?php echo htmlspecialchars($username); ?>">
                                <label for="userInp">Username</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow-sm">
                                Continue <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </form>
                    <?php elseif ($step == 2): ?>
                        <!-- Step 2: Security Question -->
                        <h5 class="fw-bold mb-3">Verify Identity</h5>
                        <p class="text-muted small mb-1">Please answer your security question:</p>
                        <p class="fw-bold text-primary mb-4">"<?php echo htmlspecialchars($question); ?>"</p>
                        <form method="POST">
                            <input type="hidden" name="action" value="verify_answer">
                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
                            <div class="form-floating mb-4">
                                <input type="text" name="answer" class="form-control rounded-3" id="ansInp" placeholder="Your Answer" required autofocus autocomplete="off">
                                <label for="ansInp">Your Answer</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow-sm">
                                Verify Answer <i class="fas fa-shield-halved ms-2"></i>
                            </button>
                        </form>
                    <?php elseif ($step == 3): ?>
                        <!-- Step 3: New Password -->
                        <h5 class="fw-bold mb-3">Set New Password</h5>
                        <p class="text-muted small mb-4">Choose a strong password with at least 6 characters.</p>
                        <form method="POST">
                            <input type="hidden" name="action" value="reset_password">
                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
                            <div class="form-floating mb-3">
                                <input type="password" name="new_password" class="form-control rounded-3" id="newPass" placeholder="New Password" required minlength="6">
                                <label for="newPass">New Password</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="password" name="confirm_password" class="form-control rounded-3" id="confPass" placeholder="Confirm Password" required minlength="6">
                                <label for="confPass">Confirm Password</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow-sm">
                                Reset Password <i class="fas fa-save ms-2"></i>
                            </button>
                        </form>
                    <?php elseif ($step == 4): ?>
                        <!-- Step 4: Success -->
                        <div class="text-center py-4">
                            <div class="rounded-circle bg-success-subtle p-4 d-inline-block mb-4">
                                <i class="fas fa-check-circle fa-4x text-success"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Password Updated!</h4>
                            <p class="text-muted mb-4"><?php echo $success; ?></p>
                            <a href="login.php" class="btn btn-primary px-5 py-3 rounded-3 fw-bold shadow-sm">
                                Proceed to Login
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mt-4 pt-2 border-top">
                        <a href="login.php" class="text-secondary small text-decoration-none"><i class="fas fa-arrow-left me-2"></i> Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php if ($error): ?>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '<?php echo $error; ?>',
                confirmButtonColor: '#6366f1'
            });
        <?php endif; ?>
    </script>

</body>

</html>