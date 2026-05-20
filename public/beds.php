<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

if (isset($_POST['btnAddBed'])) {
    $bed_number = mysqli_real_escape_string($con, trim($_POST['bed_number']));
    $room_number = mysqli_real_escape_string($con, trim($_POST['room_number']));
    $occupancy_status = mysqli_real_escape_string($con, $_POST['occupancy_status']);

    if (!$bed_number) {
        $error = 'Bed number is required.';
    } else {
        $room_id = null;

        if ($room_number !== '') {
            $stmtRoom = $con->prepare("SELECT room_id FROM room WHERE room_number = ? LIMIT 1");
            $stmtRoom->bind_param('s', $room_number);
            $stmtRoom->execute();
            $roomResult = $stmtRoom->get_result();

            if ($roomRow = $roomResult->fetch_assoc()) {
                $room_id = $roomRow['room_id'];
            } else {
                $stmtCreateRoom = $con->prepare("INSERT INTO room (room_number, capacity, status) VALUES (?, 0, 'available')");
                $stmtCreateRoom->bind_param('s', $room_number);
                if ($stmtCreateRoom->execute()) {
                    $room_id = $con->insert_id;
                }
                $stmtCreateRoom->close();
            }
            $stmtRoom->close();
        }

        if ($room_id !== null) {
            $stmtBed = $con->prepare("INSERT INTO bed (room_id, bed_number, occupancy_status) VALUES (?, ?, ?)");
            $stmtBed->bind_param('iss', $room_id, $bed_number, $occupancy_status);
        } else {
            $stmtBed = $con->prepare("INSERT INTO bed (room_id, bed_number, occupancy_status) VALUES (NULL, ?, ?)");
            $stmtBed->bind_param('ss', $bed_number, $occupancy_status);
        }

        if ($stmtBed->execute()) {
            $message = 'Bed added successfully.';
        } else {
            $error = 'Unable to add the bed. Please try again.';
        }
        $stmtBed->close();
    }
}

$sqlBeds = "SELECT bed.bed_id, bed.bed_number, bed.occupancy_status, room.room_number
            FROM bed
            LEFT JOIN room ON bed.room_id = room.room_id
            ORDER BY room.room_number, bed.bed_number";

$bedList = $con->query($sqlBeds);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Beds | StayEase</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="app-header">
        <div class="header-brand">STAYEASE</div>
        <nav class="app-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="boarders.php">Boarders</a>
            <a href="boarder_register.php">Add Boarder</a>
            <a href="beds.php" class="active">Beds</a>
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
                <p class="section-label">Bed Management</p>
                <h1>Beds &amp; Availability</h1>
                <p>Add beds to your property and track which ones are available for assignment.</p>
            </div>
            <a href="boarder_register.php" class="btn-secondary">Register Boarder</a>
        </section>

        <section class="panel form-panel">
            <div class="panel-heading">
                <h2>Add New Bed</h2>
            </div>
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" action="beds.php" class="form-grid">
                <div class="input-group">
                    <label for="bed_number">Bed Number</label>
                    <input type="text" id="bed_number" name="bed_number" required>
                </div>
                <div class="input-group">
                    <label for="room_number">Room Number (optional)</label>
                    <input type="text" id="room_number" name="room_number" placeholder="Example: 101">
                </div>
                <div class="input-group">
                    <label for="occupancy_status">Occupancy Status</label>
                    <select id="occupancy_status" name="occupancy_status" required>
                        <option value="vacant">Vacant</option>
                        <option value="occupied">Occupied</option>
                    </select>
                </div>
                <div class="input-group input-full actions-row">
                    <button type="submit" class="btn-primary" name="btnAddBed">Create Bed</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Existing Beds</h2>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Bed ID</th>
                            <th>Bed Number</th>
                            <th>Room</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bedList && $bedList->num_rows > 0): ?>
                            <?php while ($row = $bedList->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['bed_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['bed_number']); ?></td>
                                    <td><?php echo $row['room_number'] ? 'Room ' . htmlspecialchars($row['room_number']) : 'Unassigned'; ?></td>
                                    <td>
                                        <?php if ($row['occupancy_status'] === 'occupied'): ?>
                                            <span class="tag tag-warning">Occupied</span>
                                        <?php else: ?>
                                            <span class="tag">Vacant</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-state">No beds have been added yet. Create a bed to make it available for boarders.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
