<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

if (isset($_POST['btnLogViolation'])) {
    $boarder_id = intval($_POST['boarder_id']);
    $severity = mysqli_real_escape_string($con, $_POST['severity']);
    $description = mysqli_real_escape_string($con, trim($_POST['description']));

    if (!$boarder_id || !$severity || !$description) {
        $error = 'Boarder, severity, and description are required.';
    } else {
        $stmt = $con->prepare("INSERT INTO violation_log (boarder_id, severity, description) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $boarder_id, $severity, $description);
        if ($stmt->execute()) {
            $message = 'Violation logged successfully.';
        } else {
            $error = 'Unable to log violation. Please try again.';
        }
        $stmt->close();
    }
}

$boarderList = $con->query("SELECT boarder_id, first_name, last_name FROM boarder ORDER BY last_name, first_name");
$violationList = $con->query("SELECT v.violation_id, v.severity, v.description, b.first_name, b.last_name FROM violation_log v LEFT JOIN boarder b ON v.boarder_id = b.boarder_id ORDER BY v.violation_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Violations | StayEase</title>
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
            <a href="billing.php">Billing</a>
            <a href="maintenance.php">Maintenance</a>
            <a href="violations.php" class="active">Violations</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="page-content">
        <section class="page-hero">
            <div>
                <p class="section-label">Violation Tracking</p>
                <h1>House Rule Violations</h1>
                <p>Log incidents and keep a searchable record of boarder violations.</p>
            </div>
            <a href="maintenance.php" class="btn-secondary">Maintenance</a>
        </section>

        <section class="panel form-panel">
            <div class="panel-heading">
                <h2>Log a Violation</h2>
            </div>
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" action="violations.php" class="form-grid">
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
                    <label for="severity">Severity</label>
                    <select id="severity" name="severity" required>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
                <div class="input-group input-full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>
                <div class="input-group input-full actions-row">
                    <button type="submit" class="btn-primary" name="btnLogViolation">Log Violation</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Violation History</h2>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Boarder</th>
                            <th>Severity</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($violationList && $violationList->num_rows > 0): ?>
                            <?php while ($row = $violationList->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['severity']); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="empty-state">No violations logged yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
