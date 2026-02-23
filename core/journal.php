<?php
$pageTitle = 'Financial Journal';
$pageHeader = 'Financial Journal';
$extraNavContent = '<button class="btn btn-primary rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;" data-bs-toggle="modal" data-bs-target="#addJournalModal">
    <i class="fas fa-plus fa-lg"></i>
</button>';
include '../includes/header.php';
?>

<?php include '../includes/sidebar.php'; ?>

<!-- Page Content -->
<div id="page-content-wrapper">

    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">

        <?php
        // Handle Journal Creation
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_journal') {
            $title = trim($_POST['title']);
            $date = $_POST['date'];
            $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
            $notes = trim($_POST['notes']);
            $status = $_POST['financial_status'];
            $warning = isset($_POST['overspending_warning']) ? 1 : 0;

            if (!empty($title) && !empty($date)) {
                $stmt = $conn->prepare("INSERT INTO journals (user_id, date, end_date, title, notes, financial_status, overspending_warning) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssi", $_SESSION['id'], $date, $endDate, $title, $notes, $status, $warning);
                if ($stmt->execute()) {
                    $journal_id = $stmt->insert_id;

                    // Handle Manual Ledger Lines
                    if (isset($_POST['lines_account']) && is_array($_POST['lines_account'])) {
                        $lineStmt = $conn->prepare("INSERT INTO journal_lines (journal_id, account_title, debit, credit) VALUES (?, ?, ?, ?)");
                        for ($i = 0; $i < count($_POST['lines_account']); $i++) {
                            $account = trim($_POST['lines_account'][$i]);
                            $debit = floatval($_POST['lines_debit'][$i]);
                            $credit = floatval($_POST['lines_credit'][$i]);

                            if (!empty($account) && ($debit > 0 || $credit > 0)) {
                                $lineStmt->bind_param("isdd", $journal_id, $account, $debit, $credit);
                                $lineStmt->execute();
                            }
                        }
                        $lineStmt->close();
                    }

                    echo '<script>
                            document.addEventListener("DOMContentLoaded", function() {
                                Swal.fire({
                                    icon: "success",
                                    title: "Entry Added",
                                    text: "Your journal entry has been saved successfully.",
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = "journal.php";
                                });
                            });
                        </script>';
                } else {
                    echo '<div class="alert alert-danger">Error saving entry.</div>';
                }
                $stmt->close();
            }
        }

        // Handle Budget Goal Update
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_goal') {
            $goal = floatval($_POST['monthly_budget_goal']);
            if ($goal > 0) {
                $stmt = $conn->prepare("UPDATE users SET monthly_budget_goal = ? WHERE id = ?");
                $stmt->bind_param("di", $goal, $_SESSION['id']);
                if ($stmt->execute()) {
                    echo '<script>
                            document.addEventListener("DOMContentLoaded", function() {
                                Swal.fire({
                                    icon: "success",
                                    title: "Goal Updated",
                                    text: "Your monthly budget goal has been updated.",
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = "journal.php"; 
                                });
                            });
                        </script>';
                }
                $stmt->close();
            }
        }

        // Handle Journal Edit
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_journal') {
            $id = (int)$_POST['journal_id'];
            $title = trim($_POST['title']);
            $date = $_POST['date'];
            $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
            $notes = trim($_POST['notes']);
            $status = $_POST['financial_status'];
            $warning = isset($_POST['overspending_warning']) ? 1 : 0;

            if (!empty($title) && !empty($date) && $id > 0) {
                $stmt = $conn->prepare("UPDATE journals SET date=?, end_date=?, title=?, notes=?, financial_status=?, overspending_warning=? WHERE id=? AND user_id=?");
                $stmt->bind_param("ssssiii", $date, $endDate, $title, $notes, $status, $warning, $id, $_SESSION['id']);
                if ($stmt->execute()) {
                    // Update Lines: Delete old -> Insert new (Simple approach)
                    $delStmt = $conn->prepare("DELETE FROM journal_lines WHERE journal_id = ?");
                    $delStmt->bind_param("i", $id);
                    $delStmt->execute();
                    $delStmt->close();

                    if (isset($_POST['lines_account']) && is_array($_POST['lines_account'])) {
                        $lineStmt = $conn->prepare("INSERT INTO journal_lines (journal_id, account_title, debit, credit) VALUES (?, ?, ?, ?)");
                        for ($i = 0; $i < count($_POST['lines_account']); $i++) {
                            $account = trim($_POST['lines_account'][$i]);
                            $debit = floatval($_POST['lines_debit'][$i]);
                            $credit = floatval($_POST['lines_credit'][$i]);

                            if (!empty($account) && ($debit > 0 || $credit > 0)) {
                                $lineStmt->bind_param("isdd", $id, $account, $debit, $credit);
                                $lineStmt->execute();
                            }
                        }
                        $lineStmt->close();
                    }

                    echo '<script>
                            document.addEventListener("DOMContentLoaded", function() {
                                Swal.fire({
                                    title: "Updated!",
                                    text: "Your journal entry has been updated.",
                                    icon: "success",
                                    confirmButtonColor: "#6366f1"
                                }).then(() => window.location.href = "journal.php");
                            });
                        </script>';
                }
                $stmt->close();
            }
        }

        // Handle Journal Delete
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_journal') {
            $id = (int)$_POST['journal_id'];
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM journals WHERE id=? AND user_id=?");
                $stmt->bind_param("ii", $id, $_SESSION['id']);
                if ($stmt->execute()) {
                    echo '<script>
                            document.addEventListener("DOMContentLoaded", function() {
                                Swal.fire({
                                    title: "Deleted!",
                                    text: "Your journal entry has been removed.",
                                    icon: "success",
                                    confirmButtonColor: "#6366f1"
                                }).then(() => window.location.href = "journal.php");
                            });
                        </script>';
                }
                $stmt->close();
            }
        }

        // Fetch Current Budget Goal
        $budgetGoal = 0;
        $currency = $_SESSION['user_currency'] ?? 'PHP';
        $currencySymbol = CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP');

        $stmt = $conn->prepare("SELECT monthly_budget_goal FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $budgetGoal = floatval($row['monthly_budget_goal']);
        }
        $stmt->close();
        ?>

        <div class="d-flex justify-content-end mb-4">
            <?php if ($budgetGoal > 0): ?>
                <div class="bg-white rounded-pill px-3 py-1 shadow-sm border d-flex align-items-center" role="button" data-bs-toggle="modal" data-bs-target="#budgetGoalModal">
                    <i class="fas fa-bullseye text-primary me-2"></i>
                    <div>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; display: block; line-height: 1;">Monthly Goal</small>
                        <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?php echo $currencySymbol . number_format($budgetGoal, 2); ?></span>
                    </div>
                </div>
            <?php else: ?>
                <button class="btn btn-outline-primary rounded-pill btn-sm px-3" data-bs-toggle="modal" data-bs-target="#budgetGoalModal">
                    <i class="fas fa-bullseye me-2"></i>Set Budget Goal
                </button>
            <?php endif; ?>
        </div>

        <!-- Journal Entries Grid -->
        <div class="row g-4">
            <?php
            if (!isset($_SESSION['id'])) {
                echo '<div class="col-12"><div class="alert alert-warning">Please login to view your journal.</div></div>';
            } else {
                $user_id = $_SESSION['id'];
                // Pagination Setup
                $limit = 9; // Cards per page
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $offset = ($page - 1) * $limit;

                // Count Total
                $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM journals WHERE user_id = ?");
                $countStmt->bind_param("i", $user_id);
                $countStmt->execute();
                $totalRows = $countStmt->get_result()->fetch_assoc()['total'];
                $totalPages = ceil($totalRows / $limit);
                $countStmt->close();

                // Fetch Entries
                $stmt = $conn->prepare("SELECT * FROM journals WHERE user_id = ? ORDER BY date DESC, created_at DESC LIMIT ? OFFSET ?");
                $stmt->bind_param("iii", $user_id, $limit, $offset);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $dateDate = date('d', strtotime($row['date']));
                        $dateMonth = date('M', strtotime($row['date']));
                        $dateYear = date('Y', strtotime($row['date']));
                        $fullDate = date('F j, Y', strtotime($row['date']));

                        if (!empty($row['end_date'])) {
                            $endDateDate = date('d', strtotime($row['end_date']));
                            $endDateMonth = date('M', strtotime($row['end_date']));

                            if ($dateMonth == $endDateMonth) {
                                $displayDate = $dateDate . '-' . $endDateDate;
                            } else {
                                $displayDate = $dateDate . ' ' . $dateMonth . ' - ' . $endDateDate . ' ' . $endDateMonth;
                            }
                            $fullDate .= ' to ' . date('F j, Y', strtotime($row['end_date']));
                        } else {
                            $displayDate = $dateDate;
                        }

                        // Status Color Logic
                        $statusColor = 'secondary';
                        $statusIcon = 'fa-circle';
                        switch (strtolower($row['financial_status'])) {
                            case 'healthy':
                            case 'good':
                            case 'positive':
                                $statusColor = 'success';
                                $statusIcon = 'fa-smile';
                                break;
                            case 'caution':
                            case 'moderate':
                                $statusColor = 'warning';
                                $statusIcon = 'fa-meh';
                                break;
                            case 'critical':
                            case 'bad':
                            case 'negative':
                                $statusColor = 'danger';
                                $statusIcon = 'fa-frown';
                                break;
                        }

                        $warningBadge = '';
                        if ($row['overspending_warning']) {
                            $warningBadge = '<span class="badge bg-danger-subtle text-danger rounded-pill ms-2"><i class="fas fa-exclamation-triangle me-1"></i>Overspending Alert</span>';
                        }

                        // Fetch Journal Lines
                        $linesStmt = $conn->prepare("SELECT * FROM journal_lines WHERE journal_id = ? ORDER BY id ASC");
                        $linesStmt->bind_param("i", $row['id']);
                        $linesStmt->execute();
                        $linesResult = $linesStmt->get_result();
                        $lines = $linesResult->fetch_all(MYSQLI_ASSOC);
                        $linesStmt->close();
            ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden hover-lift transition-all">
                                <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-start">
                                    <div class="d-flex align-items-center">
                                        <div class="text-center me-3 border-end pe-3">
                                            <h3 class="fw-bold mb-0 text-dark" style="line-height:1;"><?php echo $displayDate; ?></h3>
                                            <small class="text-uppercase text-muted fw-bold" style="font-size:0.7rem;"><?php echo $dateMonth; ?></small>
                                        </div>
                                        <div>
                                            <div class="badge bg-<?php echo $statusColor; ?>-subtle text-<?php echo $statusColor; ?> rounded-pill mb-1">
                                                <i class="fas <?php echo $statusIcon; ?> me-1"></i> <?php echo ucfirst($row['financial_status']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <?php if ($row['overspending_warning']): ?>
                                            <div class="text-danger me-2" data-bs-toggle="tooltip" title="Overspending detected on this day">
                                                <i class="fas fa-exclamation-circle fa-lg"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="dropdown">
                                            <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                                <li>
                                                    <a class="dropdown-item small" href="#" data-bs-toggle="modal" data-bs-target="#editJournalModal<?php echo $row['id']; ?>">
                                                        <i class="fas fa-edit me-2 text-primary"></i>Edit
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item small text-danger" href="#" onclick="confirmJournalDelete(<?php echo $row['id']; ?>); return false;">
                                                        <i class="fas fa-trash me-2 text-danger"></i>Delete
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body px-4 pt-3 pb-4">
                                    <h5 class="card-title fw-bold mb-2 text-primary"><?php echo htmlspecialchars($row['title']); ?></h5>
                                    <p class="card-text text-secondary small" style="display: -webkit-box; -webkit-line-clamp: 4; line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?php echo nl2br(htmlspecialchars($row['notes'])); ?>
                                    </p>
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#journalModal<?php echo $row['id']; ?>">
                                            Read More
                                        </button>
                                    </div>
                                </div>
                                <div class="card-footer bg-light border-0 py-2 px-4 text-end">
                                    <small class="text-muted" style="font-size: 0.75rem;">Created: <?php echo date('h:i A', strtotime($row['created_at'])); ?></small>
                                </div>
                            </div>
                        </div>

                        <!-- Read Modal -->
                        <div class="modal fade" id="journalModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content border-0 rounded-4 shadow">
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold text-primary"><?php echo $fullDate; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <span class="badge bg-<?php echo $statusColor; ?>-subtle text-<?php echo $statusColor; ?> rounded-pill me-2 px-3 py-2">
                                                <i class="fas <?php echo $statusIcon; ?> me-1"></i> <?php echo ucfirst($row['financial_status']); ?>
                                            </span>
                                            <?php echo $warningBadge; ?>
                                        </div>
                                        <h3 class="fw-bold mb-3"><?php echo htmlspecialchars($row['title']); ?></h3>
                                        <div class="text-secondary" style="line-height: 1.8;">
                                            <?php echo nl2br(htmlspecialchars($row['notes'])); ?>
                                        </div>

                                        <?php if (!empty($lines)): ?>
                                            <div class="mt-4">
                                                <h6 class="fw-bold text-secondary text-uppercase small mb-3">Ledger Entries</h6>
                                                <div class="table-responsive rounded-3 border">
                                                    <table class="table table-borderless table-striped mb-0 small">
                                                        <thead class="bg-light border-bottom">
                                                            <tr>
                                                                <th class="ps-3">Account Title</th>
                                                                <th class="text-end">Debit</th>
                                                                <th class="text-end pe-3">Credit</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $totalDebit = 0;
                                                            $totalCredit = 0;
                                                            foreach ($lines as $line):
                                                                $totalDebit += $line['debit'];
                                                                $totalCredit += $line['credit'];
                                                            ?>
                                                                <tr>
                                                                    <td class="ps-3 fw-medium"><?php echo htmlspecialchars($line['account_title']); ?></td>
                                                                    <td class="text-end text-dark"><?php echo $line['debit'] > 0 ? number_format($line['debit'], 2) : '-'; ?></td>
                                                                    <td class="text-end pe-3 text-dark"><?php echo $line['credit'] > 0 ? number_format($line['credit'], 2) : '-'; ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                        <tfoot class="border-top fw-bold bg-light">
                                                            <tr>
                                                                <td class="ps-3">Total</td>
                                                                <td class="text-end"><?php echo $currencySymbol . number_format($totalDebit, 2); ?></td>
                                                                <td class="text-end pe-3"><?php echo $currencySymbol . number_format($totalCredit, 2); ?></td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editJournalModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 rounded-4 shadow">
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold">Edit Entry</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <form action="journal.php" method="POST">
                                            <input type="hidden" name="action" value="edit_journal">
                                            <input type="hidden" name="journal_id" value="<?php echo $row['id']; ?>">

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary text-uppercase">Title</label>
                                                <input type="text" class="form-control rounded-3" name="title" required value="<?php echo htmlspecialchars($row['title']); ?>">
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold text-secondary text-uppercase">From Date</label>
                                                    <input type="date" class="form-control rounded-3" name="date" required value="<?php echo $row['date']; ?>">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold text-secondary text-uppercase">To Date (Optional)</label>
                                                    <input type="date" class="form-control rounded-3" name="end_date" value="<?php echo $row['end_date']; ?>">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary text-uppercase d-flex justify-content-between">
                                                    Ledger Entries
                                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" onclick="addLedgerRow('editLedgerTable<?php echo $row['id']; ?>')">+ Add Line</button>
                                                </label>
                                                <div class="p-2 bg-light rounded-3 border">
                                                    <table class="table table-borderless table-sm mb-0" id="editLedgerTable<?php echo $row['id']; ?>">
                                                        <thead>
                                                            <tr class="text-secondary" style="font-size:0.75rem;">
                                                                <th style="width:40%">Account</th>
                                                                <th style="width:25%">Debit</th>
                                                                <th style="width:25%">Credit</th>
                                                                <th style="width:10%"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            if (!empty($lines)) {
                                                                foreach ($lines as $line) {
                                                            ?>
                                                                    <tr>
                                                                        <td><input type="text" name="lines_account[]" class="form-control form-control-sm border-0 bg-white" placeholder="Account" value="<?php echo htmlspecialchars($line['account_title']); ?>" required></td>
                                                                        <td><input type="number" step="0.01" name="lines_debit[]" class="form-control form-control-sm border-0 bg-white" placeholder="0.00" value="<?php echo $line['debit']; ?>"></td>
                                                                        <td><input type="number" step="0.01" name="lines_credit[]" class="form-control form-control-sm border-0 bg-white" placeholder="0.00" value="<?php echo $line['credit']; ?>"></td>
                                                                        <td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove()"><i class="fas fa-trash-alt"></i></button></td>
                                                                    </tr>
                                                                <?php
                                                                }
                                                            } else {
                                                                ?>
                                                                <tr>
                                                                    <td><input type="text" name="lines_account[]" class="form-control form-control-sm border-0 bg-white" placeholder="Account" required></td>
                                                                    <td><input type="number" step="0.01" name="lines_debit[]" class="form-control form-control-sm border-0 bg-white" placeholder="0.00"></td>
                                                                    <td><input type="number" step="0.01" name="lines_credit[]" class="form-control form-control-sm border-0 bg-white" placeholder="0.00"></td>
                                                                    <td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove()"><i class="fas fa-trash-alt"></i></button></td>
                                                                </tr>
                                                            <?php
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary text-uppercase">Financial Status</label>
                                                <select class="form-select rounded-3" name="financial_status">
                                                    <option value="Healthy" <?php echo ($row['financial_status'] == 'Healthy') ? 'selected' : ''; ?>>Healthy (Green)</option>
                                                    <option value="Neutral" <?php echo ($row['financial_status'] == 'Neutral') ? 'selected' : ''; ?>>Neutral (Grey)</option>
                                                    <option value="Caution" <?php echo ($row['financial_status'] == 'Caution') ? 'selected' : ''; ?>>Caution (Orange)</option>
                                                    <option value="Critical" <?php echo ($row['financial_status'] == 'Critical') ? 'selected' : ''; ?>>Critical (Red)</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary text-uppercase">Reflections / Notes</label>
                                                <textarea class="form-control rounded-3" name="notes" rows="4"><?php echo htmlspecialchars($row['notes']); ?></textarea>
                                            </div>

                                            <div class="form-check mb-4">
                                                <input class="form-check-input" type="checkbox" name="overspending_warning" id="overspendingCheck<?php echo $row['id']; ?>" <?php echo $row['overspending_warning'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label text-danger fw-bold" for="overspendingCheck<?php echo $row['id']; ?>">
                                                    <i class="fas fa-exclamation-triangle me-1"></i> Flag as Overspending
                                                </label>
                                            </div>

                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary rounded-pill py-2">Update Entry</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                } else {
                    ?>
                    <div class="col-12 text-center py-5">
                        <div class="mb-3 text-secondary opacity-50">
                            <i class="fas fa-book-open fa-4x"></i>
                        </div>
                        <h4 class="fw-bold text-secondary">No Journal Entries Yet</h4>
                        <p class="text-muted">Chat with the AI Assistant or click "New Entry" to create your first reflection!</p>
                    </div>
            <?php
                }
                $stmt->close();

                // Simple Pagination
                if ($totalPages > 1) {
                    echo '<div class="col-12 mt-4"><nav><ul class="pagination justify-content-center">';
                    for ($i = 1; $i <= $totalPages; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '"><a class="page-link rounded-circle mx-1" href="?page=' . $i . '">' . $i . '</a></li>';
                    }
                    echo '</ul></nav></div>';
                }
            }
            ?>
        </div>

    </div>
</div>

<!-- Add Journal Modal -->
<div class="modal fade" id="addJournalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">New Journal Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form action="journal.php" method="POST">
                    <input type="hidden" name="action" value="add_journal">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Title</label>
                        <input type="text" class="form-control rounded-3" name="title" required placeholder="e.g., Weekly Summary">
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">From Date</label>
                            <input type="date" class="form-control rounded-3" name="date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">To Date (Optional)</label>
                            <input type="date" class="form-control rounded-3" name="end_date">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase d-flex justify-content-between">
                            Ledger Entries
                            <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" onclick="addLedgerRow('addLedgerTable')">+ Add Line</button>
                        </label>
                        <div class="p-2 bg-light rounded-3 border">
                            <table class="table table-borderless table-sm mb-0" id="addLedgerTable">
                                <thead>
                                    <tr class="text-secondary" style="font-size:0.75rem;">
                                        <th style="width:40%">Account</th>
                                        <th style="width:25%">Debit</th>
                                        <th style="width:25%">Credit</th>
                                        <th style="width:10%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input type="text" name="lines_account[]" class="form-control form-control-sm border-0 bg-white" placeholder="Account" required></td>
                                        <td><input type="number" step="0.01" name="lines_debit[]" class="form-control form-control-sm border-0 bg-white" placeholder="0.00"></td>
                                        <td><input type="number" step="0.01" name="lines_credit[]" class="form-control form-control-sm border-0 bg-white" placeholder="0.00"></td>
                                        <td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove()"><i class="fas fa-trash-alt"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Financial Status</label>
                        <select class="form-select rounded-3" name="financial_status">
                            <option value="Healthy">Healthy (Green)</option>
                            <option value="Neutral" selected>Neutral (Grey)</option>
                            <option value="Caution">Caution (Orange)</option>
                            <option value="Critical">Critical (Red)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Reflections / Notes</label>
                        <textarea class="form-control rounded-3" name="notes" rows="4" placeholder="How did you feel about your spending today?"></textarea>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="overspending_warning" id="overspendingCheck">
                        <label class="form-check-label text-danger fw-bold" for="overspendingCheck">
                            <i class="fas fa-exclamation-triangle me-1"></i> Flag as Overspending
                        </label>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary rounded-pill py-2">Save Entry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Budget Goal Modal -->
<div class="modal fade" id="budgetGoalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Budget Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form action="journal.php" method="POST">
                    <input type="hidden" name="action" value="update_goal">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Monthly Limit</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><?php echo CurrencyHelper::getSymbol($_SESSION['user_currency'] ?? 'PHP'); ?></span>
                            <input type="number" step="0.01" class="form-control border-start-0 ps-0" name="monthly_budget_goal" required placeholder="0.00" value="<?php echo ($budgetGoal > 0) ? $budgetGoal : ''; ?>">
                        </div>
                        <div class="form-text small">This goal helps the AI track your progress.</div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary rounded-pill py-2">Update Goal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



<!-- Hidden Form for Deletion -->
<form id="deleteJournalForm" action="journal.php" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete_journal">
    <input type="hidden" name="journal_id" id="deleteJournalId">
</form>

<script>
    function addLedgerRow(tableId) {
        const table = document.getElementById(tableId).getElementsByTagName('tbody')[0];
        const newRow = table.insertRow();
        newRow.innerHTML = `
            <td><input type="text" name="lines_account[]" class="form-control form-control-sm border-0 bg-white" placeholder="Account" required></td>
            <td><input type="number" step="0.01" name="lines_debit[]" class="form-control form-control-sm border-0 bg-white" placeholder="0.00"></td>
            <td><input type="number" step="0.01" name="lines_credit[]" class="form-control form-control-sm border-0 bg-white" placeholder="0.00"></td>
            <td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove()"><i class="fas fa-trash-alt"></i></button></td>
        `;
    }

    function confirmJournalDelete(id) {
        Swal.fire({
            title: 'Delete Entry?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6366f1',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteJournalId').value = id;
                document.getElementById('deleteJournalForm').submit();
            }
        });
        return false;
    }

    // --- Page Tutorial ---
    <?php if (!isset($seen_tutorials['journal.php'])): ?>

        function startTutorial() {
            if (window.seenTutorials['journal.php']) return;

            const steps = [{
                    title: 'Financial Journal',
                    text: 'Deeply track your financial health with the Double-Entry Journal System.',
                    icon: 'info',
                    confirmButtonText: 'Let\'s Go'
                },
                {
                    title: 'Journal Entries',
                    text: 'View your history with detailed notes, status, and ledger lines.',
                    icon: 'book',
                    confirmButtonText: 'Next',
                    target: '.row.g-4'
                },
                {
                    title: 'Monthly Budget Goal',
                    text: 'Set targets for your monthly spending to keep your finances on track.',
                    icon: 'bullseye',
                    confirmButtonText: 'Next',
                    target: '[data-bs-target="#budgetGoalModal"]'
                },
                {
                    title: 'Create Manual Entry',
                    text: 'Found a transaction that isn\'t automated? Record it manually here with full ledger control.',
                    icon: 'edit',
                    confirmButtonText: 'Finish',
                    target: '[data-bs-target="#addJournalModal"]'
                }
            ];

            function showStep(index) {
                if (index >= steps.length) {
                    markPageTutorialSeen('journal.php');
                    return;
                }
                const step = steps[index];
                Swal.fire({
                    title: step.title,
                    text: step.text,
                    icon: 'info',
                    confirmButtonText: step.confirmButtonText,
                    showCancelButton: true,
                    cancelButtonText: 'Skip',
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
                                setTimeout(() => el.classList.remove('tutorial-highlight'), 3000);
                            }
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) showStep(index + 1);
                    else if (result.dismiss === Swal.DismissReason.cancel) markPageTutorialSeen('journal.php');
                });
            }
            if (!document.getElementById('tutorial-styles')) {
                const style = document.createElement('style');
                style.id = 'tutorial-styles';
                style.textContent = `.tutorial-highlight { outline: 4px solid var(--primary); outline-offset: 4px; border-radius: 12px; transition: outline 0.3s ease; z-index: 9999; position: relative; }`;
                document.head.appendChild(style);
            }
            showStep(0);
        }
        setTimeout(startTutorial, 1000);
    <?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>