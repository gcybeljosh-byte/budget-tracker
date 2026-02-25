<?php
session_start();
if (isset($_SESSION['id'])) {
    header("Location: " . SITE_URL . "core/dashboard.php");
    exit;
}

include '../includes/config.php';
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
                    $error = "No security question set for this account. Please contact an admin.";
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
        $step = 2; // Keep at step 2 if verification fails

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
                $success = "Password reset successfully! You can now login.";
                $step = 4;
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
    </style>
</head>

<body>
    <div class="bg-blob bg-indigo-200 -top-48 -left-48"></div>
    <div class="bg-blob bg-purple-200 -bottom-48 -right-48"></div>

    <div class="w-full max-w-md px-6 py-12 relative z-10">
        <div class="mb-8 text-center">
            <a href="login.php" class="inline-flex items-center gap-2 text-slate-500 hover:text-indigo-600 font-semibold text-sm ios-transition group">
                <i class="fas fa-arrow-left text-xs group-hover:-translate-x-1 transition-transform"></i>
                Back to Login
            </a>
        </div>

        <div class="glass p-8 md:p-10 rounded-[2.5rem] ios-shadow">
            <div class="text-center mb-10">
                <div class="w-16 h-16 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-indigo-100 animate-float text-white text-2xl">
                    <i class="fas fa-key"></i>
                </div>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Recover Account</h2>
                <p class="text-slate-500 mt-2 font-medium">Follow the steps to reset password</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-600 text-xs font-bold rounded-2xl flex items-center gap-3">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="check_username">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Username</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" class="w-full pl-12 pr-4 py-4 bg-slate-50 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-medium text-slate-700 border" placeholder="Enter your username" required>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-slate-900 text-white font-bold py-4 rounded-2xl shadow-xl shadow-slate-200 hover:bg-slate-800 hover:-translate-y-1 ios-transition flex items-center justify-center gap-2">
                        Next Step <i class="fas fa-arrow-right text-xs"></i>
                    </button>
                </form>

            <?php elseif ($step == 2): ?>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="verify_answer">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
                    <div class="bg-indigo-50 p-6 rounded-3xl border border-indigo-100 mb-6">
                        <label class="block text-[10px] font-bold text-indigo-400 uppercase tracking-widest mb-2">Security Question</label>
                        <p class="text-indigo-900 font-bold leading-relaxed"><?php echo htmlspecialchars($question); ?></p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Your Answer</label>
                        <input type="text" name="answer" class="w-full px-5 py-4 bg-slate-50 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-medium text-slate-700 border" placeholder="Enter answer here" required>
                    </div>
                    <button type="submit" class="w-full bg-slate-900 text-white font-bold py-4 rounded-2xl shadow-xl shadow-slate-200 hover:bg-slate-800 hover:-translate-y-1 ios-transition">
                        Verify Answer
                    </button>
                    <button type="button" onclick="history.back()" class="w-full text-slate-400 font-bold text-sm hover:text-slate-600 ios-transition pt-2">
                        Go Back
                    </button>
                </form>

            <?php elseif ($step == 3): ?>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">New Password</label>
                        <input type="password" name="new_password" class="w-full px-5 py-4 bg-slate-50 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-medium text-slate-700 border" placeholder="••••••••" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Confirm Password</label>
                        <input type="password" name="confirm_password" class="w-full px-5 py-4 bg-slate-50 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-medium text-slate-700 border" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-4 rounded-2xl shadow-xl shadow-indigo-100 hover:bg-indigo-700 hover:-translate-y-1 ios-transition">
                        Reset Password
                    </button>
                </form>

            <?php elseif ($step == 4): ?>
                <div class="text-center py-6">
                    <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                        <i class="fas fa-check"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2 italic">Success!</h3>
                    <p class="text-slate-500 mb-8"><?php echo $success; ?></p>
                    <a href="login.php" class="inline-block bg-slate-900 text-white font-bold px-10 py-4 rounded-2xl shadow-xl shadow-slate-200 hover:bg-slate-800 hover:-translate-y-1 ios-transition">
                        Go to Login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Alert handle for backend success from redirected page if any
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('reset') === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Your password has been reset.',
                confirmButtonColor: '#4f46e5',
                customClass: {
                    popup: 'rounded-[2rem]',
                    confirmButton: 'rounded-xl px-6 py-2.5 font-bold'
                }
            });
        }
    </script>
</body>

</html>