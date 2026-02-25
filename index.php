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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Caveat:wght@400;700&display=swap" rel="stylesheet">
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
                <span class="font-bold text-lg md:text-xl tracking-tight text-slate-800 whitespace-nowrap">BudgetTracker</span>
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

            <!-- Dynamic Dashboard Preview (Pure CSS/Tailwind) -->
            <div class="relative max-w-5xl mx-auto px-4 md:px-0 group reveal">
                <!-- Background Glow -->
                <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-[2rem] md:rounded-[2.5rem] blur opacity-25 group-hover:opacity-40 ios-transition"></div>

                <div class="relative glass p-4 md:p-8 rounded-[2rem] md:rounded-[2.5rem] ios-shadow overflow-hidden min-h-[400px] md:min-h-[500px] flex flex-col gap-6">
                    <!-- Internal Dashboard Mockup Header (Account Overview) -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-2">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-indigo-100 rounded-2xl flex items-center justify-center text-indigo-600 shadow-inner">
                                <i class="fas fa-user-circle text-2xl"></i>
                            </div>
                            <div class="text-left">
                                <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-widest">Signed in as</p>
                                <h5 class="font-bold text-slate-800 leading-tight">Alex Thompson <span class="hidden md:inline text-indigo-500 ml-1 text-xs px-2 py-0.5 bg-indigo-50 rounded-full">Pro</span></h5>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 bg-slate-50 px-4 py-2 rounded-2xl border border-slate-100 self-start md:self-auto">
                            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="text-[10px] md:text-xs font-bold text-slate-600">February 2026 Overview</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 flex-1">
                        <!-- Balance & Analytics Column -->
                        <div class="md:col-span-2 flex flex-col gap-6">
                            <!-- Animated Balance Cards -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="p-4 md:p-6 bg-white rounded-3xl ios-shadow border border-slate-50 float-animation">
                                    <p class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 md:mb-2">Total Balance</p>
                                    <h4 class="text-xl md:text-2xl font-extrabold text-slate-800">₱45,250.00</h4>
                                    <div class="mt-3 md:mt-4 flex items-center gap-2">
                                        <span class="px-2 py-0.5 bg-green-100 text-green-600 text-[9px] md:text-[10px] font-bold rounded-full">+12.5%</span>
                                        <span class="text-[9px] md:text-[10px] text-slate-400">vs last month</span>
                                    </div>
                                </div>
                                <div class="p-4 md:p-6 bg-slate-900 rounded-3xl shadow-xl float-animation" style="animation-delay: 0.5s;">
                                    <p class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 md:mb-2 text-slate-500">Monthly Spend</p>
                                    <h4 class="text-xl md:text-2xl font-extrabold text-white">₱12,800.00</h4>
                                    <div class="mt-3 md:mt-4 w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                                        <div class="bg-indigo-500 h-full rounded-full transition-all duration-1000 w-[65%]" style="transition-delay: 1s;"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Animated Chart Preview -->
                            <div class="flex-1 p-6 bg-white rounded-3xl ios-shadow border border-slate-50">
                                <div class="flex items-center justify-between mb-6">
                                    <h5 class="font-bold text-slate-800">Spending Overview</h5>
                                    <div class="flex gap-1">
                                        <div class="w-12 h-2 bg-indigo-500 rounded-full"></div>
                                        <div class="w-12 h-2 bg-slate-100 rounded-full"></div>
                                    </div>
                                </div>
                                <div class="flex items-end justify-between h-32 gap-3 px-2">
                                    <div class="w-full bg-slate-50 rounded-t-xl transition-all duration-1000 h-[40%]" style="transition-delay: 0.2s;"></div>
                                    <div class="w-full bg-indigo-500 rounded-t-xl transition-all duration-1000 h-[70%]" style="transition-delay: 0.4s;"></div>
                                    <div class="w-full bg-slate-50 rounded-t-xl transition-all duration-1000 h-[50%]" style="transition-delay: 0.6s;"></div>
                                    <div class="w-full bg-purple-500 rounded-t-xl transition-all duration-1000 h-[90%]" style="transition-delay: 0.8s;"></div>
                                    <div class="w-full bg-slate-50 rounded-t-xl transition-all duration-1000 h-[60%]" style="transition-delay: 1.0s;"></div>
                                    <div class="w-full bg-indigo-600 rounded-t-xl transition-all duration-1000 h-[30%]" style="transition-delay: 1.2s;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar / Transactions Column -->
                        <div class="flex flex-col gap-6">
                            <div class="p-6 bg-white rounded-3xl ios-shadow border border-slate-50 flex-1 flex flex-col gap-4 overflow-hidden relative">
                                <h5 class="font-bold text-slate-800 mb-2">Recent Activity</h5>

                                <!-- Simulated Transactions -->
                                <div class="space-y-4">
                                    <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-2xl hover:bg-slate-100 ios-transition cursor-default">
                                        <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center">
                                            <i class="fas fa-shopping-cart text-sm"></i>
                                        </div>
                                        <div class="flex-1 text-left">
                                            <p class="text-xs font-bold text-slate-700">Groceries</p>
                                            <p class="text-[10px] text-slate-400">2 mins ago</p>
                                        </div>
                                        <p class="text-xs font-bold text-red-500">-₱450</p>
                                    </div>
                                    <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-2xl hover:bg-slate-100 ios-transition cursor-default">
                                        <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center">
                                            <i class="fas fa-wallet text-sm"></i>
                                        </div>
                                        <div class="flex-1 text-left">
                                            <p class="text-xs font-bold text-slate-700">Salary</p>
                                            <p class="text-[10px] text-slate-400">1 hour ago</p>
                                        </div>
                                        <p class="text-xs font-bold text-green-500">+₱5k</p>
                                    </div>
                                    <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-2xl hover:bg-slate-100 ios-transition cursor-default">
                                        <div class="w-10 h-10 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center">
                                            <i class="fas fa-bolt text-sm"></i>
                                        </div>
                                        <div class="flex-1 text-left">
                                            <p class="text-xs font-bold text-slate-700">Electricity</p>
                                            <p class="text-[10px] text-slate-400">Earlier today</p>
                                        </div>
                                        <p class="text-xs font-bold text-red-500">-₱2k</p>
                                    </div>
                                </div>

                                <!-- Fade effect at the bottom -->
                                <div class="absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-white to-transparent pointer-events-none"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Dynamic Ecosystem Section -->
    <section id="ecosystem" class="py-24 px-6 bg-slate-50 relative overflow-hidden">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16 reveal">
                <h2 class="text-4xl md:text-5xl font-extrabold text-slate-900 mb-4 tracking-tight">The Ecosystem of Financial Freedom.</h2>
                <p class="text-slate-500 text-lg max-w-2xl mx-auto">16 integrated modules designed to work in perfect harmony, replacing the chaos of manual tracking.</p>
            </div>

            <!-- Feature Grid -->
            <div id="feature-grid" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 md:gap-6 mb-20 reveal">
                <?php
                $ecosystem = [
                    ['name' => 'Monthly Dashboard', 'icon' => 'tachometer-alt', 'color' => 'indigo', 'manual' => 'Mental Math'],
                    ['name' => 'Quick Access Hub', 'icon' => 'layer-group', 'color' => 'purple', 'manual' => 'Searching Pockets'],
                    ['name' => 'Monthly Statements', 'icon' => 'file-invoice', 'color' => 'blue', 'manual' => 'Bank Line Ups'],
                    ['name' => 'Precision Expenses', 'icon' => 'receipt', 'color' => 'orange', 'manual' => 'Scribbled Napkins'],
                    ['name' => 'AI Budget Planner', 'icon' => 'magic', 'color' => 'pink', 'manual' => 'Crystal Balls'],
                    ['name' => 'Budget Limits', 'icon' => 'sliders-h', 'color' => 'rose', 'manual' => 'Just Hoping'],
                    ['name' => 'Allowance Tracker', 'icon' => 'hand-holding-dollar', 'color' => 'emerald', 'manual' => 'Physical Notebooks'],
                    ['name' => 'Savings Sync', 'icon' => 'sync', 'color' => 'cyan', 'manual' => 'Under Mattress'],
                    ['name' => 'Bills Hub', 'icon' => 'file-invoice-dollar', 'color' => 'amber', 'manual' => 'Shoebox Receipts'],
                    ['name' => 'Safe-to-Spend', 'icon' => 'shield-halved', 'color' => 'sky', 'manual' => 'Flipping a Coin'],
                    ['name' => 'Goal Deep Dive', 'icon' => 'bullseye', 'color' => 'violet', 'manual' => 'Wishing on Stars'],
                    ['name' => 'Financial Journal', 'icon' => 'book', 'color' => 'slate', 'manual' => 'Scattered Post-its'],
                    ['name' => 'Expense Trends', 'icon' => 'chart-line', 'color' => 'green', 'manual' => 'Guessing Game'],
                    ['name' => 'Spending Heatmap', 'icon' => 'calendar-alt', 'color' => 'red', 'manual' => 'Coloring Books'],
                    ['name' => 'AI Assistant', 'icon' => 'robot', 'color' => 'fuchsia', 'manual' => 'Asking Strangers'],
                    ['name' => 'Hardened Security', 'icon' => 'lock', 'color' => 'indigo', 'manual' => 'Open Pockets'],
                ];
                foreach ($ecosystem as $f): ?>
                    <div class="feature-card group cursor-default" data-manual="<?php echo $f['manual']; ?>" data-feature="<?php echo $f['name']; ?>" data-icon="fa-<?php echo $f['icon']; ?>">
                        <div class="bg-white p-6 rounded-[2rem] ios-shadow border border-slate-100 flex flex-col items-center gap-4 ios-transition hover:-translate-y-1 h-full min-h-[160px] justify-center text-center">
                            <div class="feature-icon w-12 h-12 rounded-2xl flex items-center justify-center text-white bg-<?php echo $f['color']; ?>-500 shadow-lg shadow-<?php echo $f['color']; ?>-100 ios-transition">
                                <i class="fas fa-<?php echo $f['icon']; ?> fs-5"></i>
                            </div>
                            <span class="feature-label font-bold text-slate-800 text-sm md:text-base leading-tight"><?php echo $f['name']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- "Imagine Without" Toggle -->
            <div class="flex flex-col md:flex-row items-center justify-center gap-8 reveal">
                <div class="flex items-center gap-6">
                    <span class="text-slate-400 font-bold uppercase tracking-widest text-xs">Standard World</span>
                    <label class="relative inline-flex items-center cursor-pointer group">
                        <input type="checkbox" id="imagine-toggle" class="sr-only peer">
                        <div class="w-20 h-10 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:rounded-full after:h-8 after:w-9 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-indigo-600 peer-checked:to-purple-600 shadow-inner group-hover:scale-105 active:scale-95 ios-transition"></div>
                    </label>
                    <span class="text-indigo-600 font-bold uppercase tracking-widest text-xs">Imagine without BudgetTracker</span>
                </div>
            </div>
        </div>

        <!-- Custom Grid Styles -->
        <style>
            .feature-card.is-manual .feature-icon {
                background: #f1f5f9 !important;
                color: #94a3b8 !important;
                box-shadow: none !important;
                transform: rotate(15deg);
            }

            .feature-card.is-manual .feature-label {
                color: #94a3b8 !important;
                text-decoration: line-through;
            }

            .bg-indigo-500 {
                background-color: #6366f1;
            }

            .bg-purple-500 {
                background-color: #a855f7;
            }

            .bg-blue-500 {
                background-color: #3b82f6;
            }

            .bg-orange-500 {
                background-color: #f97316;
            }

            .bg-pink-500 {
                background-color: #ec4899;
            }

            .bg-rose-500 {
                background-color: #f43f5e;
            }

            .bg-emerald-500 {
                background-color: #10b981;
            }

            .bg-cyan-500 {
                background-color: #06b6d4;
            }

            .bg-amber-500 {
                background-color: #f59e0b;
            }

            .bg-sky-500 {
                background-color: #0ea5e9;
            }

            .bg-violet-500 {
                background-color: #8b5cf6;
            }

            .bg-slate-500 {
                background-color: #64748b;
            }

            .bg-green-500 {
                background-color: #22c55e;
            }

            .bg-red-500 {
                background-color: #ef4444;
            }

            .bg-fuchsia-500 {
                background-color: #d946ef;
            }

            .shadow-indigo-100 {
                box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2);
            }

            .shadow-purple-100 {
                box-shadow: 0 10px 15px -3px rgba(168, 85, 247, 0.2);
            }

            .shadow-blue-100 {
                box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.2);
            }

            .shadow-orange-100 {
                box-shadow: 0 10px 15px -3px rgba(249, 115, 22, 0.2);
            }

            .shadow-pink-100 {
                box-shadow: 0 10px 15px -3px rgba(236, 72, 153, 0.2);
            }

            .shadow-rose-100 {
                box-shadow: 0 10px 15px -3px rgba(244, 63, 94, 0.2);
            }

            .shadow-emerald-100 {
                box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.2);
            }

            .shadow-cyan-100 {
                box-shadow: 0 10px 15px -3px rgba(6, 182, 212, 0.2);
            }

            .shadow-amber-100 {
                box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.2);
            }

            .shadow-sky-100 {
                box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.2);
            }

            .shadow-violet-100 {
                box-shadow: 0 10px 15px -3px rgba(139, 92, 246, 0.2);
            }

            .shadow-slate-100 {
                box-shadow: 0 10px 15px -3px rgba(100, 116, 139, 0.2);
            }

            .shadow-green-100 {
                box-shadow: 0 10px 15px -3px rgba(34, 197, 94, 0.2);
            }

            .shadow-red-100 {
                box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.2);
            }

            .shadow-fuchsia-100 {
                box-shadow: 0 10px 15px -3px rgba(217, 70, 239, 0.2);
            }
        </style>
    </section>

    <!-- Legacy Features Section Refined -->
    <section id="features" class="py-24 px-6 bg-white overflow-hidden relative">
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

    <!-- Final CTA Section -->
    <section class="py-24 px-6 bg-white relative overflow-hidden">
        <div class="max-w-4xl mx-auto text-center relative z-10 reveal">
            <!-- Decorative Doodles -->
            <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-full h-24 pointer-events-none opacity-40">
                <svg viewBox="0 0 400 100" class="w-full h-full fill-none stroke-amber-400 stroke-[3] stroke-round">
                    <path d="M100,80 Q110,40 120,80 M130,70 Q140,30 150,70 M250,70 Q260,30 270,70 M280,80 Q290,40 300,80" class="animate-pulse" />
                    <path d="M50,50 L70,30 M60,60 L80,40 M320,30 L340,50 M330,40 L350,60" />
                </svg>
            </div>

            <h2 class="text-5xl md:text-7xl font-extrabold tracking-tight text-slate-900 mb-10 relative">
                <span class="font-['Caveat'] text-indigo-600 block mb-2 transform -rotate-2">Command</span>
                your <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-500">wealth journey</span>
            </h2>

            <div class="flex flex-col items-center gap-6">
                <a href="<?php echo SITE_URL; ?>auth/register.php" class="bg-indigo-600 text-white font-bold px-12 py-5 rounded-2xl text-xl shadow-2xl shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-1 ios-transition flex items-center justify-center gap-3">
                    Sign Up - It's Free
                </a>

                <div class="flex flex-col items-center gap-2">
                    <div class="text-teal-600 animate-bounce">
                        <i class="fas fa-arrow-up text-lg"></i>
                    </div>
                    <div class="flex flex-col text-slate-400 font-bold uppercase tracking-widest text-[10px] md:text-xs gap-1">
                        <span>No credit card required</span>
                        <span class="text-slate-300">Instant access</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Extra background blobs for the CTA -->
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-emerald-50 rounded-full blur-3xl opacity-40 -z-0"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-teal-50 rounded-full blur-3xl opacity-40 -z-0"></div>
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

        // Imagine Toggle Logic
        const imagineToggle = document.getElementById('imagine-toggle');
        const featureCards = document.querySelectorAll('.feature-card');

        imagineToggle.addEventListener('change', function() {
            featureCards.forEach((card, index) => {
                const label = card.querySelector('.feature-label');
                const icon = card.querySelector('.feature-icon i');
                const originalFeature = card.getAttribute('data-feature');
                const manualAlt = card.getAttribute('data-manual');
                const originalIcon = card.getAttribute('data-icon');

                // Staggered animation
                setTimeout(() => {
                    card.style.transform = 'scale(0.95) rotate(-2deg)';
                    setTimeout(() => {
                        if (this.checked) {
                            card.classList.add('is-manual');
                            label.textContent = manualAlt;
                            icon.className = 'fas fa-unlink'; // Broke/Manual icon
                        } else {
                            card.classList.remove('is-manual');
                            label.textContent = originalFeature;
                            icon.className = `fas ${originalIcon}`;
                        }
                        card.style.transform = '';
                    }, 150);
                }, index * 30);
            });
        });
    </script>
</body>

</html>