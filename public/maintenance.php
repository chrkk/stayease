<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

if (isset($_POST['btnLogMaintenance'])) {
    $room_id = intval($_POST['room_id']);
    $maintenance_date = mysqli_real_escape_string($con, $_POST['maintenance_date']);
    $description = mysqli_real_escape_string($con, trim($_POST['description']));
    $status = mysqli_real_escape_string($con, $_POST['status']);

    if (!$room_id || !$maintenance_date || !$description) {
        $error = 'Room, date, and description are required.';
    } else {
        $stmt = $con->prepare("INSERT INTO maintenance_log (room_id, maintenance_date, description, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('isss', $room_id, $maintenance_date, $description, $status);
        if ($stmt->execute()) {
            $message = 'Maintenance request logged successfully.';
        } else {
            $error = 'Unable to log maintenance request. Please try again.';
        }
        $stmt->close();
    }
}

if (isset($_POST['btnUpdateStatus'])) {
    $maintenance_id = intval($_POST['maintenance_id']);
    $status = mysqli_real_escape_string($con, $_POST['status']);

    if (!$maintenance_id) {
        $error = 'Invalid request.';
    } else {
        $stmt = $con->prepare("UPDATE maintenance_log SET status = ? WHERE maintenance_id = ?");
        $stmt->bind_param('si', $status, $maintenance_id);
        if ($stmt->execute()) {
            $message = 'Maintenance status updated.';
        } else {
            $error = 'Unable to update status. Please try again.';
        }
        $stmt->close();
    }
}

$roomList = $con->query("SELECT room_id, room_number FROM room ORDER BY room_number");
$maintenanceList = $con->query("SELECT m.maintenance_id, m.maintenance_date, m.description, m.status, r.room_number FROM maintenance_log m LEFT JOIN room r ON m.room_id = r.room_id ORDER BY m.maintenance_date DESC, m.maintenance_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance | StayEase</title>
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
            <a href="maintenance.php" class="active">Maintenance</a>
            <a href="violations.php">Violations</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="page-content">
        <section class="page-hero">
            <div>
                <p class="section-label">Maintenance Requests</p>
                <h1>Repair Tracking</h1>
                <p>Log new requests, update repair status, and keep a full maintenance history.</p>
            </div>
            <a href="violations.php" class="btn-secondary">Violations</a>
        </section>

        <section class="panel form-panel">
            <div class="panel-heading">
                <h2>Log Maintenance Request</h2>
            </div>
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" action="maintenance.php" class="form-grid">
                <div class="input-group">
                    <label for="room_id">Room</label>
                    <select id="room_id" name="room_id" required>
                        <option value="">Select room</option>
                        <?php while ($room = $roomList->fetch_assoc()): ?>
                            <option value="<?php echo intval($room['room_id']); ?>"><?php echo htmlspecialchars($room['room_number']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="maintenance_date">Date</label>
                    <input type="date" id="maintenance_date" name="maintenance_date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="input-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <div class="input-group input-full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>
                <div class="input-group input-full actions-row">
                    <button type="submit" class="btn-primary" name="btnLogMaintenance">Log Request</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Maintenance History</h2>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Room</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($maintenanceList && $maintenanceList->num_rows > 0): ?>
                            <?php while ($row = $maintenanceList->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['maintenance_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['room_number'] ?: 'Unassigned'); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                                    <td>
                                        <form method="post" action="maintenance.php" class="inline-form">
                                            <input type="hidden" name="maintenance_id" value="<?php echo intval($row['maintenance_id']); ?>">
                                            <select name="status" class="status-select">
                                                <option value="Pending" <?php echo $row['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="In Progress" <?php echo $row['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                <option value="Completed" <?php echo $row['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                            <button type="submit" class="btn-secondary" name="btnUpdateStatus">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">No maintenance requests logged yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
