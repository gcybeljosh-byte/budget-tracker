<?php
// index.php - iOS-Inspired Landing Page
session_start();
require_once 'includes/config.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['id'])) {
    $role = $_SESSION['role'] ?? 'user';
    if ($role === 'superadmin') {
        header("Location: " . SITE_URL . "admin/dashboard.php");
    } else {
        header("Location: " . SITE_URL . "core/dashboard.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Tracker - Master Your Finances</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
        }

        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .ios-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        @keyframes float {
            0% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-10px) rotate(2deg);
            }

            100% {
                transform: translateY(0px) rotate(0deg);
            }
        }

        .float-animation {
            animation: float 6s ease-in-out infinite;
        }
    </style>
</head>

<body class="text-slate-900 overflow-x-hidden">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 px-4 md:px-6 py-4">
        <div class="max-w-6xl mx-auto flex items-center justify-between glass px-4 md:px-6 py-2 md:py-3 rounded-2xl md:rounded-3xl ios-shadow">
            <div class="flex items-center gap-2 md:gap-3">
                <div class="w-8 h-8 md:w-10 md:h-10 flex items-center justify-center">
                    <img src="<?php echo SITE_URL; ?>assets/images/favicon.png" alt="Logo" class="w-full h-full object-contain">
                </div>
                <span class="font-bold text-lg md:text-xl tracking-tight text-slate-800 whitespace-nowrap">Budget<span class="text-indigo-600">Tracker</span></span>
            </div>
            <div class="flex items-center gap-1 md:gap-4">
                <a href="<?php echo SITE_URL; ?>auth/login.php" class="text-slate-600 font-bold text-sm md:text-base hover:text-indigo-600 ios-transition px-3 py-2 rounded-xl hover:bg-slate-50">Login</a>
                <a href="<?php echo SITE_URL; ?>auth/register.php" class="bg-indigo-600 text-white text-xs md:text-sm font-bold px-4 md:px-6 py-2 md:py-2.5 rounded-xl md:rounded-2xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 ios-transition whitespace-nowrap">Sign Up</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <main class="relative pt-32 pb-20 px-6 overflow-hidden">
        <!-- Decoration -->
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-100 rounded-full blur-3xl opacity-50 -z-10"></div>
        <div class="absolute top-1/2 -left-24 w-72 h-72 bg-purple-100 rounded-full blur-3xl opacity-50 -z-10"></div>

        <div class="max-w-6xl mx-auto text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 border border-indigo-100 rounded-full mb-6 md:mb-8 ios-shadow animate-bounce">
                <span class="flex h-2 w-2 rounded-full bg-indigo-500"></span>
                <span class="text-indigo-600 font-bold text-[10px] md:text-xs uppercase tracking-widest">Version 2.5 New Features</span>
            </div>

            <h1 class="text-4xl md:text-7xl font-extrabold tracking-tight text-slate-900 mb-6 md:mb-8 leading-[1.1]">
                Master your finances <br class="hidden md:block">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">with effortless clarity.</span>
            </h1>

            <p class="text-base md:text-xl text-slate-500 max-w-2xl mx-auto mb-10 md:mb-12 leading-relaxed px-4 md:px-0">
                Experience a revolutionary way to track expenses, manage savings, and reach your financial goals with an interface designed for simplicity.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 md:gap-6 mb-16 md:mb-20 px-4 md:px-0">
                <a href="<?php echo SITE_URL; ?>auth/register.php" class="w-full sm:w-auto bg-slate-900 text-white font-bold px-8 md:px-10 py-4 md:py-5 rounded-2xl md:rounded-[2rem] text-base md:text-lg shadow-xl shadow-slate-200 hover:bg-slate-800 hover:-translate-y-1 ios-transition flex items-center justify-center gap-3">
                    Start Your Path <i class="fas fa-arrow-right text-sm"></i>
                </a>
                <a href="#features" class="w-full sm:w-auto glass text-slate-700 font-bold px-8 md:px-10 py-4 md:py-5 rounded-2xl md:rounded-[2rem] text-base md:text-lg ios-shadow hover:bg-white hover:-translate-y-1 ios-transition">
                    Explore Features
                </a>
            </div>

            <!-- Dashboard Preview -->
            <div class="relative max-w-5xl mx-auto px-4 md:px-0 group reveal">
                <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-[2rem] md:rounded-[2.5rem] blur opacity-25 group-hover:opacity-40 ios-transition"></div>
                <div class="relative glass p-2 md:p-4 rounded-[2rem] md:rounded-[2.5rem] ios-shadow overflow-hidden">
                    <div class="aspect-[4/3] md:aspect-video rounded-[1.5rem] overflow-hidden bg-slate-100 flex items-center justify-center relative">
                        <!-- High Quality Mockup Image -->
                        <img src="https://images.unsplash.com/photo-1551288049-bbbda5366391?auto=format&fit=crop&q=80&w=2070" alt="Dashboard Preview" class="w-full h-full object-cover shadow-inner border border-slate-100 float-animation">

                        <!-- Mobile Centered Placeholder Info -->
                        <div class="absolute inset-0 flex items-center justify-center bg-black/40 md:hidden p-6 text-center">
                            <div class="bg-white/10 backdrop-blur-md border border-white/20 p-6 rounded-3xl">
                                <i class="fas fa-chart-line text-white text-4xl mb-3"></i>
                                <p class="text-white font-bold text-lg">Next-Gen Analytics</p>
                                <p class="text-white/80 text-sm mt-2">Track every penny with precision on any device.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Stat 1 -->
                    <div class="absolute top-12 left-12 glass p-4 md:p-6 rounded-2xl md:rounded-3xl ios-shadow md:block hidden float-animation" style="animation-delay: 1s;">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 text-green-600 rounded-xl md:rounded-2xl flex items-center justify-center">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <div class="text-left">
                                <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-widest">Net Profit</p>
                                <p class="text-lg md:text-xl font-extrabold text-slate-800">+₱24,500.00</p>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Stat 2 (Mobile and Desktop Friendly) -->
                    <div class="absolute bottom-12 right-12 glass p-4 md:p-6 rounded-2xl md:rounded-3xl ios-shadow md:block hidden float-animation" style="animation-delay: 2s;">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-indigo-100 text-indigo-600 rounded-xl md:rounded-2xl flex items-center justify-center">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <div class="text-left">
                                <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-widest">Goal Status</p>
                                <p class="text-lg md:text-xl font-extrabold text-slate-800">82% Reached</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Features Section -->
    <section id="features" class="py-24 px-6 bg-white overflow-hidden">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-20 reveal">
                <h2 class="text-4xl font-extrabold text-slate-900 mb-4 tracking-tight">Financial management, refined.</h2>
                <p class="text-slate-500 text-lg">Every feature built with an obsession for detail and user experience.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="p-8 rounded-[2.5rem] bg-slate-50 hover:bg-indigo-50 border border-slate-100 hover:border-indigo-100 ios-transition group reveal">
                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mb-8 ios-shadow group-hover:bg-indigo-600 ios-transition">
                        <i class="fas fa-bolt text-indigo-600 text-2xl group-hover:text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-4">Real-time Insights</h3>
                    <p class="text-slate-500 leading-relaxed">Instant updates on your spending habits with intuitive charts and real-time balance calculations.</p>
                </div>

                <!-- Feature 2 -->
                <div class="p-8 rounded-[2.5rem] bg-slate-50 hover:bg-purple-50 border border-slate-100 hover:border-purple-100 ios-transition group reveal" style="transition-delay: 0.2s;">
                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mb-8 ios-shadow group-hover:bg-purple-600 ios-transition">
                        <i class="fas fa-shield-alt text-purple-600 text-2xl group-hover:text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-4">Secure by Design</h3>
                    <p class="text-slate-500 leading-relaxed">Your data is yours. Protected with industry-standard encryption and secure authentication protocols.</p>
                </div>

                <!-- Feature 3 -->
                <div class="p-8 rounded-[2.5rem] bg-slate-50 hover:bg-emerald-50 border border-slate-100 hover:border-emerald-100 ios-transition group reveal" style="transition-delay: 0.4s;">
                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mb-8 ios-shadow group-hover:bg-emerald-600 ios-transition">
                        <i class="fas fa-robot text-emerald-600 text-2xl group-hover:text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-4">AI-Powered Hub</h3>
                    <p class="text-slate-500 leading-relaxed">Ask our intelligent assistant any financial question and get instant tailored advice for your budget.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 px-6 bg-slate-50 border-top border-slate-100">
        <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-piggy-bank text-white text-sm"></i>
                </div>
                <span class="font-bold text-lg tracking-tight text-slate-800">Budget<span class="text-indigo-600">Tracker</span></span>
            </div>
            <p class="text-slate-400 text-sm font-medium">© 2026 Budget Tracker. Precision & Simplicity.</p>
            <div class="flex items-center gap-6">
                <a href="#" class="text-slate-400 hover:text-indigo-600 ios-transition"><i class="fab fa-twitter text-lg"></i></a>
                <a href="#" class="text-slate-400 hover:text-indigo-600 ios-transition"><i class="fab fa-github text-lg"></i></a>
                <a href="#" class="text-slate-400 hover:text-indigo-600 ios-transition"><i class="fab fa-instagram text-lg"></i></a>
            </div>
        </div>
    </footer>
    <script>
        // Scroll Reveal Animation
        function reveal() {
            var reveals = document.querySelectorAll(".reveal");
            for (var i = 0; i < reveals.length; i++) {
                var windowHeight = window.innerHeight;
                var elementTop = reveals[i].getBoundingClientRect().top;
                var elementVisible = 150;
                if (elementTop < windowHeight - elementVisible) {
                    reveals[i].classList.add("active");
                } else {
                    reveals[i].classList.remove("active");
                }
            }
        }

        window.addEventListener("scroll", reveal);
        window.addEventListener("load", reveal); // Initial check
    </script>
</body>

</html>