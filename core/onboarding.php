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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            padding: 2rem 1rem;
        }

        .glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
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

        .step-content {
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

        .category-chip {
            cursor: pointer;
            padding: 0.6rem 1.2rem;
            margin: 0.3rem;
            border-radius: 1rem;
            border: 1.5px solid #e2e8f0;
            display: inline-block;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.85rem;
            font-weight: 600;
            background: white;
            color: #64748b;
        }

        .category-chip.selected {
            background-color: #6366f1;
            border-color: #6366f1;
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
            transform: translateY(-2px);
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

    <div class="w-full max-w-xl relative z-10 px-4">
        <div class="glass rounded-[2.5rem] ios-shadow overflow-hidden">
            <!-- Progress Bar -->
            <div class="h-1.5 bg-slate-100 flex">
                <div id="progress" class="h-full bg-indigo-600 transition-all duration-500 ease-out" style="width: 20%;"></div>
            </div>

            <form id="onboardingForm" class="p-8 md:p-12">
                <!-- Step 1: Welcome & Currency -->
                <div class="step-content active" id="step1">
                    <div class="w-20 h-20 bg-indigo-50 text-indigo-600 rounded-3xl flex items-center justify-center mx-auto mb-8 text-3xl animate-float">
                        <i class="fas fa-hand-sparkles"></i>
                    </div>
                    <div class="text-center mb-10">
                        <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-3">Welcome, <?php echo htmlspecialchars($first_name); ?>!</h2>
                        <p class="text-slate-500 font-medium">Let's personalize your financial workspace.</p>
                    </div>

                    <div class="mb-8">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Preferred Currency</label>
                        <select name="currency" class="w-full px-5 py-4 bg-slate-50 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-bold text-slate-700 border">
                            <option value="PHP">Philippine Peso (₱)</option>
                            <option value="USD">US Dollar ($)</option>
                            <option value="EUR">Euro (€)</option>
                            <option value="JPY">Japanese Yen (¥)</option>
                        </select>
                    </div>

                    <button type="button" class="w-full bg-slate-900 text-white font-bold py-4 rounded-2xl shadow-xl shadow-slate-200 hover:bg-slate-800 hover:-translate-y-1 active:scale-[0.98] ios-transition next-step" data-next="2">
                        Get Started <i class="fas fa-arrow-right ml-2 text-xs"></i>
                    </button>
                </div>

                <!-- Step 2: Profile Customization -->
                <div class="step-content" id="step2">
                    <div class="text-center mb-10">
                        <div class="relative inline-block mb-8">
                            <div class="w-32 h-32 bg-indigo-600 text-white rounded-[2.5rem] flex items-center justify-center overflow-hidden border-4 border-white shadow-xl ios-shadow">
                                <img id="profilePreview" src="" alt="Profile" class="hidden w-full h-full object-cover">
                                <i id="defaultIcon" class="fas fa-user text-4xl"></i>
                            </div>
                            <label for="profile_upload" class="absolute -bottom-2 -right-2 bg-white w-10 h-10 rounded-full shadow-lg flex items-center justify-center cursor-pointer hover:scale-110 ios-transition text-indigo-600">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" name="profile_picture" id="profile_upload" class="hidden" accept="image/*">
                        </div>
                        <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-2">Build Your Identity</h3>
                        <p class="text-slate-500 font-medium text-sm">Set your display name and profile picture.</p>
                    </div>

                    <div class="mb-8">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Nickname</label>
                        <input type="text" name="nickname" class="w-full px-5 py-4 bg-slate-50 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-bold text-slate-700 border" placeholder="e.g. Finance Wizard" value="<?php echo htmlspecialchars($first_name); ?>">
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <button type="button" class="flex-1 py-4 text-slate-500 font-bold hover:text-slate-700 font-medium ios-transition prev-step" data-prev="1">Back</button>
                        <button type="button" class="flex-[2] bg-slate-900 text-white font-bold py-4 rounded-2xl shadow-xl shadow-slate-200 hover:bg-slate-800 hover:-translate-y-1 active:scale-[0.98] ios-transition next-step" data-next="3">Next Step</button>
                    </div>
                </div>

                <!-- Step 3: Categories -->
                <div class="step-content" id="step3">
                    <div class="text-center mb-10">
                        <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-2">Spending Categories</h3>
                        <p class="text-slate-500 font-medium text-sm">Select the categories you'll use to track expenses.</p>
                    </div>

                    <div id="categoryContainer" class="bg-slate-50 p-6 rounded-[2rem] border border-dashed border-slate-200 mb-10 flex flex-wrap justify-center">
                        <?php
                        $defaults = ['Food & Dining', 'Transportation', 'Rent & Utilities', 'Entertainment', 'Shopping', 'Healthcare', 'Education', 'Savings', 'Other'];
                        foreach ($defaults as $cat): ?>
                            <div class="category-chip" data-name="<?php echo $cat; ?>"><?php echo $cat; ?></div>
                        <?php endforeach; ?>
                        <input type="hidden" name="selected_categories" id="selected_categories">
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <button type="button" class="flex-1 py-4 text-slate-500 font-bold hover:text-slate-700 font-medium ios-transition prev-step" data-prev="2">Back</button>
                        <button type="button" class="flex-[2] bg-slate-900 text-white font-bold py-4 rounded-2xl shadow-xl shadow-slate-200 hover:bg-slate-800 hover:-translate-y-1 active:scale-[0.98] ios-transition next-step" data-next="4">Next Step</button>
                    </div>
                </div>

                <!-- Step 4: Goals -->
                <div class="step-content" id="step4">
                    <div class="text-center mb-10">
                        <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-2">Financial Boundaries</h3>
                        <p class="text-slate-500 font-medium text-sm">Set a monthly spending limit to help you stay on track.</p>
                    </div>

                    <div class="mb-10">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Monthly Limit</label>
                        <div class="relative group">
                            <span class="absolute left-5 top-1/2 -translate-y-1/2 font-bold text-slate-400 group-focus-within:text-indigo-600 ios-transition">
                                <?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?>
                            </span>
                            <input type="number" name="budget_goal" class="w-full pl-12 pr-5 py-4 bg-slate-50 border-transparent focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl ios-transition outline-none font-bold text-slate-700 border" placeholder="0.00" value="5000">
                        </div>
                        <p class="mt-4 text-[11px] text-slate-400 font-medium text-center italic">
                            <i class="fas fa-info-circle mr-1"></i> We'll alert you if you're approaching this limit.
                        </p>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <button type="button" class="flex-1 py-4 text-slate-500 font-bold hover:text-slate-700 font-medium ios-transition prev-step" data-prev="3">Back</button>
                        <button type="button" class="flex-[2] bg-slate-900 text-white font-bold py-4 rounded-2xl shadow-xl shadow-slate-200 hover:bg-slate-800 hover:-translate-y-1 active:scale-[0.98] ios-transition next-step" data-next="5">Last Step</button>
                    </div>
                </div>

                <!-- Step 5: Review -->
                <div class="step-content" id="step5">
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-2">Account Review</h3>
                        <p class="text-slate-500 font-medium text-sm">Confirm your credentials for future access.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 mb-8">
                        <div class="bg-indigo-50/50 p-5 rounded-2xl border border-indigo-100/50">
                            <label class="block text-[10px] font-bold text-indigo-400 uppercase tracking-[0.15em] mb-1">Display Name</label>
                            <div id="reviewNickname" class="text-slate-700 font-extrabold text-lg">Pending...</div>
                        </div>

                        <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">Username</label>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-700 font-bold truncate mr-4"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                <button type="button" onclick="copyValue('onboardingUsername', this)" class="text-indigo-600 hover:scale-110 ios-transition">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <input type="hidden" id="onboardingUsername" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                            </div>
                        </div>

                        <?php
                        $pass_exists = isset($_SESSION['google_registered_password']) || isset($_SESSION['temp_registration_password']);
                        $pass = $_SESSION['google_registered_password'] ?? ($_SESSION['temp_registration_password'] ?? '********');
                        ?>
                        <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-[0.15em] mb-2">Password</label>
                            <div class="flex items-center justify-between">
                                <div class="relative flex-1 mr-4 overflow-hidden">
                                    <input type="password" id="onboardingPassword" value="<?php echo htmlspecialchars($pass); ?>" class="bg-transparent border-none p-0 w-full text-slate-700 font-bold outline-none" readonly>
                                </div>
                                <div class="flex items-center gap-3">
                                    <?php if ($pass_exists): ?>
                                        <button type="button" onclick="togglePasswordDisplay('onboardingPassword', this)" class="text-slate-400 hover:text-indigo-600 ios-transition">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" onclick="copyValue('onboardingPassword', this)" class="text-indigo-600 hover:scale-110 ios-transition">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-[10px] text-slate-400 italic">Encrypted</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 mb-8 flex gap-3 items-start">
                        <i class="fas fa-shield-alt text-blue-500 mt-1"></i>
                        <p class="text-[11px] text-blue-700 font-medium leading-relaxed">
                            Please save these details securely. They are your primary keys to accessing your dashboard.
                        </p>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <button type="button" class="flex-1 py-4 text-slate-500 font-bold hover:text-slate-700 font-medium ios-transition prev-step" data-prev="4">Back</button>
                        <button type="submit" class="flex-[2] bg-indigo-600 text-white font-bold py-4 rounded-2xl shadow-xl shadow-indigo-100 hover:bg-indigo-700 hover:-translate-y-1 active:scale-[0.98] ios-transition">
                            Complete Setup <i class="fas fa-check-circle ml-2 text-xs"></i>
                        </button>
                    </div>
                </div>
            </form>

            <div class="bg-slate-50/50 p-6 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-400 font-medium">
                    Not you? <a href="../auth/logout.php" class="text-indigo-600 font-bold hover:underline">Sign out & Start Over</a>
                </p>
            </div>
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

            // Start with no categories selected (User will choose)
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
                        profilePreview.classList.remove('hidden');
                        defaultIcon.classList.add('hidden');
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });

            window.copyValue = function(elementId, btn) {
                const copyText = document.getElementById(elementId);
                const originalValue = copyText.value;

                navigator.clipboard.writeText(originalValue).then(() => {
                    const originalIcon = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check text-green-500"></i>';
                    setTimeout(() => {
                        btn.innerHTML = originalIcon;
                    }, 2000);
                });
            };

            window.togglePasswordDisplay = function(elementId, btn) {
                const passInput = document.getElementById(elementId);
                const icon = btn.querySelector('i');
                if (passInput.type === 'password') {
                    passInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            };

            // Form Submit
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(form);

                Swal.fire({
                    title: 'Finalizing Setup',
                    text: 'Preparing your premium financial dashboard...',
                    background: '#ffffff',
                    color: '#1e293b',
                    confirmButtonColor: '#6366f1',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                    customClass: {
                        popup: 'rounded-[2rem] shadow-xl border-0',
                    }
                });

                fetch('<?php echo SITE_URL; ?>api/onboarding.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Welcome Aboard!',
                                text: 'Your setup is complete. Let\'s start tracking!',
                                timer: 2000,
                                showConfirmButton: false,
                                customClass: {
                                    popup: 'rounded-[2rem]',
                                }
                            }).then(() => {
                                window.location.href = '<?php echo SITE_URL; ?>core/dashboard.php';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Setup Issue',
                                text: data.message || 'Something went wrong',
                                confirmButtonColor: '#6366f1'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'System Error',
                            text: 'We couldn\'t finalize your setup at this time.',
                            confirmButtonColor: '#6366f1'
                        });
                    });
            });
        });
    </script>

</body>

</html>