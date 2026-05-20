<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';
$user_id = 1;

$stmtUser = $con->prepare("SELECT user_id FROM user WHERE username = ? LIMIT 1");
$stmtUser->bind_param('s', $_SESSION['username']);
$stmtUser->execute();
$userResult = $stmtUser->get_result();
if ($userRow = $userResult->fetch_assoc()) {
    $user_id = intval($userRow['user_id']);
}
$stmtUser->close();

$boarderList = $con->query("SELECT boarder_id, first_name, last_name FROM boarder ORDER BY last_name, first_name");

if (isset($_POST['btnRecordPayment'])) {
    $boarder_id = intval($_POST['boarder_id']);
    $transaction_date = mysqli_real_escape_string($con, $_POST['transaction_date']);
    $amount = mysqli_real_escape_string($con, $_POST['amount']);
    $transaction_type = mysqli_real_escape_string($con, $_POST['transaction_type']);
    $utility_id = intval($_POST['utility_id'] ?? 0);
    $description = mysqli_real_escape_string($con, $_POST['description']);

    if (!$boarder_id || !$transaction_date || !$amount) {
        $error = 'Boarder, payment date, and amount are required.';
    } else {
        $stmt = $con->prepare("INSERT INTO `transaction` (boarder_id, user_id, transaction_date, amount, transaction_type, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iisdss', $boarder_id, $user_id, $transaction_date, $amount, $transaction_type, $description);
        if ($stmt->execute()) {
            $transaction_id = $con->insert_id;
            if ($transaction_type === 'Utility') {
                if ($utility_id) {
                    $stmtUpdate = $con->prepare("UPDATE utility_expense SET status = 'Paid', transaction_id = ? WHERE utility_id = ?");
                    $stmtUpdate->bind_param('ii', $transaction_id, $utility_id);
                    $stmtUpdate->execute();
                    $stmtUpdate->close();
                } else {
                    $stmtMatch = $con->prepare("SELECT utility_id FROM utility_expense WHERE status IN ('Pending','Overdue') AND total_amount = ? AND due_date <= ? AND transaction_id IS NULL ORDER BY due_date ASC LIMIT 1");
                    $stmtMatch->bind_param('ds', $amount, $transaction_date);
                    $stmtMatch->execute();
                    $matchResult = $stmtMatch->get_result();
                    if ($matchExpense = $matchResult->fetch_assoc()) {
                        $stmtUpdate = $con->prepare("UPDATE utility_expense SET status = 'Paid', transaction_id = ? WHERE utility_id = ?");
                        $stmtUpdate->bind_param('ii', $transaction_id, $matchExpense['utility_id']);
                        $stmtUpdate->execute();
                        $stmtUpdate->close();
                    }
                    $stmtMatch->close();
                }
            }
            $message = 'Payment recorded successfully.';
        } else {
            $error = 'Unable to record payment. Please try again.';
        }
        $stmt->close();
    }
}

if (isset($_POST['btnCreateUtility'])) {
    $utility_type = mysqli_real_escape_string($con, $_POST['utility_type']);
    $billing_period = mysqli_real_escape_string($con, $_POST['billing_period']);
    $total_amount = mysqli_real_escape_string($con, $_POST['total_amount']);
    $due_date = mysqli_real_escape_string($con, $_POST['due_date']);
    $status = mysqli_real_escape_string($con, $_POST['status']);

    if (!$utility_type || !$billing_period || !$total_amount || !$due_date) {
        $error = 'Please complete all utility expense fields.';
    } else {
        $stmt = $con->prepare("INSERT INTO utility_expense (transaction_id, utility_type, billing_period, total_amount, due_date, status) VALUES (NULL, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssdss', $utility_type, $billing_period, $total_amount, $due_date, $status);
        if ($stmt->execute()) {
            $message = 'Utility expense logged successfully.';
        } else {
            $error = 'Unable to add utility expense. Please try again.';
        }
        $stmt->close();
    }
}

if (isset($_POST['btnMarkUtilityPaid'])) {
    $utility_id = intval($_POST['utility_id']);

    if (!$utility_id) {
        $error = 'Invalid utility expense selection.';
    } else {
        $stmt = $con->prepare("UPDATE utility_expense SET status = 'Paid' WHERE utility_id = ?");
        $stmt->bind_param('i', $utility_id);
        if ($stmt->execute()) {
            $message = 'Utility expense marked as paid.';
        } else {
            $error = 'Unable to update utility status. Please try again.';
        }
        $stmt->close();
    }
}

$summarySql = "SELECT
                    IFNULL((SELECT SUM(amount) FROM `transaction`), 0) AS total_payments,
                    IFNULL((SELECT SUM(total_amount) FROM utility_expense WHERE status <> 'Paid'), 0) AS total_utilities_due,
                    IFNULL((SELECT COUNT(*) FROM utility_expense WHERE status = 'Overdue'), 0) AS overdue_utilities
                ";
$summary = $con->query($summarySql)->fetch_assoc();

$pendingUtilities = $con->query("SELECT utility_id, utility_type, billing_period, total_amount, due_date FROM utility_expense WHERE status IN ('Pending','Overdue') AND transaction_id IS NULL ORDER BY due_date ASC");

$transactionHistory = $con->query("SELECT t.transaction_id, t.transaction_date, t.amount, t.transaction_type, t.description, b.first_name, b.last_name
                                   FROM `transaction` t
                                   LEFT JOIN boarder b ON t.boarder_id = b.boarder_id
                                   ORDER BY t.transaction_date DESC, t.transaction_id DESC");

$utilityHistory = $con->query("SELECT u.utility_id, u.utility_type, u.billing_period, u.total_amount, u.due_date, u.status FROM utility_expense u ORDER BY u.due_date DESC, u.utility_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing | StayEase</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="app-header">
        <div class="header-brand">STAYEASE</div>
        <nav class="app-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="boarders.php">Boarders</a>
            <a href="boarder_register.php">Add Boarder</a>
            <a href="beds.php">Beds</a>
            <a href="rooms.php">Rooms</a>
            <a href="billing.php" class="active">Billing</a>
            <a href="maintenance.php">Maintenance</a>
            <a href="violations.php">Violations</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="page-content">
        <section class="page-hero">
            <div>
                <p class="section-label">Payments & Billing</p>
                <h1>Billing Dashboard</h1>
                <p>Record tenant payments, log utility expenses, and view a complete billing summary.</p>
            </div>
            <a href="maintenance.php" class="btn-secondary">Maintenance</a>
        </section>

        <section class="summary-grid">
            <div class="summary-card">
                <p class="summary-title">Total payments</p>
                <p class="summary-value">₱<?php echo number_format($summary['total_payments'], 2); ?></p>
            </div>
            <div class="summary-card">
                <p class="summary-title">Utilities due</p>
                <p class="summary-value">₱<?php echo number_format($summary['total_utilities_due'], 2); ?></p>
            </div>
            <div class="summary-card">
                <p class="summary-title">Overdue utilities</p>
                <p class="summary-value"><?php echo intval($summary['overdue_utilities']); ?></p>
            </div>
        </section>

        <section class="panel form-panel">
            <div class="panel-heading">
                <h2>Record Payment</h2>
            </div>
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" action="billing.php" class="form-grid">
                <div class="input-group">
                    <label for="boarder_id">Boarder</label>
                    <select id="boarder_id" name="boarder_id" required>
                        <option value="">Select boarder</option>
                        <?php while ($boarder = $boarderList->fetch_assoc()): ?>
                            <option value="<?php echo intval($boarder['boarder_id']); ?>"><?php echo htmlspecialchars($boarder['first_name'] . ' ' . $boarder['last_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="transaction_date">Payment Date</label>
                    <input type="date" id="transaction_date" name="transaction_date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="input-group">
                    <label for="amount">Amount</label>
                    <input type="number" step="0.01" id="amount" name="amount" required>
                </div>
                <div class="input-group">
                    <label for="transaction_type">Type</label>
                    <select id="transaction_type" name="transaction_type" required>
                        <option value="Rent">Rent</option>
                        <option value="Utility">Utility</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="input-group input-full">
                    <label for="utility_id">Match Utility Expense (optional)</label>
                    <select id="utility_id" name="utility_id">
                        <option value="">Auto-match or no utility selected</option>
                        <?php if ($pendingUtilities && $pendingUtilities->num_rows > 0): ?>
                            <?php while ($pendingUtility = $pendingUtilities->fetch_assoc()): ?>
                                <option value="<?php echo intval($pendingUtility['utility_id']); ?>">
                                    <?php echo htmlspecialchars($pendingUtility['utility_type'] . ' - ' . $pendingUtility['billing_period'] . ' / ₱' . number_format($pendingUtility['total_amount'], 2) . ' due ' . $pendingUtility['due_date']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                    <span class="form-note">Choose a pending utility to mark the exact expense paid automatically.</span>
                </div>
                <div class="input-group input-full">
                    <label for="description">Notes</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                <div class="input-group input-full actions-row">
                    <button type="submit" class="btn-primary" name="btnRecordPayment">Record Payment</button>
                </div>
            </form>
        </section>

        <section class="panel form-panel">
            <div class="panel-heading">
                <h2>Log Utility Expense</h2>
            </div>
            <form method="post" action="billing.php" class="form-grid">
                <div class="input-group">
                    <label for="utility_type">Utility Type</label>
                    <input type="text" id="utility_type" name="utility_type" required>
                </div>
                <div class="input-group">
                    <label for="billing_period">Billing Period</label>
                    <input type="date" id="billing_period" name="billing_period" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="input-group">
                    <label for="total_amount">Total Amount</label>
                    <input type="number" step="0.01" id="total_amount" name="total_amount" required>
                </div>
                <div class="input-group">
                    <label for="due_date">Due Date</label>
                    <input type="date" id="due_date" name="due_date" required>
                </div>
                <div class="input-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="Pending">Pending</option>
                        <option value="Paid">Paid</option>
                        <option value="Overdue">Overdue</option>
                    </select>
                </div>
                <div class="input-group input-full actions-row">
                    <button type="submit" class="btn-primary" name="btnCreateUtility">Log Utility Expense</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Transaction History</h2>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Boarder</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($transactionHistory && $transactionHistory->num_rows > 0): ?>
                            <?php while ($row = $transactionHistory->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['transaction_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['transaction_type']); ?></td>
                                    <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">No transactions recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Utility Expense History</h2>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Period</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($utilityHistory && $utilityHistory->num_rows > 0): ?>
                            <?php while ($row = $utilityHistory->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['utility_type']); ?></td>
                                    <td><?php echo htmlspecialchars($row['billing_period']); ?></td>
                                    <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                                    <td>
                                        <?php if ($row['status'] !== 'Paid'): ?>
                                            <form method="post" action="billing.php" class="inline-form">
                                                <input type="hidden" name="utility_id" value="<?php echo intval($row['utility_id']); ?>">
                                                <button type="submit" class="btn-secondary" name="btnMarkUtilityPaid">Mark Paid</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="tag">Paid</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">No utility expenses logged yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
