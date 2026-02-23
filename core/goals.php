<?php
$pageTitle  = 'Financial Goals';
$pageHeader = 'Financial Goals';
$extraNavContent = '<button class="btn btn-success rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;" data-bs-toggle="modal" data-bs-target="#addGoalModal">
    <i class="fas fa-plus fa-lg"></i>
</button>';
include '../includes/header.php';

if ($_SESSION['role'] === 'superadmin') {
    header("Location: " . SITE_URL . "admin/dashboard.php");
    exit;
}
?>
<?php include '../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">

        <!-- Header -->
        <div class="mb-4 fade-up">
            <p class="text-secondary small mb-0">Set saving targets and track your progress.</p>
        </div>

        <!-- Goals Grid -->
        <div class="row g-4" id="goalsGrid">
            <div class="col-12 text-center text-muted py-5">
                <i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>Loading goals...
            </div>
        </div>

    </div>
</div>

<!-- Add Goal Modal -->
<div class="modal fade" id="addGoalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-bullseye me-2 text-success"></i>New Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addGoalForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Goal Title</label>
                        <input type="text" id="goalTitle" class="form-control rounded-3" placeholder="e.g. New Laptop" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Target Amount (₱)</label>
                        <input type="number" id="goalTarget" class="form-control rounded-3" step="0.01" min="1" placeholder="0.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Deadline <span class="text-muted">(optional)</span></label>
                        <input type="date" id="goalDeadline" class="form-control rounded-3">
                    </div>
                    <button type="submit" class="btn btn-success w-100 rounded-pill fw-bold py-2">Create Goal</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Contribute Modal -->
<div class="modal fade" id="contributeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Add Funds</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3" id="contributeGoalName"></p>
                <input type="hidden" id="contributeGoalId">
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Amount (₱)</label>
                    <input type="number" id="contributeAmount" class="form-control rounded-3" step="0.01" min="1" placeholder="0.00">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Source Wallet</label>
                    <select id="contributeSource" class="form-select rounded-3">
                        <option value="Cash">Cash Wallet</option>
                        <option value="GCash">GCash</option>
                        <option value="Maya">Maya</option>
                        <option value="Bank">Bank Account</option>
                        <option value="Electronic">Electronic / Others</option>
                    </select>
                </div>
                <button id="contributeSubmit" class="btn btn-success w-100 rounded-pill fw-bold py-2">Add Funds</button>
            </div>
        </div>
    </div>
</div>

<!-- Goal Details Modal -->
<div class="modal fade" id="goalDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold" id="detailTitle">Goal Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <h2 class="display-6 fw-bold text-success mb-1" id="detailPct">0%</h2>
                    <p class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">Overall Progress</p>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block mb-1">Target Amount</small>
                            <span class="fw-bold fs-5" id="detailTarget">₱0.00</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block mb-1">Saved Amount</small>
                            <span class="fw-bold fs-5 text-success" id="detailSaved">₱0.00</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 p-3 bg-primary-subtle rounded-3 d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-coins text-primary fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-primary-emphasis d-block fw-bold small">Remaining Balance</small>
                        <span class="fw-bold" id="detailRemaining">₱0.00</span>
                    </div>
                </div>

                <hr class="my-4 opacity-10">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-secondary small fw-bold text-uppercase">Timeline</span>
                    <span id="detailDeadline" class="badge rounded-pill bg-light text-dark border">No Deadline</span>
                </div>

                <div id="detailTimelineInfo" class="small text-muted mb-4 text-center p-2 bg-light rounded-2">
                    Establish a deadline to track your daily required savings.
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary rounded-pill fw-bold py-2 edit-goal-from-detail">
                        <i class="fas fa-edit me-2"></i>Edit Goal
                    </button>
                    <button class="btn btn-success rounded-pill fw-bold py-2 contribute-from-detail">
                        <i class="fas fa-plus me-2"></i>Add Funds
                    </button>
                    <button class="btn btn-outline-secondary rounded-pill fw-bold py-2" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Goal Modal -->
<div class="modal fade" id="editGoalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2 text-primary"></i>Edit Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="editGoalForm">
                    <input type="hidden" id="editGoalId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Goal Title</label>
                        <input type="text" id="editGoalTitle" class="form-control rounded-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Target Amount (₱)</label>
                        <input type="number" id="editGoalTarget" class="form-control rounded-3" step="0.01" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-secondary">Deadline <span class="text-muted">(optional)</span></label>
                        <input type="date" id="editGoalDeadline" class="form-control rounded-3">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2">Update Goal</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .goal-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .goal-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
    }

    .progress {
        height: 10px;
        border-radius: 10px;
    }

    .goal-status-active {
        background: #d1fae5;
        color: #065f46;
    }

    .goal-status-completed {
        background: #dbeafe;
        color: #1e40af;
    }

    .goal-status-overdue {
        background: #fee2e2;
        color: #991b1b;
    }
</style>

<script>
    const SITE_URL = '<?php echo SITE_URL; ?>';

    function formatPHP(n) {
        return '₱' + parseFloat(n).toLocaleString('en-PH', {
            minimumFractionDigits: 2
        });
    }

    function getDaysLeft(deadline) {
        if (!deadline) return null;
        const diff = Math.ceil((new Date(deadline) - new Date()) / 86400000);
        return diff;
    }

    function renderGoals(goals) {
        const grid = document.getElementById('goalsGrid');
        if (!goals.length) {
            grid.innerHTML = `
            <div class="col-12 text-center py-5 text-muted">
                <i class="fas fa-bullseye fa-3x mb-3 opacity-25"></i>
                <p class="fw-semibold">No goals yet. Start by adding one!</p>
            </div>`;
            return;
        }

        grid.innerHTML = goals.map(g => {
            const pct = g.target_amount > 0 ? Math.min(100, (g.saved_amount / g.target_amount) * 100) : 0;
            const barColor = g.status === 'completed' ? 'bg-primary' : (pct > 75 ? 'bg-success' : (pct > 40 ? 'bg-warning' : 'bg-danger'));
            const daysLeft = getDaysLeft(g.deadline);
            const statusBadge = `<span class="badge rounded-pill px-3 goal-status-${g.status} small fw-bold text-uppercase">${g.status}</span>`;

            let deadlineText = '';
            if (g.deadline) {
                if (daysLeft > 0) deadlineText = `<span class="text-muted small"><i class="fas fa-calendar me-1"></i>${daysLeft} days left</span>`;
                else if (daysLeft === 0) deadlineText = `<span class="text-warning small">Due today</span>`;
                else deadlineText = `<span class="text-danger small">Overdue by ${Math.abs(daysLeft)} days</span>`;
            }

            return `
        <div class="col-md-6 col-lg-4 stagger-item">
            <div class="card border-0 shadow-sm rounded-4 p-4 goal-card h-100" onclick="showGoalDetails(${g.id})" style="cursor: pointer;">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div onclick="event.stopPropagation()">
                        <h6 class="fw-bold mb-1">${g.title}</h6>
                        ${statusBadge}
                    </div>
                    <div class="dropdown" onclick="event.stopPropagation()">
                        <button class="btn btn-sm btn-light rounded-circle" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3">
                            <li><a class="dropdown-item edit-goal-btn" href="#" data-id="${g.id}"><i class="fas fa-edit me-2 text-primary"></i>Edit Goal</a></li>
                            <li><a class="dropdown-item contribute-btn" href="#" data-id="${g.id}" data-title="${g.title}"><i class="fas fa-plus me-2 text-success"></i>Add Funds</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger delete-goal" href="#" data-id="${g.id}"><i class="fas fa-trash me-2"></i>Delete</a></li>
                        </ul>
                    </div>
                </div>

                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Saved</span>
                        <span class="fw-bold">${formatPHP(g.saved_amount)} / ${formatPHP(g.target_amount)}</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar ${barColor}" style="width: ${pct.toFixed(1)}%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">${pct.toFixed(1)}% complete</small>
                        ${deadlineText}
                    </div>
                </div>
            </div>
        </div>`;
        }).join('');

        // Bind events
        document.querySelectorAll('.contribute-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                document.getElementById('contributeGoalId').value = btn.dataset.id;
                document.getElementById('contributeGoalName').textContent = 'Adding funds to: ' + btn.dataset.title;
                document.getElementById('contributeAmount').value = '';
                new bootstrap.Modal(document.getElementById('contributeModal')).show();
            });
        });

        document.querySelectorAll('.delete-goal').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                Swal.fire({
                    title: 'Delete Goal?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete',
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#d33'
                }).then(result => {
                    if (result.isConfirmed) {
                        fetch(SITE_URL + 'api/financial_goals.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'delete',
                                id: btn.dataset.id
                            })
                        }).then(r => r.json()).then(d => {
                            if (d.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    icon: 'success',
                                    confirmButtonColor: '#6366f1'
                                });
                                loadGoals();
                            } else Swal.fire({
                                title: 'Error',
                                text: d.message,
                                icon: 'error',
                                confirmButtonColor: '#6366f1'
                            });
                        });
                    }
                });
            });
        });

        document.querySelectorAll('.edit-goal-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                openEditGoalModal(btn.dataset.id);
            });
        });
    }

    let currentGoals = [];

    function showGoalDetails(goalId) {
        const g = currentGoals.find(item => item.id == goalId);
        if (!g) return;

        document.getElementById('detailTitle').textContent = g.title;
        document.getElementById('detailTarget').textContent = formatPHP(g.target_amount);
        document.getElementById('detailSaved').textContent = formatPHP(g.saved_amount);

        const remaining = Math.max(0, g.target_amount - g.saved_amount);
        document.getElementById('detailRemaining').textContent = formatPHP(remaining);

        const pct = g.target_amount > 0 ? (g.saved_amount / g.target_amount) * 100 : 0;
        document.getElementById('detailPct').textContent = Math.min(100, pct).toFixed(1) + '%';

        const deadlineEl = document.getElementById('detailDeadline');
        const timelineInfo = document.getElementById('detailTimelineInfo');

        if (g.deadline) {
            deadlineEl.textContent = g.deadline;
            deadlineEl.className = 'badge rounded-pill bg-white text-primary border border-primary';

            const days = getDaysLeft(g.deadline);
            if (days > 0) {
                const dailyReq = remaining / days;
                timelineInfo.innerHTML = `<i class="fas fa-info-circle me-1"></i> You need to save <strong>${formatPHP(dailyReq)}</strong> daily to reach this goal.`;
                timelineInfo.className = 'small text-dark mb-4 text-center p-2 bg-info-subtle rounded-2';
            } else if (days === 0) {
                timelineInfo.textContent = "Goal is due today!";
                timelineInfo.className = 'small text-warning mb-4 text-center p-2 bg-warning-subtle rounded-2';
            } else {
                timelineInfo.textContent = "Goal is overdue.";
                timelineInfo.className = 'small text-danger mb-4 text-center p-2 bg-danger-subtle rounded-2';
            }
        } else {
            deadlineEl.textContent = 'No Deadline';
            deadlineEl.className = 'badge rounded-pill bg-light text-muted border';
            timelineInfo.textContent = "Set a deadline to see daily savings requirements.";
            timelineInfo.className = 'small text-muted mb-4 text-center p-2 bg-light rounded-2';
        }

        // Modal button handler
        const editBtn = document.querySelector('.edit-goal-from-detail');
        editBtn.onclick = () => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('goalDetailsModal'));
            if (modal) modal.hide();
            setTimeout(() => openEditGoalModal(g.id), 400);
        };

        const contribBtn = document.querySelector('.contribute-from-detail');
        contribBtn.onclick = () => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('goalDetailsModal'));
            if (modal) modal.hide();
            setTimeout(() => {
                document.getElementById('contributeGoalId').value = g.id;
                document.getElementById('contributeGoalName').textContent = 'Adding funds to: ' + g.title;
                document.getElementById('contributeAmount').value = '';
                new bootstrap.Modal(document.getElementById('contributeModal')).show();
            }, 400);
        };

        new bootstrap.Modal(document.getElementById('goalDetailsModal')).show();
    }

    function openEditGoalModal(id) {
        const g = currentGoals.find(item => item.id == id);
        if (!g) return;

        document.getElementById('editGoalId').value = g.id;
        document.getElementById('editGoalTitle').value = g.title;
        document.getElementById('editGoalTarget').value = g.target_amount;
        document.getElementById('editGoalDeadline').value = g.deadline || '';

        new bootstrap.Modal(document.getElementById('editGoalModal')).show();
    }

    document.getElementById('editGoalForm').addEventListener('submit', e => {
        e.preventDefault();
        const payload = {
            action: 'edit',
            id: document.getElementById('editGoalId').value,
            title: document.getElementById('editGoalTitle').value,
            target_amount: document.getElementById('editGoalTarget').value,
            deadline: document.getElementById('editGoalDeadline').value || null
        };
        fetch(SITE_URL + 'api/financial_goals.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        }).then(r => r.json()).then(d => {
            if (d.success) {
                bootstrap.Modal.getInstance(document.getElementById('editGoalModal')).hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Goal Updated!',
                    timer: 1500,
                    showConfirmButton: false
                });
                loadGoals();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: d.message,
                    confirmButtonColor: '#6366f1'
                });
            }
        });
    });

    function loadGoals() {
        fetch(SITE_URL + 'api/financial_goals.php')
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    currentGoals = d.data;
                    renderGoals(d.data);
                }
            });
    }

    // Add Goal
    document.getElementById('addGoalForm').addEventListener('submit', e => {
        e.preventDefault();
        const payload = {
            action: 'add',
            title: document.getElementById('goalTitle').value,
            target_amount: document.getElementById('goalTarget').value,
            deadline: document.getElementById('goalDeadline').value || null
        };
        fetch(SITE_URL + 'api/financial_goals.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        }).then(r => r.json()).then(d => {
            if (d.success) {
                bootstrap.Modal.getInstance(document.getElementById('addGoalModal')).hide();
                document.getElementById('addGoalForm').reset();
                Swal.fire({
                    icon: 'success',
                    title: 'Goal Created!',
                    timer: 1500,
                    showConfirmButton: false
                });
                loadGoals();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: d.message,
                    confirmButtonColor: '#6366f1'
                });
            }
        });
    });

    // Contribute
    document.getElementById('contributeSubmit').addEventListener('click', () => {
        const id = document.getElementById('contributeGoalId').value;
        const amount = document.getElementById('contributeAmount').value;
        const source_type = document.getElementById('contributeSource').value;
        if (!amount || amount <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Enter a valid amount',
                confirmButtonColor: '#6366f1'
            });
            return;
        }
        fetch(SITE_URL + 'api/financial_goals.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'contribute',
                id,
                amount,
                source_type
            })
        }).then(r => r.json()).then(d => {
            if (d.success) {
                bootstrap.Modal.getInstance(document.getElementById('contributeModal')).hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Funds Added!',
                    timer: 1500,
                    showConfirmButton: false
                });
                loadGoals();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: d.message,
                    confirmButtonColor: '#6366f1'
                });
            }
        });
    });

    loadGoals();

    // --- Page Tutorial ---
    <?php if (!isset($seen_tutorials['goals.php'])): ?>

        function startTutorial() {
            if (window.seenTutorials && window.seenTutorials['goals.php']) return;

            const steps = [{
                    title: '🎯 Financial Goals',
                    text: 'Set saving targets for things you care about — a new gadget, a vacation, an emergency fund — and track your progress here.'
                },
                {
                    title: '📊 Your Goals Grid',
                    text: 'Each card shows your goal name, how much you\'ve saved vs. target, a progress bar, and a deadline countdown.',
                    target: '#goalsGrid'
                },
                {
                    title: '💰 Add Funds',
                    text: 'Click "Add Funds" on a goal card (or via the ⋯ menu) to contribute money from your savings or wallet. The progress bar updates instantly.',
                },
                {
                    title: '✏️ Goal Details & Editing',
                    text: 'Click on any goal card to open its detail view — see daily savings requirements, progress percentage, and edit or fund the goal directly.',
                },
                {
                    title: '➕ Create a New Goal',
                    text: 'Click the green + button in the top-right to create a new goal. You can set a title, target amount, and optional deadline.',
                    target: '[data-bs-target="#addGoalModal"]'
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
                    markPageTutorialSeen('goals.php');
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
                    else if (result.dismiss === Swal.DismissReason.cancel) markPageTutorialSeen('goals.php');
                });
            }

            showStep(0);
        }

        setTimeout(startTutorial, 1200);
    <?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>