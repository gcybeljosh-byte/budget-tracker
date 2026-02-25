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

    if ($oncompleted == 0 && !in_array($_SESSION['role'] ?? 'user', ['superadmin', 'admin'])) {
        header("Location: " . SITE_URL . "core/onboarding.php");
    } else {
        header("Location: " . SITE_URL . "core/dashboard.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['google_auth'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, password, first_name, last_name, profile_picture, role, status, currency FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $db_password, $first_name, $last_name, $profile_picture, $role, $status, $currency);
            $stmt->fetch();

            if ($password === $db_password || password_verify($password, $db_password)) {
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

                    // Log successful login
                    logActivity($conn, $id, 'login', "User logged in successfully");

                    if ($_SESSION['role'] === 'superadmin') {
                        header("Location: " . SITE_URL . "admin/dashboard.php");
                    } else {
                        header("Location: " . SITE_URL . "core/dashboard.php");
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
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>assets/images/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .ios-shadow {
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.05);
        }

        .ios-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .bg-blob {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.3;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        .form-input {
            @apply w-full pl-12 pr-4 py-4 bg-slate-50 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-medium text-slate-700;
        }
    </style>
</head>

<body>
    <!-- Background Decorations -->
    <div class="bg-blob bg-indigo-200 -top-48 -left-48"></div>
    <div class="bg-blob bg-purple-200 -bottom-48 -right-48"></div>

    <div class="w-full max-w-md px-6 py-12 relative z-10">
        <!-- Back Button -->
        <div class="mb-8 text-center">
            <a href="<?php echo SITE_URL; ?>" class="inline-flex items-center gap-2 text-slate-500 hover:text-indigo-600 font-semibold text-sm ios-transition group">
                <i class="fas fa-arrow-left text-xs group-hover:-translate-x-1 transition-transform"></i>
                Back to Home
            </a>
        </div>

        <div class="glass p-8 md:p-10 rounded-[2.5rem] ios-shadow relative overflow-hidden">
            <div class="text-center mb-10">
                <div class="w-16 h-16 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-indigo-100 animate-float text-white">
                    <img src="<?php echo SITE_URL; ?>assets/images/favicon.png" alt="Logo" class="w-10 h-10 object-contain brightness-0 invert">
                </div>
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Welcome Back</h2>
                <p class="text-slate-500 mt-2 font-medium">Continue your financial journey</p>
            </div>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Username</label>
                    <div class="relative group">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 ios-transition">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" name="username" class="w-full pl-12 pr-4 py-4 bg-slate-50 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-medium text-slate-700 border" placeholder="Enter your username" required>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2 ml-1">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Password</label>
                        <a href="forgot_password.php" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-700 uppercase tracking-widest">Forgot?</a>
                    </div>
                    <div class="relative group">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 ios-transition">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="password" class="w-full pl-12 pr-12 py-4 bg-slate-50 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-medium text-slate-700 border" placeholder="••••••••" required>
                        <button type="button" onclick="togglePassword('password', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 ios-transition">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full bg-slate-900 text-white font-bold py-4 rounded-2xl shadow-xl shadow-slate-200 hover:bg-slate-800 hover:-translate-y-1 active:scale-[0.98] ios-transition flex items-center justify-center gap-3 mt-8">
                    Sign In <i class="fas fa-arrow-right text-xs"></i>
                </button>
            </form>

            <div class="my-8 flex items-center gap-4">
                <div class="flex-1 h-px bg-slate-100"></div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">OR</span>
                <div class="flex-1 h-px bg-slate-100"></div>
            </div>

            <!-- Google Sign In -->
            <div class="flex justify-center">
                <div id="g_id_onload"
                    data-client_id="<?php echo GOOGLE_CLIENT_ID; ?>"
                    data-context="signin"
                    data-ux_mode="popup"
                    data-callback="handleCredentialResponse"
                    data-auto_prompt="false">
                </div>
                <div class="g_id_signin"
                    data-type="standard"
                    data-shape="pill"
                    data-theme="outline"
                    data-text="signin_with"
                    data-size="large"
                    data-logo_alignment="left">
                </div>
            </div>

            <div class="mt-10 text-center">
                <p class="text-slate-500 text-sm font-medium">
                    New here? <a href="register.php" class="text-indigo-600 font-bold hover:text-indigo-700 inline-flex items-center gap-1">Create account <i class="fas fa-external-link-alt text-[10px]"></i></a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
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

        const urlParams = new URLSearchParams(window.location.search);

        <?php if ($error): ?>
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: '<?php echo $error; ?>',
                confirmButtonColor: '#4f46e5',
                customClass: {
                    popup: 'rounded-[2rem]',
                    confirmButton: 'rounded-xl px-6 py-2.5 font-bold'
                }
            });
        <?php endif; ?>

        if (urlParams.get('logout') === 'success') {
            const isAuto = urlParams.get('auto') === '1';
            Swal.fire({
                icon: isAuto ? 'warning' : 'success',
                title: isAuto ? 'Session Expired' : 'Logged Out',
                text: isAuto ? 'You have been logged out due to inactivity.' : 'Successfully logged out.',
                confirmButtonColor: '#4f46e5',
                timer: isAuto ? 5000 : 2000,
                customClass: {
                    popup: 'rounded-[2rem]',
                    confirmButton: 'rounded-xl px-6 py-2.5 font-bold'
                }
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        function handleCredentialResponse(response) {
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
            modeInput.value = 'login';

            form.appendChild(input);
            form.appendChild(modeInput);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>

</html>