<?php
session_start();
if (isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

include 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, password, first_name, last_name, profile_picture, role, status, currency FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashed_password, $first_name, $last_name, $profile_picture, $role, $status, $currency);
            $stmt->fetch();
            
            if (password_verify($password, $hashed_password)) {
                if ($status === 'inactive') {
                    $error = "Your account is currently inactive. Please contact the administrator.";
                } else {
                    $_SESSION['id'] = $id;
                    $_SESSION['username'] = $username;
                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['last_name'] = $last_name;
                    $_SESSION['profile_picture'] = $profile_picture;
                    $_SESSION['role'] = $role;
                    $_SESSION['user_currency'] = $currency ?? 'PHP';
                    $_SESSION['login_time'] = date("Y-m-d H:i:s");
                    
                    if ($role === 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit;
                }
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "User not found.";
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
    <title>Login - Budget Tracker</title>
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
        <div class="col-md-7 col-lg-5">
            <div class="text-center mb-5">
                <div class="mb-3 d-inline-flex align-items-center justify-content-center bg-white shadow-sm rounded-circle p-3">
                    <i class="fas fa-wallet fa-2x text-primary"></i>
                </div>
                <h2 class="h4 fw-bold brand-text mb-1">Budget Tracker</h2>
                <p class="text-muted small">Manage your finances with ease.</p>
            </div>
            
            <div class="auth-card mx-auto">
                <h5 class="text-center mb-4 fw-bold text-dark">Welcome Back</h5>
                
                <form method="POST">
                    <div class="form-floating mb-3">
                        <input type="text" name="username" class="form-control rounded-3" id="floatingInput" placeholder="Username" required>
                        <label for="floatingInput">Username</label>
                    </div>
                    <div class="form-floating mb-4">
                        <input type="password" name="password" class="form-control rounded-3" id="floatingPassword" placeholder="Password" required>
                        <label for="floatingPassword">Password</label>
                    </div>
                    <div class="text-end mb-4">
                        <a href="forgot_password.php" class="text-secondary small text-decoration-none hover-primary">Forgot Password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-3 fw-bold shadow-sm">
                        <i class="fas fa-arrow-right me-2"></i>Sign In
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <p class="text-muted mb-0">Don't have an account? <a href="register.php" class="text-primary fw-bold text-decoration-none">Register here</a></p>
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
        title: 'Login Failed',
        text: '<?php echo $error; ?>',
        confirmButtonColor: '#6366f1'
    });
    <?php endif; ?>

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('logout') === 'success') {
        const isAuto = urlParams.get('auto') === '1';
        Swal.fire({
            icon: isAuto ? 'warning' : 'success',
            title: isAuto ? 'Session Expired' : 'Logged Out',
            text: isAuto ? 'You have been logged out due to 5 minutes of inactivity for your security.' : 'You have been successfully logged out.',
            confirmButtonColor: '#6366f1',
            timer: isAuto ? 5000 : 2000,
            showConfirmButton: isAuto
        });
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // --- Google OAuth ---
    function handleGoogleAuth() {
        // Redirect to Google Apps Script -> which redirects to google_auth.php
        // Replace with your deploy ID
        const scriptUrl = "https://script.google.com/macros/s/AKfycbyGhF-3_fzHaZv9Aoi8362EgyEnZBkvjqx6yOjq3IJA5_5iKehE-t8Rj_-vLv__xw8/exec"; 
        window.location.href = scriptUrl + "?action=login";
    }

    // Inject Google Button
    const loginForm = document.querySelector('form');
    if(loginForm) {
        const googleBtn = document.createElement('button');
        googleBtn.type = 'button'; 
        googleBtn.className = 'btn btn-outline-dark w-100 py-3 rounded-3 fw-bold shadow-sm mt-3';
        googleBtn.innerHTML = '<i class="fab fa-google me-2"></i>Login with Google';
        googleBtn.onclick = handleGoogleAuth;
        
        loginForm.appendChild(document.createElement('div')).className = 'text-center my-3 text-muted small';
        loginForm.lastChild.innerText = 'OR';
        loginForm.appendChild(googleBtn);
    }
</script>

</body>
</html>
