<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/BalanceHelper.php';
require_once '../includes/CurrencyHelper.php';

if (!isset($_SESSION['id'])) {
    die("Unauthorized");
}

$user_id = $_SESSION['id'];
$month = $_GET['month'] ?? date('Y-m');
$balanceHelper = new BalanceHelper($conn);

// Fetch User Info
$stmt = $conn->prepare("SELECT first_name, last_name, email, user_currency FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$currencySymbol = CurrencyHelper::getSymbol($user['user_currency'] ?? 'PHP');

// Fetch Monthly Data
$start_date = $month . "-01";
$end_date = date("Y-m-t", strtotime($start_date));

// Allowance
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM allowances WHERE user_id = ? AND deleted_at IS NULL AND date BETWEEN ? AND ?");
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$total_allowance = (float)$stmt->get_result()->fetch_row()[0];
$stmt->close();

// Expenses
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ? AND deleted_at IS NULL AND date BETWEEN ? AND ?");
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$total_expenses = (float)$stmt->get_result()->fetch_row()[0];
$stmt->close();

// Savings
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM savings WHERE user_id = ? AND deleted_at IS NULL AND date BETWEEN ? AND ?");
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$total_savings = (float)$stmt->get_result()->fetch_row()[0];
$stmt->close();

// Top Categories
$categories = [];
$stmt = $conn->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE user_id = ? AND deleted_at IS NULL AND date BETWEEN ? AND ? GROUP BY category ORDER BY total DESC LIMIT 5");
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $categories[] = $row;
$stmt->close();

// Transactions
$transactions = [];
$sql = "(SELECT 'Allowance' as type, description, amount, date FROM allowances WHERE user_id = ? AND deleted_at IS NULL AND date BETWEEN ? AND ?)
        UNION ALL
        (SELECT 'Expense' as type, description, amount, date FROM expenses WHERE user_id = ? AND deleted_at IS NULL AND date BETWEEN ? AND ?)
        UNION ALL
        (SELECT 'Savings' as type, description, amount, date FROM savings WHERE user_id = ? AND deleted_at IS NULL AND date BETWEEN ? AND ?)
        ORDER BY date DESC LIMIT 20";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isssssiss", $user_id, $start_date, $end_date, $user_id, $start_date, $end_date, $user_id, $start_date, $end_date);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $transactions[] = $row;
$stmt->close();

// HTML Template for PDF
ob_start();
?>
<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            color: #333;
            line-height: 1.6;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #6366f1;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #6366f1;
            margin: 0;
        }

        .summary-box {
            background: #f8fafc;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .grid {
            display: block;
            clear: both;
        }

        .col {
            float: left;
            width: 30%;
            text-align: center;
        }

        .label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
        }

        .value {
            font-size: 20px;
            font-weight: bold;
            color: #1e293b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: #6366f1;
            color: white;
            text-align: left;
            padding: 10px;
            font-size: 14px;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            margin-top: 50px;
        }

        .type-Allowance {
            color: #10b981;
        }

        .type-Expense {
            color: #ef4444;
        }

        .type-Savings {
            color: #6366f1;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Monthly Financial Statement</h1>
        <p><?php echo date("F Y", strtotime($start_date)); ?> | <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
    </div>

    <div class="summary-box">
        <div class="grid">
            <div class="col">
                <div class="label">Total Income</div>
                <div class="value"><?php echo $currencySymbol . number_format($total_allowance, 2); ?></div>
            </div>
            <div class="col">
                <div class="label">Total Expenses</div>
                <div class="value"><?php echo $currencySymbol . number_format($total_expenses, 2); ?></div>
            </div>
            <div class="col">
                <div class="label">Net Savings</div>
                <div class="value"><?php echo $currencySymbol . number_format($total_allowance - $total_expenses, 2); ?></div>
            </div>
        </div>
        <div style="clear: both;"></div>
    </div>

    <h3>Spending by Category</h3>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Amount Spent</th>
                <th>% of Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cat['category']); ?></td>
                    <td><?php echo $currencySymbol . number_format($cat['total'], 2); ?></td>
                    <td><?php echo number_format(($cat['total'] / ($total_expenses ?: 1)) * 100, 1); ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Recent Activity</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $tx): ?>
                <tr>
                    <td><?php echo date("M d", strtotime($tx['date'])); ?></td>
                    <td class="type-<?php echo $tx['type']; ?>"><?php echo $tx['type']; ?></td>
                    <td><?php echo htmlspecialchars($tx['description']); ?></td>
                    <td style="text-align: right;"><?php echo ($tx['type'] == 'Expense' ? '-' : '+') . $currencySymbol . number_format($tx['amount'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        Generated by <?php echo APP_NAME; ?> on <?php echo date("Y-m-d H:i:s"); ?><br>
        Your personal financial assistant.
    </div>
</body>

</html>
<?php
$html = ob_get_clean();

// For now, since installing dompdf is tricky without composer, 
// we'll provide a high-fidelity HTML version that prints perfectly to PDF via browser.
// This is the most reliable way for the user to get a PDF right now.

if (isset($_GET['print'])) {
    echo $html;
    echo "<script>window.onload = function() { window.print(); }</script>";
} else {
    echo $html;
}
?>