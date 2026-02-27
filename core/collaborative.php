<?php
$pageTitle = 'Shared Wallets';
$pageHeader = 'Shared Wallets';
$extraNavContent = '<button class="btn btn-primary rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;" data-bs-toggle="modal" data-bs-target="#createGroupModal" title="New Group">
    <i class="fas fa-plus fa-lg"></i>
</button>';

include '../includes/header.php';
?>
<?php include '../includes/sidebar.php'; ?>

<!-- Page Content -->
<div id="page-content-wrapper">

    <?php include '../includes/navbar.php'; ?>

    <div class="container py-4">
        <!-- Header Section -->
        <div class="mb-4">
            <h2 class="fw-bold mb-1">Shared Wallets <span class="badge bg-primary-subtle text-primary rounded-pill align-middle ms-2" style="font-size: 0.8rem; vertical-align: middle;">BETA</span></h2>
            <p class="text-secondary small mb-0">Manage collaborative budgets with household or couples.</p>
        </div>

        <div class="row g-4">
            <!-- Groups Column -->
            <div class="col-lg-8">
                <div id="groupsList" class="row g-4">
                    <!-- Groups will be injected here -->
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>

            <!-- Pending Invitations Column -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h6 class="mb-0 fw-bold text-uppercase small text-secondary">Pending Invitations</h6>
                    </div>
                    <div class="card-body p-0">
                        <div id="invitationsList" class="list-group list-group-flush">
                            <!-- Invitations will be injected here -->
                            <div class="p-4 text-center text-muted small">No pending invitations</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Group Modal -->
    <div class="modal fade" id="createGroupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Create Shared Wallet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="createGroupForm">
                        <input type="hidden" name="action" value="create_group">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Group Name</label>
                            <input type="text" name="name" class="form-control rounded-3" placeholder="e.g. Household, Travel Fund" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Description (Optional)</label>
                            <textarea name="description" class="form-control rounded-3" rows="2" placeholder="What is this group for?"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold mt-2">Create Group</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Invite Member Modal -->
    <div class="modal fade" id="inviteMemberModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Invite Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="inviteMemberForm">
                        <input type="hidden" name="action" value="invite_member">
                        <input type="hidden" name="group_id" id="inviteGroupId">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">User Email</label>
                            <input type="email" name="email" class="form-control rounded-3" placeholder="Enter their email address" required>
                        </div>
                        <p class="extra-small text-muted mb-3"><i class="fas fa-info-circle me-1"></i> They will receive an invitation on their Collaborative dashboard.</p>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">Send Invitation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadMyGroups();

            // Create Group
            document.getElementById('createGroupForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch('../api/collaborative.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success', data.message, 'success');
                            bootstrap.Modal.getInstance(document.getElementById('createGroupModal')).hide();
                            this.reset();
                            loadMyGroups();
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
            });

            // Invite Member
            document.getElementById('inviteMemberForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch('../api/collaborative.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success', data.message, 'success');
                            bootstrap.Modal.getInstance(document.getElementById('inviteMemberModal')).hide();
                            this.reset();
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
            });
        });

        function loadMyGroups() {
            fetch('../api/collaborative.php?action=my_groups')
                .then(res => res.json())
                .then(data => {
                    const groupsList = document.getElementById('groupsList');
                    const invitationsList = document.getElementById('invitationsList');

                    let groupsHtml = '';
                    let invitesHtml = '';

                    const activeGroups = data.data.filter(g => g.member_status === 'active');
                    const pendingInvites = data.data.filter(g => g.member_status === 'pending');

                    if (activeGroups.length === 0) {
                        groupsHtml = `
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                        <div class="mb-3"><i class="fas fa-users-cog text-light" style="font-size: 3rem;"></i></div>
                        <h5 class="fw-bold">No Shared Wallets</h5>
                        <p class="text-secondary small">Create a group to start managing finances with others.</p>
                        <button class="btn btn-outline-primary rounded-pill px-4 btn-sm mx-auto" data-bs-toggle="modal" data-bs-target="#createGroupModal">Create First Group</button>
                    </div>
                </div>
            `;
                    } else {
                        activeGroups.forEach(group => {
                            groupsHtml += `
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden transition-all hover-lift">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="rounded-circle bg-primary-subtle p-3 text-primary">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <span class="badge rounded-pill bg-light text-secondary extra-small fw-bold">${group.member_count} Members</span>
                                </div>
                                <h5 class="fw-bold mb-1">${group.name}</h5>
                                <p class="text-secondary small mb-3 text-truncate">${group.description || 'Shared budget group'}</p>
                                <div class="d-flex gap-2">
                                <div class="d-flex gap-2">
                                    <button onclick="peekWallet(${group.id}, '${group.name.replace(/'/g, "\\'")}')" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold flex-grow-1">View Wallet</button>
                                    ${group.role === 'admin' ? `
                                        <button onclick="openInviteModal(${group.id})" class="btn btn-outline-primary btn-sm rounded-circle" title="Invite User">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                        });
                    }

                    if (pendingInvites.length === 0) {
                        invitesHtml = '<div class="p-4 text-center text-muted small">No pending invitations</div>';
                    } else {
                        pendingInvites.forEach(invite => {
                            invitesHtml += `
                    <div class="list-group-item p-3 border-0 border-bottom">
                        <p class="small mb-2 fw-bold">Invited to join: <span class="text-primary">${invite.name}</span></p>
                        <div class="d-flex gap-2">
                            <button onclick="respondInvite(${invite.membership_id}, 'active')" class="btn btn-success btn-sm rounded-pill px-3 extra-small fw-bold">Accept</button>
                            <button onclick="respondInvite(${invite.membership_id}, 'delete')" class="btn btn-outline-danger btn-sm rounded-pill px-3 extra-small fw-bold">Decline</button>
                        </div>
                    </div>
                `;
                        });
                    }

                    groupsList.innerHTML = groupsHtml;
                    invitationsList.innerHTML = invitesHtml;
                });
        }

        function openInviteModal(groupId) {
            document.getElementById('inviteGroupId').value = groupId;
            new bootstrap.Modal(document.getElementById('inviteMemberModal')).show();
        }

        function respondInvite(inviteId, status) {
            const formData = new FormData();
            formData.append('action', 'respond_invitation');
            formData.append('invite_id', inviteId);
            formData.append('status', status);

            fetch('../api/collaborative.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: data.message,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                        loadMyGroups();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
        }

        function peekWallet(groupId, groupName) {
            document.getElementById('peekWalletName').textContent = groupName;
            document.getElementById('peekWalletLink').href = `dashboard.php?group_id=${groupId}`;
            document.getElementById('peekWalletContent').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm"></div></div>';

            const peekModal = new bootstrap.Modal(document.getElementById('peekWalletModal'));
            peekModal.show();

            fetch(`../api/dashboard.php?group_id=${groupId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        let transactionsHtml = '';
                        if (data.recent_transactions && data.recent_transactions.length > 0) {
                            data.recent_transactions.slice(0, 5).forEach(tx => {
                                const isExpense = tx.type === 'expenses';
                                const color = isExpense ? 'text-danger' : 'text-success';
                                const symbol = window.userCurrency ? window.userCurrency.symbol : '₱';
                                transactionsHtml += `
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-light">
                                    <div>
                                        <div class="small fw-bold text-dark">${tx.description}</div>
                                        <div class="extra-small text-muted">${tx.date}</div>
                                    </div>
                                    <div class="small fw-bold ${color}">${isExpense ? '-' : '+'}${symbol}${parseFloat(tx.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                                </div>
                            `;
                            });
                        } else {
                            transactionsHtml = '<p class="text-center text-muted extra-small py-3">No recent transactions</p>';
                        }

                        const symbol = window.userCurrency ? window.userCurrency.symbol : '₱';
                        document.getElementById('peekWalletContent').innerHTML = `
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <div class="p-3 bg-primary-subtle rounded-3 text-center">
                                    <div class="extra-small text-uppercase fw-bold text-primary opacity-75 mb-1">Balance</div>
                                    <div class="h5 fw-bold text-primary mb-0">${symbol}${parseFloat(data.balance).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-danger-subtle rounded-3 text-center">
                                    <div class="extra-small text-uppercase fw-bold text-danger opacity-75 mb-1">Expenses</div>
                                    <div class="h5 fw-bold text-danger mb-0">${symbol}${parseFloat(data.total_expenses).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                                </div>
                            </div>
                        </div>
                        <h6 class="fw-bold small mb-3 text-uppercase text-secondary letter-spacing-1">Recent Activity</h6>
                        <div class="wallet-peek-tx-list">
                            ${transactionsHtml}
                        </div>
                    `;
                    } else {
                        document.getElementById('peekWalletContent').innerHTML = `<div class="alert alert-danger extra-small">${data.message}</div>`;
                    }
                });
        }
    </script>

    <!-- Wallet Peek Modal -->
    <div class="modal fade" id="peekWalletModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="modal-title fw-bold" id="peekWalletName">Wallet Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="peekWalletContent">
                    <!-- Data will be loaded via AJAX -->
                </div>
                <div class="modal-footer border-0 pt-0">
                    <a href="#" id="peekWalletLink" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">Open Full Dashboard</a>
                </div>
            </div>
        </div>
    </div>

</div> <!-- End of page-content-wrapper -->
</div> <!-- End of wrapper -->

<?php include '../includes/footer.php'; ?>