<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$sql = "SELECT b.boarder_id, b.first_name, b.last_name, b.gender, b.birthdate, b.contact_number, b.email, b.address,
               ra.agreement_id, ra.start_date, ra.end_date, ra.monthly_rate, ra.status AS agreement_status,
               bed.bed_number, room.room_number
        FROM boarder b
        LEFT JOIN rental_agreement ra ON b.boarder_id = ra.boarder_id
        LEFT JOIN bed ON ra.bed_id = bed.bed_id
        LEFT JOIN room ON bed.room_id = room.room_id
        ORDER BY b.boarder_id DESC";

$result = $con->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boarder List | StayEase</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="app-header">
        <div class="header-brand">STAYEASE</div>
        <nav class="app-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="boarders.php" class="active">Boarders</a>
            <a href="boarder_register.php">Add Boarder</a>
            <a href="beds.php">Beds</a>
            <a href="rooms.php">Rooms</a>
            <a href="billing.php">Billing</a>
            <a href="maintenance.php">Maintenance</a>
            <a href="violations.php">Violations</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="page-content">
        <section class="page-hero">
            <div>
                <p class="section-label">Tenant Management</p>
                <h1>Boarder Registry</h1>
                <p>Review registered boarders, assigned beds, and rental agreements in one place.</p>
            </div>
            <a href="boarder_register.php" class="btn-primary">Register New Boarder</a>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Current Boarders</h2>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Boarder</th>
                            <th>Gender</th>
                            <th>Contact</th>
                            <th>Bed / Room</th>
                            <th>Agreement</th>
                            <th>Rate</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong><br>
                                        <span class="small-text"><?php echo htmlspecialchars($row['email']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($row['contact_number']); ?><br>
                                        <span class="small-text"><?php echo htmlspecialchars($row['birthdate']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo $row['bed_number'] ? 'Bed ' . htmlspecialchars($row['bed_number']) : 'Unassigned'; ?><br>
                                        <span class="small-text"><?php echo $row['room_number'] ? 'Room ' . htmlspecialchars($row['room_number']) : ''; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($row['agreement_id']): ?>
                                            <?php echo 'ID #' . htmlspecialchars($row['agreement_id']); ?><br>
                                            <span class="small-text"><?php echo htmlspecialchars($row['start_date']); ?> &rarr; <?php echo htmlspecialchars($row['end_date']); ?></span>
                                        <?php else: ?>
                                            <span class="tag tag-warning">No agreement</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['monthly_rate'] ? '₱' . number_format($row['monthly_rate'], 2) : '-'; ?></td>
                                    <td>
                                        <?php if ($row['agreement_status']): ?>
                                            <span class="tag"><?php echo htmlspecialchars($row['agreement_status']); ?></span>
                                        <?php else: ?>
                                            <span class="tag tag-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-state">No boarders found. Add a new boarder to begin assigning beds.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
