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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['google_auth'])) {
    if (isMaintenanceMode($conn)) {
        $error = "🔧 The system is currently under scheduled maintenance. Registrations are temporarily disabled. Please try again later.";
    } else {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($first_name) || empty($last_name) || empty($email) || empty($contact_number) || empty($username) || empty($password) || empty($confirm_password)) {
            $error = "Please fill in all fields.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 8 || !preg_match("/[0-9]/", $password) || !preg_match("/[^a-zA-Z0-9]/", $password)) {
            $error = "Password must be at least 8 characters with a number and special character.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "Username or email already exists.";
            } else {
                $stmt_insert = $conn->prepare("INSERT INTO users (username, password, first_name, last_name, email, contact_number, auth_method, plaintext_password) VALUES (?, ?, ?, ?, ?, ?, 'Local', ?)");
                $stmt_insert->bind_param("sssssss", $username, $password, $first_name, $last_name, $email, $contact_number, $password);

                if ($stmt_insert->execute()) {
                    $success = "Registration successful! You can now login.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
                $stmt_insert->close();
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
    <title>Register - Budget Tracker</title>
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
            padding: 2rem 1rem;
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
            width: 600px;
            height: 600px;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.25;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .animate-float {
            animation: float 4s ease-in-out infinite;
        }
    </style>
</head>

<body>
    <div class="bg-blob bg-indigo-200 -top-48 -left-48"></div>
    <div class="bg-blob bg-purple-200 -bottom-48 -right-48"></div>

    <div class="w-full max-w-2xl px-6 relative z-10">
        <div class="mb-6 text-center">
            <a href="<?php echo SITE_URL; ?>" class="inline-flex items-center gap-2 text-slate-500 hover:text-indigo-600 font-semibold text-sm ios-transition group">
                <i class="fas fa-arrow-left text-xs group-hover:-translate-x-1 transition-transform"></i>
                Back to Home
            </a>
        </div>

        <div class="glass p-8 md:p-12 rounded-[2.5rem] ios-shadow">
            <div class="text-center mb-10">
                <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-xl ios-shadow animate-float">
                    <img src="<?php echo SITE_URL; ?>assets/images/favicon.png" alt="Logo" class="w-10 h-10 object-contain">
                </div>
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Join Budget Tracker</h2>
                <p class="text-slate-500 mt-2 font-medium">Start your journey to financial freedom</p>
            </div>

            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">First Name</label>
                        <input type="text" name="first_name" placeholder="John" class="w-full px-5 py-4 bg-slate-50 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-medium text-slate-700 border" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Last Name</label>
                        <input type="text" name="last_name" placeholder="Doe" class="w-full px-5 py-4 bg-slate-50 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-medium text-slate-700 border" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Email Address</label>
                        <input type="email" name="email" placeholder="john@example.com" class="w-full px-5 py-4 bg-slate-50 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-medium text-slate-700 border" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Contact Number</label>
                        <input type="text" name="contact_number" placeholder="09123456789" class="w-full px-5 py-4 bg-slate-50 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-medium text-slate-700 border" required>
                    </div>
                </div>

                <div class="bg-slate-50 p-6 rounded-3xl border border-dashed border-slate-200 space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Username</label>
                        <input type="text" name="username" placeholder="johndoe123" class="w-full px-5 py-4 bg-white border-transparent focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-medium text-slate-700 border" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Password</label>
                            <input type="password" name="password" placeholder="••••••••" class="w-full px-5 py-4 bg-white border-transparent focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-medium text-slate-700 border" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Confirm Password</label>
                            <input type="password" name="confirm_password" placeholder="••••••••" class="w-full px-5 py-4 bg-white border-transparent focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-medium text-slate-700 border" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-slate-900 text-white font-bold py-4 rounded-2xl shadow-xl shadow-slate-200 hover:bg-slate-800 hover:-translate-y-1 active:scale-[0.98] ios-transition flex items-center justify-center gap-3 mt-4">
                    Create Account <i class="fas fa-check-circle text-xs"></i>
                </button>
            </form>

            <div class="my-8 flex items-center gap-4">
                <div class="flex-1 h-px bg-slate-100"></div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">OR</span>
                <div class="flex-1 h-px bg-slate-100"></div>
            </div>

            <!-- Google Sign Up -->
            <div class="flex justify-center flex-col items-center gap-4">
                <div id="g_id_onload"
                    data-client_id="<?php echo GOOGLE_CLIENT_ID; ?>"
                    data-context="signup"
                    data-ux_mode="popup"
                    data-callback="handleCredentialResponse"
                    data-auto_prompt="false">
                </div>
                <div class="g_id_signin"
                    data-type="standard"
                    data-shape="pill"
                    data-theme="outline"
                    data-text="signup_with"
                    data-size="large"
                    data-logo_alignment="left">
                </div>
            </div>

            <div class="mt-10 text-center">
                <p class="text-slate-500 text-sm font-medium">
                    Already have an account? <a href="login.php" class="text-indigo-600 font-bold hover:text-indigo-700">Log in here</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);

        <?php if ($error): ?>
            Swal.fire({
                icon: 'error',
                title: 'Registration Error',
                text: '<?php echo $error; ?>',
                confirmButtonColor: '#4f46e5',
                customClass: {
                    popup: 'rounded-[2rem]',
                    confirmButton: 'rounded-xl px-6 py-2.5 font-bold'
                }
            });
        <?php endif; ?>

        <?php if ($success): ?>
            Swal.fire({
                icon: 'success',
                title: 'Welcome!',
                text: '<?php echo $success; ?>',
                confirmButtonColor: '#4f46e5',
                customClass: {
                    popup: 'rounded-[2rem]',
                    confirmButton: 'rounded-xl px-6 py-2.5 font-bold'
                }
            }).then(() => {
                window.location.href = 'login.php';
            });
        <?php endif; ?>

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
            modeInput.value = 'register';
            form.appendChild(input);
            form.appendChild(modeInput);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>

</html>