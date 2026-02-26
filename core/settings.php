<?php
$pageTitle = 'Settings';
$pageHeader = 'User Settings';
include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<!-- Page Content -->
<div id="page-content-wrapper">

    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div id="alertContainer"></div>

        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8 col-xl-7">

                <!-- Settings Navigation Buttons -->
                <div class="row g-3 mb-4 text-center">
                    <div class="col-4">
                        <button class="btn btn-primary text-white w-100 py-3 rounded-4 shadow-sm fw-bold border-0 d-flex flex-column align-items-center justify-content-center h-100 transition-all hover-lift" id="btnSecurity">
                            <i class="fas fa-shield-alt mb-2 fs-4"></i>
                            <span class="small" style="font-size: 0.7rem;">Security</span>
                        </button>
                    </div>

                    <?php if ($_SESSION['role'] === 'superadmin'): ?>
                        <div class="col-4">
                            <button class="btn btn-light w-100 py-3 rounded-4 shadow-sm fw-bold border-0 d-flex flex-column align-items-center justify-content-center h-100 transition-all toggle-btn-inactive hover-lift" id="btnSystem">
                                <i class="fas fa-cogs mb-2 fs-4"></i>
                                <span class="small" style="font-size: 0.7rem;">System</span>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="col-4">
                            <button class="btn btn-light w-100 py-3 rounded-4 shadow-sm fw-bold border-0 d-flex flex-column align-items-center justify-content-center h-100 transition-all toggle-btn-inactive hover-lift" id="btnPreferences">
                                <i class="fas fa-sliders-h mb-2 fs-4"></i>
                                <span class="small" style="font-size: 0.7rem;">Preferences</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="col-4">
                        <button class="btn btn-light w-100 py-3 rounded-4 shadow-sm fw-bold border-0 d-flex flex-column align-items-center justify-content-center h-100 transition-all toggle-btn-inactive hover-lift" id="btnAbout">
                            <i class="fas fa-info-circle mb-2 fs-4"></i>
                            <span class="small" style="font-size: 0.7rem;">About</span>
                        </button>
                    </div>
                </div>

                <style>
                    .transition-all {
                        transition: all 0.3s ease;
                    }

                    .hover-lift:hover {
                        transform: translateY(-3px);
                        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
                    }

                    .settings-section {
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

                    .toggle-btn-inactive {
                        background-color: #f8f9fa !important;
                        color: #6c757d !important;
                    }

                    .toggle-btn-inactive:hover {
                        background-color: #e9ecef !important;
                    }

                    /* Feature Colors */
                    :root {
                        --bs-indigo: #6366f1;
                        --bs-purple: #a855f7;
                        --bs-blue: #3b82f6;
                        --bs-orange: #f97316;
                        --bs-pink: #ec4899;
                        --bs-rose: #f43f5e;
                        --bs-emerald: #10b981;
                        --bs-cyan: #06b6d4;
                        --bs-amber: #f59e0b;
                        --bs-sky: #0ea5e9;
                        --bs-violet: #8b5cf6;
                        --bs-slate: #64748b;
                        --bs-green: #22c55e;
                        --bs-red: #ef4444;
                        --bs-fuchsia: #d946ef;
                    }
                </style>

                <!-- Security Settings Section -->
                <div id="securitySection" class="settings-section">
                    <div class="d-flex justify-content-between align-items-center mb-4 px-1">
                        <div>
                            <h4 class="fw-bold mb-0">Identity & Security</h4>
                            <p class="text-muted small mb-0">Manage your account credentials and recovery options.</p>
                        </div>
                        <button class="btn btn-outline-primary rounded-pill px-4 btn-sm fw-bold border-2" id="btnEditSecurity">
                            <i class="fas fa-lock me-1"></i> Edit
                        </button>
                    </div>

                    <!-- 1. Username Card -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3 small text-uppercase text-secondary">Identity Hub</h6>
                            <form id="usernameForm">
                                <input type="hidden" name="action" value="username">
                                <div class="mb-3">
                                    <label class="form-label text-secondary small text-uppercase fw-bold">Login Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0 rounded-start-3"><i class="fas fa-at text-muted"></i></span>
                                        <input type="text" class="form-control bg-light border-0 rounded-end-3 py-2" name="username" id="usernameInput" disabled>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 py-2 fw-bold mt-2" disabled>
                                        <i class="fas fa-user-edit me-2"></i>Update Username
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- 2. Password & Auth Card -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3 small text-uppercase text-secondary">Access Security</h6>
                            <div id="authMethodBadge"></div>

                            <form id="passwordForm">
                                <div class="mb-3">
                                    <label class="form-label text-secondary small text-uppercase fw-bold">Current Password</label>
                                    <input type="password" class="form-control bg-light border-0 rounded-3 py-2" name="current_password" disabled required placeholder="Enter current password">
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-secondary small text-uppercase fw-bold">New Password</label>
                                        <input type="password" class="form-control bg-light border-0 rounded-3 py-2" name="new_password" disabled required minlength="8" placeholder="Minimum 8 characters">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-secondary small text-uppercase fw-bold">Confirm New Password</label>
                                        <input type="password" class="form-control bg-light border-0 rounded-3 py-2" name="confirm_password" disabled required minlength="8" placeholder="Repeat new password">
                                    </div>
                                </div>
                                <div class="mt-4 text-center">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 py-2 fw-bold" disabled>
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- 3. Security Question Card -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3 small text-uppercase text-secondary">Account Recovery</h6>
                            <form id="securityQuestionForm">
                                <div class="mb-3">
                                    <label class="form-label text-secondary small text-uppercase fw-bold">Security Question</label>
                                    <select class="form-select bg-light border-0 rounded-3 py-2" name="security_question" id="securityQuestionSelect" disabled required>
                                        <option value="">Select a security question...</option>
                                        <option value="What was the name of your first pet?">What was the name of your first pet?</option>
                                        <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                        <option value="What was the name of your elementary school?">What was the name of your elementary school?</option>
                                        <option value="In what city were you born?">In what city were you born?</option>
                                        <option value="What is the name of your favorite book?">What is the name of your favorite book?</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-secondary small text-uppercase fw-bold">Answer</label>
                                    <input type="password" class="form-control bg-light border-0 rounded-3 py-2" name="security_answer" id="securityAnswerInput" placeholder="Enter answer to verify or update" disabled required>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 py-2 fw-bold" disabled>
                                        <i class="fas fa-shield-alt me-2"></i>Save Security Recovery
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- 4. Danger Zone -->
                    <div class="card border-0 shadow-sm rounded-4 border-start border-danger border-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-2 text-danger small text-uppercase">Danger Zone</h6>
                            <p class="text-muted small mb-3">Once you delete your account, there is no going back. Please be certain.</p>
                            <div class="text-center">
                                <button class="btn btn-outline-danger btn-sm rounded-pill px-4 fw-bold" id="btnDeleteAccount" disabled>
                                    <i class="fas fa-trash-alt me-2"></i>Delete My Account Permanently
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($_SESSION['role'] !== 'superadmin'): ?>
                    <!-- App Preferences Section -->
                    <div id="preferencesSection" class="settings-section">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center px-4">
                                <div>
                                    <h5 class="mb-0 fw-bold"><i class="fas fa-sliders-h me-2 text-primary"></i>App Preferences</h5>
                                    <p class="text-muted small mb-0">Personalize your experience and notifications.</p>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <form id="preferencesForm">
                                    <input type="hidden" name="action" value="preferences">

                                    <!-- 1. General Preferences -->
                                    <div class="mb-5">
                                        <h6 class="fw-bold mb-3 small text-uppercase text-secondary">General Settings</h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label text-secondary small text-uppercase fw-bold">Preferred Currency</label>
                                                <select class="form-select border-0 bg-light rounded-3 py-2" name="preferred_currency" id="prefCurrency">
                                                    <option value="PHP">Philippine Peso (₱)</option>
                                                    <option value="USD">US Dollar ($)</option>
                                                    <option value="EUR">Euro (€)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-secondary small text-uppercase fw-bold">Monthly Budget Goal</label>
                                                <input type="number" step="0.01" class="form-control border-0 bg-light rounded-3 py-2" name="monthly_budget_goal" id="prefGoal">
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4 text-secondary opacity-25">

                                    <!-- 2. Notification Center -->
                                    <div class="mb-5">
                                        <h6 class="fw-bold mb-3 small text-uppercase text-secondary">Notification Center</h6>
                                        <div class="card border-0 bg-light rounded-4 p-3 mb-3">
                                            <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0">
                                                <div>
                                                    <label class="form-check-label fw-bold text-dark d-block" for="notifBudget">Budget Reminders</label>
                                                    <span class="text-muted small">Daily alerts at 10am, 5pm, and 9pm to track your expenses.</span>
                                                </div>
                                                <input class="form-check-input ms-0 mt-0" type="checkbox" role="switch" name="notif_budget" id="notifBudget">
                                            </div>
                                        </div>
                                        <div class="card border-0 bg-light rounded-4 p-3">
                                            <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0">
                                                <div>
                                                    <label class="form-check-label fw-bold text-dark d-block" for="notifLowBalance">Low Balance Alerts</label>
                                                    <span class="text-muted small">Instant notification when your balance drops below ₱500.</span>
                                                </div>
                                                <input class="form-check-input ms-0 mt-0" type="checkbox" role="switch" name="notif_low_balance" id="notifLowBalance">
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4 text-secondary opacity-25">

                                    <!-- 3. Interface & Experience -->
                                    <div class="mb-5">
                                        <h6 class="fw-bold mb-3 small text-uppercase text-secondary">Interface & AI</h6>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label text-secondary small text-uppercase fw-bold">Tone Personality</label>
                                                <select class="form-select border-0 bg-light rounded-3 py-2" name="ai_tone" id="prefTone">
                                                    <option value="Professional">Professional & Direct</option>
                                                    <option value="Friendly">Friendly & Encouraging</option>
                                                    <option value="Strict">Strict & Analytical</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-primary px-5 py-3 fw-bold rounded-pill shadow-sm">
                                            <i class="fas fa-save me-2"></i>Save Preferences
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'superadmin'): ?>
                    <!-- System Management Section -->
                    <div id="systemSection" class="settings-section d-none">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center px-4">
                                <div>
                                    <h5 class="mb-0 fw-bold"><i class="fas fa-cogs me-2 text-primary"></i>System Management</h5>
                                    <p class="text-muted small mb-0">Control global system settings and maintenance.</p>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <!-- Maintenance Mode Toggle -->
                                <div class="card border-0 bg-light rounded-4 p-4 mb-3 border-start border-warning border-4">
                                    <div class="form-check form-switch d-flex justify-content-between align-items-center ps-0">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h6 class="fw-bold text-dark mb-0">Maintenance Mode</h6>
                                                <span id="maintenanceStatusBadge" class="badge rounded-pill bg-success small">Live</span>
                                            </div>
                                            <p class="text-muted small mb-0">When enabled, regular users and admins cannot login. Superadmins retain access.</p>
                                        </div>
                                        <input class="form-check-input ms-0 mt-0" type="checkbox" role="switch" id="maintenanceToggle" style="width: 3rem; height: 1.5rem;">
                                    </div>
                                </div>

                                <div class="alert alert-info rounded-4 border-0 small">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Note:</strong> Enabling maintenance mode will log out all currently active users (except yourself) upon their next request.
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- About System Section -->
                <div id="aboutSection" class="settings-section">
                    <div class="card border-0 shadow-lg rounded-4 mb-4 overflow-hidden" style="background: #ffffff;">
                        <!-- Premium Header -->
                        <div class="p-5 text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border-radius: 0 0 25px 25px;">
                            <!-- Background Illustration -->
                            <div class="position-absolute opacity-10" style="top: 0px; right: -30px; font-size: 11rem; transform: rotate(-5deg);">
                                <i class="fas fa-wallet"></i>
                            </div>

                            <div class="position-relative z-1">
                                <div class="d-flex align-items-center gap-4">
                                    <!-- Logo Container -->
                                    <div class="bg-white p-2 rounded-4 shadow-sm animate__animated animate__fadeIn d-flex align-items-center justify-content-center" style="width: 75px; height: 75px;">
                                        <div class="w-100 h-100 rounded-3 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #06b6d4 0%, #8b5cf6 100%);">
                                            <i class="fas fa-wallet text-white fs-4"></i>
                                        </div>
                                    </div>

                                    <!-- Title & Version -->
                                    <div>
                                        <h2 class="mb-0 fw-bold tracking-tight text-white mb-1" style="font-size: 2.1rem;"><?php echo APP_NAME; ?></h2>
                                        <div class="badge bg-white text-primary rounded-pill px-3 py-2 shadow-sm d-inline-flex align-items-center gap-2" style="font-size: 0.75rem; font-weight: 700;">
                                            <span class="text-primary" style="opacity: 0.8;">v2.5.0</span>
                                            <span class="text-muted opacity-25">•</span>
                                            <span class="text-primary">Enterprise Suite</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-4 p-md-5">
                            <!-- 1. General Objectives -->
                            <div class="mb-5">
                                <div class="d-flex align-items-center gap-2 mb-4">
                                    <div class="icon-box-sm bg-primary bg-opacity-10 text-primary rounded-3 p-2">
                                        <i class="fas fa-bullseye shadow-sm"></i>
                                    </div>
                                    <h5 class="fw-bold mb-0 text-dark">General Objectives</h5>
                                </div>
                                <div class="p-4 bg-light rounded-4 border-start border-primary border-4 shadow-sm">
                                    <ul class="text-secondary mb-0 lh-lg list-unstyled" style="font-size: 0.95rem;">
                                        <li>1. Provide a unified platform for managing allowances, expenses, savings, and historical statements.</li>
                                        <li>2. Empower users with real-time monthly tracking through automated dashboard resets.</li>
                                        <li>3. Proactively manage recurring costs with the Bills and Subscriptions Hub.</li>
                                        <li>4. Deliver real-time daily spending intelligence via the Safe-to-Spend Calculator.</li>
                                        <li>5. Ensure multi-level security with a 3-tier role system and session guards.</li>
                                        <li>6. Deliver a premium, mobile-responsive experience with desktop-class animations.</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- 2. Specific Objectives -->
                            <div class="mb-5">
                                <h6 class="fw-bold text-uppercase text-muted mb-4 px-2" style="font-size: 0.75rem; letter-spacing: 1.5px;">Specific Outcomes</h6>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="d-flex gap-3 p-2">
                                            <div class="text-success fs-4 mt-1"><i class="fas fa-check-circle"></i></div>
                                            <div>
                                                <h6 class="fw-bold mb-1">Financial Tracking</h6>
                                                <p class="text-muted small mb-0">Full CRUD support for allowances, expenses, and savings with decimal-precise tracking.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex gap-3 p-2">
                                            <div class="text-primary fs-4 mt-1"><i class="fas fa-tachometer-alt"></i></div>
                                            <div>
                                                <h6 class="fw-bold mb-1">Real-time Dashboard</h6>
                                                <p class="text-muted small mb-0">Monthly Resets for focused financial monitoring and immediate visibility of net performance.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex gap-3 p-2">
                                            <div class="text-info fs-4 mt-1"><i class="fas fa-chart-pie"></i></div>
                                            <div>
                                                <h6 class="fw-bold mb-1">Budget Limits</h6>
                                                <p class="text-muted small mb-0">Category-level caps with interactive progress tracking and AI-suggested limit plans.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex gap-3 p-2">
                                            <div class="text-warning fs-4 mt-1"><i class="fas fa-shield-alt"></i></div>
                                            <div>
                                                <h6 class="fw-bold mb-1">Hardened Security</h6>
                                                <p class="text-muted small mb-0">Multi-tier role system, Google OAuth integration, and proactive session guards for data privacy.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex gap-3 p-2">
                                            <div class="fs-4 mt-1" style="color: #8b5cf6;"><i class="fas fa-robot"></i></div>
                                            <div>
                                                <h6 class="fw-bold mb-1">Contextual AI</h6>
                                                <p class="text-muted small mb-0">AI Help Desk with grounded data access for personalized financial guidance and reflections.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex gap-3 p-2">
                                            <div class="text-danger fs-4 mt-1"><i class="fas fa-calculator"></i></div>
                                            <div>
                                                <h6 class="fw-bold mb-1">Spending Insight</h6>
                                                <p class="text-muted small mb-0">Safe-to-Spend Calculator and automated bill tracking for smart daily spending intelligence.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Core Features -->
                            <div class="mb-5">
                                <h6 class="fw-bold text-uppercase text-muted mb-4 px-2" style="font-size: 0.75rem; letter-spacing: 1.5px;">Core Ecosystem</h6>
                                <div class="row g-3">
                                    <?php
                                    $features = [
                                        ['tachometer-alt',      'name' => 'Monthly Dashboard',   'color' => 'indigo'],
                                        ['layer-group',         'name' => 'Quick Access Hub',    'color' => 'purple'],
                                        ['file-invoice',        'name' => 'Monthly Statements',  'color' => 'blue'],
                                        ['receipt',             'name' => 'Precision Expenses',  'color' => 'orange'],
                                        ['magic',               'name' => 'AI Budget Planner',   'color' => 'pink'],
                                        ['sliders-h',           'name' => 'Budget Limits',       'color' => 'rose'],
                                        ['hand-holding-dollar', 'name' => 'Allowance Tracker',   'color' => 'emerald'],
                                        ['sync',                'name' => 'Savings Sync',        'color' => 'cyan'],
                                        ['file-invoice-dollar', 'name' => 'Bills Hub',           'color' => 'amber'],
                                        ['shield-halved',       'name' => 'Safe-to-Spend',       'color' => 'sky'],
                                        ['bullseye',            'name' => 'Goal Deep Dive',      'color' => 'violet'],
                                        ['book',                'name' => 'Financial Journal',   'color' => 'slate'],
                                        ['chart-line',          'name' => 'Expense Trends',      'color' => 'green'],
                                        ['calendar-alt',        'name' => 'Spending Heatmap',    'color' => 'red'],
                                        ['robot',               'name' => 'AI Assistant',        'color' => 'fuchsia'],
                                        ['lock',                'name' => 'Hardened Security',   'color' => 'indigo'],
                                    ];
                                    foreach ($features as $f): ?>
                                        <div class="col-6 col-md-3">
                                            <div class="p-4 text-center bg-white border border-light rounded-4 shadow-sm h-100 hover-lift transition-all">
                                                <div class="mb-3 fs-3" style="color: var(--bs-<?php echo $f['color'] ?? 'primary'; ?>);"><i class="fas fa-<?php echo $f[0]; ?>"></i></div>
                                                <div class="small fw-bold text-dark" style="font-size: 0.85rem;"><?php echo $f['name']; ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="mt-5 pt-4 border-top">
                                <div class="d-flex flex-column align-items-center text-center">
                                    <p class="text-muted mb-2" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px;">Designed & Engineered by</p>
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="fw-bold fs-5 text-dark mb-1" style="letter-spacing: 1px;">CYBEL JOSH A. GAMIDO</div>
                                        <div class="small text-primary fw-medium">
                                            <i class="fas fa-envelope me-1"></i> cjagamido@usm.edu.ph
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            let isSecurityUnlocked = false; // Internal flag for current page session

            // Section Toggling Logic
            const btnSecurity = document.getElementById('btnSecurity');
            const btnPreferences = document.getElementById('btnPreferences');
            const btnSystem = document.getElementById('btnSystem');
            const btnAbout = document.getElementById('btnAbout');
            const securitySection = document.getElementById('securitySection');
            const preferencesSection = document.getElementById('preferencesSection');
            const systemSection = document.getElementById('systemSection');
            const aboutSection = document.getElementById('aboutSection');

            function setActiveSection(btn, section) {
                const sections = [securitySection, preferencesSection, systemSection, aboutSection].filter(Boolean);
                const buttons = [btnSecurity, btnPreferences, btnSystem, btnAbout].filter(Boolean);

                sections.forEach(s => s.classList.add('d-none'));
                buttons.forEach(b => {
                    b.classList.remove('btn-primary', 'text-white');
                    b.classList.add('btn-light', 'toggle-btn-inactive');
                });

                if (section) section.classList.remove('d-none');
                if (btn) {
                    btn.classList.remove('toggle-btn-inactive', 'btn-light');
                    btn.classList.add('btn-primary', 'text-white');
                }
            }

            // --- Persistence Logic: Check URL for active section ---
            const urlParams = new URLSearchParams(window.location.search);
            const activeSectionParam = urlParams.get('section');
            const userRole = '<?php echo $_SESSION['role']; ?>';

            if (activeSectionParam === 'about') {
                setActiveSection(btnAbout, aboutSection);
            } else if (activeSectionParam === 'security') {
                setActiveSection(btnSecurity, securitySection);
            } else if (activeSectionParam === 'system') {
                setActiveSection(btnSystem, systemSection);
            } else if (userRole === 'superadmin') {
                // Superadmins default to security
                setActiveSection(btnSecurity, securitySection);
            } else {
                // Admins and regular users default to preferences
                setActiveSection(btnPreferences, preferencesSection);
            }

            // 4. Update Preferences
            const prefTone = document.getElementById('prefTone');
            // AI Tone logic remains if needed, but the theme listener is gone.

            btnSecurity.addEventListener('click', () => {
                if (isSecurityUnlocked) {
                    setActiveSection(btnSecurity, securitySection);
                    return;
                }

                Swal.fire({
                    title: 'Verify Password',
                    text: 'Please enter your password to access security settings.',
                    input: 'password',
                    inputAttributes: {
                        autocapitalize: 'off',
                        autocorrect: 'off'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Confirm',
                    confirmButtonColor: '#6366f1',
                    showLoaderOnConfirm: true,
                    preConfirm: (password) => {
                        const formData = new FormData();
                        formData.append('action', 'verify_password');
                        formData.append('password', password);

                        return fetch('<?php echo SITE_URL; ?>api/settings.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => {
                                if (!response.ok) throw new Error(response.statusText);
                                return response.json();
                            })
                            .catch(error => {
                                Swal.showValidationMessage(`Request failed: ${error}`);
                            });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (result.value.success) {
                            isSecurityUnlocked = true;
                            setActiveSection(btnSecurity, securitySection);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Verification Failed',
                                text: result.value.message || 'Incorrect password. Please try again.',
                                confirmButtonColor: '#6366f1'
                            });
                        }
                    }
                });
            });

            if (btnPreferences) btnPreferences.addEventListener('click', () => setActiveSection(btnPreferences, preferencesSection));
            if (btnSystem) btnSystem.addEventListener('click', () => setActiveSection(btnSystem, systemSection));
            if (btnAbout) btnAbout.addEventListener('click', () => setActiveSection(btnAbout, aboutSection));

            // 1. Initial Data Load (Profile + Preferences)
            fetch('<?php echo SITE_URL; ?>api/profile.php')
                .then(res => res.json())
                .then(result => {
                    if (result.success && result.data) {
                        const user = result.data;
                        document.getElementById('usernameInput').value = user.username;

                        // Load Security Question
                        if (user.security_question) {
                            document.getElementById('securityQuestionSelect').value = user.security_question;
                        }

                        // Load Preferences if section exists
                        if (preferencesSection) {
                            document.getElementById('prefCurrency').value = user.preferred_currency || 'PHP';
                            document.getElementById('prefGoal').value = user.monthly_budget_goal || 5000;
                            document.getElementById('prefTone').value = user.ai_tone || 'Professional';
                            document.getElementById('notifBudget').checked = parseInt(user.notif_budget) === 1;
                            document.getElementById('notifLowBalance').checked = parseInt(user.notif_low_balance) === 1;
                        }

                        // Update Auth Method Badge
                        const authBadge = document.getElementById('authMethodBadge');
                        if (user.auth_method === 'Google') {
                            authBadge.innerHTML = '<i class="fab fa-google me-2"></i>Google Account';
                            authBadge.className = 'd-inline-block px-3 py-1 rounded-pill small fw-bold mb-3 bg-danger-subtle text-danger border border-danger border-opacity-10';
                        } else {
                            authBadge.innerHTML = '<i class="fas fa-user-lock me-2"></i>Local Account';
                            authBadge.className = 'd-inline-block px-3 py-1 rounded-pill small fw-bold mb-3 bg-primary-subtle text-primary border border-primary border-opacity-10';
                        }
                    }
                });

            // 1.1 Edit Mode Logic for Security
            const btnEditSecurity = document.getElementById('btnEditSecurity');
            let isEditMode = false;

            function toggleEditMode(enable) {
                isEditMode = enable;
                const securityFields = securitySection.querySelectorAll('input, select, button[type="submit"], #btnDeleteAccount');
                securityFields.forEach(field => field.disabled = !enable);

                if (enable) {
                    btnEditSecurity.innerHTML = '<i class="fas fa-times me-1"></i> Cancel';
                    btnEditSecurity.classList.replace('btn-outline-primary', 'btn-outline-danger');
                } else {
                    btnEditSecurity.innerHTML = '<i class="fas fa-lock me-1"></i> Edit';
                    btnEditSecurity.classList.replace('btn-outline-danger', 'btn-outline-primary');
                }
            }

            btnEditSecurity.addEventListener('click', () => toggleEditMode(!isEditMode));

            // 2. Username Update
            document.getElementById('usernameForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch('<?php echo SITE_URL; ?>api/profile.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) {
                            showAlert('Username updated!', 'success');
                            toggleEditMode(false); // Lock fields after success
                            const navName = document.getElementById('navbarProfileName');
                            if (navName && !navName.textContent.includes(' ')) {
                                navName.textContent = document.getElementById('usernameInput').value;
                            }
                        } else {
                            showAlert(result.message, 'error');
                        }
                    });
            });

            // 3. Password Update
            document.getElementById('passwordForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch('<?php echo SITE_URL; ?>api/change_password.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) {
                            showAlert('Password changed successfully', 'success');
                            toggleEditMode(false); // Lock fields after success
                            this.reset();
                            // Re-disable fields that were reset
                            toggleEditMode(false);
                        } else {
                            showAlert(result.message, 'error');
                        }
                    });
            });

            // 4. Preferences Update
            const preferencesForm = document.getElementById('preferencesForm');
            if (preferencesForm) {
                preferencesForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);

                    // Handle checkboxes manually
                    if (!formData.has('notif_budget')) formData.append('notif_budget', '0');
                    if (!formData.has('notif_low_balance')) formData.append('notif_low_balance', '0');

                    fetch('<?php echo SITE_URL; ?>api/profile.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(result => {
                            if (result.success) {
                                showAlert('Preferences saved!', 'success');
                                setTimeout(() => {
                                    window.location.href = 'settings.php?section=preferences&saved=1';
                                }, 1500);
                            } else {
                                showAlert(result.message, 'error');
                            }
                        });
                });
            }

            // 5. Security Question Update
            document.getElementById('securityQuestionForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'security_question');

                fetch('<?php echo SITE_URL; ?>api/profile.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) {
                            showAlert('Security question saved!', 'success');
                            toggleEditMode(false); // Lock fields after success
                            document.getElementById('securityAnswerInput').value = ''; // Clear answer for security
                        } else {
                            showAlert(result.message, 'error');
                        }
                    });
            });

            // 7. System Management Logic (Superadmin)
            if (systemSection) {
                const maintenanceToggle = document.getElementById('maintenanceToggle');
                const statusBadge = document.getElementById('maintenanceStatusBadge');

                // Initial Load
                const fetchStatus = () => {
                    const formData = new FormData();
                    formData.append('action', 'get_status');
                    fetch('<?php echo SITE_URL; ?>api/admin_system.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(result => {
                            if (result.success) {
                                const isMaint = result.status === 'true';
                                maintenanceToggle.checked = isMaint;
                                updateBadge(isMaint);
                            }
                        });
                };

                const updateBadge = (isMaint) => {
                    if (isMaint) {
                        statusBadge.textContent = 'Under Maintenance';
                        statusBadge.classList.replace('bg-success', 'bg-warning');
                        statusBadge.classList.add('text-dark');
                    } else {
                        statusBadge.textContent = 'Live';
                        statusBadge.classList.replace('bg-warning', 'bg-success');
                        statusBadge.classList.remove('text-dark');
                    }
                };

                maintenanceToggle.addEventListener('change', function() {
                    const isChecked = this.checked;
                    Swal.fire({
                        title: isChecked ? 'Enable Maintenance Mode?' : 'Disable Maintenance Mode?',
                        text: isChecked ?
                            'This will block all Admins and Regular Users from accessing the system.' : 'All users will be able to access the system again.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Proceed',
                        confirmButtonColor: '#6366f1'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const formData = new FormData();
                            formData.append('action', 'toggle_maintenance');
                            formData.append('status', isChecked ? 'true' : 'false');

                            fetch('<?php echo SITE_URL; ?>api/admin_system.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(res => res.json())
                                .then(result => {
                                    if (result.success) {
                                        updateBadge(isChecked);
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Status Updated',
                                            text: result.message,
                                            timer: 2000,
                                            showConfirmButton: false
                                        });
                                    } else {
                                        this.checked = !isChecked; // Revert toggle
                                        showAlert(result.message, 'error');
                                    }
                                });
                        } else {
                            this.checked = !isChecked; // Revert toggle
                        }
                    });
                });

                fetchStatus();
            }

            // 6. Delete Account Logic (Double Confirmation)
            const btnDeleteAccount = document.getElementById('btnDeleteAccount');
            btnDeleteAccount.addEventListener('click', function() {
                Swal.fire({
                    title: 'Close Account Forever?',
                    text: "This action is permanent and will wipe all your budget data, expenses, and history. Are you absolutely sure?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, proceed to confirm',
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#d33',
                    cancelButtonText: 'No, keep my account'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Type DELETE to confirm',
                            text: 'To prevent accidental deletion, please type "DELETE" below to permanently close your account.',
                            input: 'text',
                            inputPlaceholder: 'DELETE',
                            showCancelButton: true,
                            confirmButtonText: 'Permanently Delete Account',
                            confirmButtonColor: '#d33',
                            showLoaderOnConfirm: true,
                            preConfirm: (input) => {
                                if (input.toUpperCase() !== 'DELETE') {
                                    Swal.showValidationMessage('Please type "DELETE" exactly to confirm.');
                                    return false;
                                }

                                return fetch('<?php echo SITE_URL; ?>api/delete_account.php', {
                                        method: 'POST'
                                    })
                                    .then(response => {
                                        if (!response.ok) throw new Error(response.statusText);
                                        return response.json();
                                    })
                                    .catch(error => {
                                        Swal.showValidationMessage(`Request failed: ${error}`);
                                    });
                            },
                            allowOutsideClick: () => !Swal.isLoading()
                        }).then((finalResult) => {
                            if (finalResult.isConfirmed && finalResult.value.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Account Deleted',
                                    text: 'Your account and data have been removed. Redirecting you to home...',
                                    showConfirmButton: false,
                                    confirmButtonColor: '#6366f1',
                                    timer: 3000
                                }).then(() => {
                                    window.location.href = '<?php echo SITE_URL; ?>index.php';
                                });
                            }
                        });
                    }
                });
            });

            function showAlert(msg, icon) {
                Swal.fire({
                    icon: icon,
                    title: icon.charAt(0).toUpperCase() + icon.slice(1),
                    text: msg,
                    showConfirmButton: false,
                    confirmButtonColor: '#6366f1',
                    timer: 2000
                });
            }

            // --- Page Tutorial ---
            <?php if (!isset($seen_tutorials['settings.php'])): ?>
                if (!(window.seenTutorials && window.seenTutorials['settings.php'])) {
                    const steps = [{
                            title: '⚙️ Settings',
                            text: 'This page has three sections — Security, Preferences, and About. Use the buttons at the top to switch between them.'
                        },
                        {
                            title: '🛡️ Security Tab',
                            text: 'Change your login username, update your password, and set a security question for account recovery. Click the "Edit" button first to unlock the fields.',
                            target: '#btnSecurity'
                        },
                        {
                            title: '🔒 Verify to Access',
                            text: 'For your safety, the Security section requires you to enter your current password before making any changes.'
                        },
                        <?php if ($_SESSION['role'] !== 'superadmin'): ?> {
                                title: '🎨 Preferences Tab',
                                text: 'Set your preferred currency, monthly budget goal, AI tone personality, and notification alerts (budget reminders & low balance).',
                                target: '#btnPreferences'
                            },
                        <?php endif; ?> {
                            title: '🗑️ Danger Zone',
                            text: 'The red "Delete My Account" button permanently erases all your data. It requires double confirmation — use with extreme care!',
                            target: '#btnDeleteAccount'
                        }
                    ];

                    if (!document.getElementById('tutorial-styles')) {
                        const style = document.createElement('style');
                        style.id = 'tutorial-styles';
                        style.textContent = `.tutorial-highlight { outline: 4px solid #6366f1; outline-offset: 4px; border-radius: 12px; transition: outline 0.3s ease; z-index: 9999; position: relative; }`;
                        document.head.appendChild(style);
                    }

                    function showStep(index) {
                        if (index >= steps.length) {
                            markPageTutorialSeen('settings.php');
                            return;
                        }
                        const step = steps[index];
                        Swal.fire({
                            title: step.title,
                            text: step.text,
                            icon: 'info',
                            confirmButtonText: index === steps.length - 1 ? '🎉 Got it!' : 'Next →',
                            confirmButtonColor: '#6366f1',
                            showCancelButton: true,
                            cancelButtonText: 'Skip Tour',
                            reverseButtons: true,
                            allowOutsideClick: false,
                            didOpen: () => {
                                if (step.target) {
                                    const el = document.querySelector(step.target);
                                    if (el) {
                                        el.scrollIntoView({
                                            behavior: 'smooth',
                                            block: 'center'
                                        });
                                        el.classList.add('tutorial-highlight');
                                        setTimeout(() => el.classList.remove('tutorial-highlight'), 2500);
                                    }
                                }
                            }
                        }).then((result) => {
                            if (result.isConfirmed) showStep(index + 1);
                            else if (result.dismiss === Swal.DismissReason.cancel) markPageTutorialSeen('settings.php');
                        });
                    }

                    setTimeout(() => showStep(0), 1000);
                }
            <?php endif; ?>
        });
    </script>

    <?php include '../includes/footer.php'; ?>