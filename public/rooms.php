<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';
$edit_room = null;

if (isset($_POST['btnAddRoom'])) {
    $room_number = mysqli_real_escape_string($con, trim($_POST['room_number']));
    $capacity = intval($_POST['capacity']);

    if (!$room_number) {
        $error = 'Room number is required.';
    } else {
        // New rooms start as 'available' by default; occupancy refresher will adjust.
        $stmt = $con->prepare("INSERT INTO room (room_number, capacity, status) VALUES (?, ?, 'available')");
        $stmt->bind_param('si', $room_number, $capacity);
            if ($stmt->execute()) {
                $message = 'Room added successfully.';
                refresh_room_occupancy_statuses($con);
            } else {
                $error = 'Unable to add room. Please try again.';
            }
        $stmt->close();
    }
}

if (isset($_POST['btnUpdateRoom'])) {
    $room_id = intval($_POST['room_id']);
    $room_number = mysqli_real_escape_string($con, trim($_POST['room_number']));
    $capacity = intval($_POST['capacity']);

    if (!$room_number || !$room_id) {
        $error = 'Room number and room selection are required.';
    } else {
        // Status is automated; only update room_number and capacity here.
        $stmt = $con->prepare("UPDATE room SET room_number = ?, capacity = ? WHERE room_id = ?");
        $stmt->bind_param('sii', $room_number, $capacity, $room_id);
        if ($stmt->execute()) {
            $message = 'Room updated successfully.';
            refresh_room_occupancy_statuses($con);
        } else {
            $error = 'Unable to update room. Please try again.';
        }
        $stmt->close();
    }
}


if (isset($_POST['toggleMaintenance'])) {
    $toggle_room_id = intval($_POST['room_id'] ?? 0);
    if ($toggle_room_id > 0) {
    
        $stmtS = $con->prepare("SELECT status FROM room WHERE room_id = ? LIMIT 1");
        $stmtS->bind_param('i', $toggle_room_id);
        $stmtS->execute();
        $resS = $stmtS->get_result();
        $rowS = $resS->fetch_assoc();
        $stmtS->close();

        if ($rowS && $rowS['status'] === 'maintenance') {
        
            $stmtCount = $con->prepare("SELECT r.capacity, COALESCE(SUM(CASE WHEN b.occupancy_status = 'occupied' THEN 1 ELSE 0 END),0) AS occupied_beds FROM room r LEFT JOIN bed b ON r.room_id = b.room_id WHERE r.room_id = ? GROUP BY r.room_id");
            $stmtCount->bind_param('i', $toggle_room_id);
            $stmtCount->execute();
            $resCount = $stmtCount->get_result();
            $cnt = $resCount->fetch_assoc();
            $stmtCount->close();

            $newStatus = 'available';
            if ($cnt && intval($cnt['occupied_beds']) > 0) {
                if (intval($cnt['capacity']) > 0 && intval($cnt['occupied_beds']) >= intval($cnt['capacity'])) {
                    $newStatus = 'full';
                } else {
                    $newStatus = 'occupied';
                }
            }
            $stmtU = $con->prepare("UPDATE room SET status = ? WHERE room_id = ?");
            $stmtU->bind_param('si', $newStatus, $toggle_room_id);
            $stmtU->execute();
            $stmtU->close();
        } else {
           
            $stmtU = $con->prepare("UPDATE room SET status = 'maintenance' WHERE room_id = ?");
            $stmtU->bind_param('i', $toggle_room_id);
            $stmtU->execute();
            $stmtU->close();
        }
    }
}

// Handle room deletion (only allow if no beds exist for the room)
if (isset($_POST['deleteRoom'])) {
    $del_room_id = intval($_POST['room_id'] ?? 0);
    if ($del_room_id > 0) {
        // Check for beds
        $stmtB = $con->prepare("SELECT COUNT(*) AS bed_count FROM bed WHERE room_id = ?");
        $stmtB->bind_param('i', $del_room_id);
        $stmtB->execute();
        $resB = $stmtB->get_result();
        $cntB = $resB->fetch_assoc();
        $stmtB->close();

        if ($cntB && intval($cntB['bed_count']) > 0) {
            $error = 'Cannot delete room that still has beds. Remove beds first.';
        } else {
            $stmtD = $con->prepare("DELETE FROM room WHERE room_id = ?");
            $stmtD->bind_param('i', $del_room_id);
            if ($stmtD->execute()) {
                $message = 'Room deleted successfully.';
            } else {
                $error = 'Unable to delete room. Please try again.';
            }
            $stmtD->close();
        }
    }
}

function refresh_room_occupancy_statuses($con) {
    $statusSql = "SELECT r.room_id, r.capacity, r.status,
                         COALESCE(SUM(CASE WHEN b.occupancy_status = 'occupied' THEN 1 ELSE 0 END), 0) AS occupied_beds
                  FROM room r
                  LEFT JOIN bed b ON r.room_id = b.room_id
                  GROUP BY r.room_id";
    $result = $con->query($statusSql);
    while ($row = $result->fetch_assoc()) {
        $newStatus = 'available';
        if (intval($row['occupied_beds']) > 0) {
            if (intval($row['capacity']) > 0 && intval($row['occupied_beds']) >= intval($row['capacity'])) {
                $newStatus = 'full';
            } else {
                $newStatus = 'occupied';
            }
        }

        if ($row['status'] === 'maintenance') {
            continue;
        }

        if ($newStatus !== $row['status']) {
            $stmtStatus = $con->prepare("UPDATE room SET status = ? WHERE room_id = ?");
            $stmtStatus->bind_param('si', $newStatus, $row['room_id']);
            $stmtStatus->execute();
            $stmtStatus->close();
        }
    }
}

if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    if ($edit_id > 0) {
        $stmt = $con->prepare("SELECT room_id, room_number, capacity, status FROM room WHERE room_id = ? LIMIT 1");
        $stmt->bind_param('i', $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_room = $result->fetch_assoc();
        $stmt->close();
    }
}

refresh_room_occupancy_statuses($con);

$sqlRooms = "SELECT r.room_id, r.room_number, r.capacity, r.status,
                    COUNT(b.bed_id) AS total_beds,
                    COALESCE(SUM(CASE WHEN b.occupancy_status = 'occupied' THEN 1 ELSE 0 END), 0) AS occupied_beds,
                    GREATEST(r.capacity - COALESCE(SUM(CASE WHEN b.occupancy_status = 'occupied' THEN 1 ELSE 0 END), 0), 0) AS vacant_beds
            FROM room r
            LEFT JOIN bed b ON r.room_id = b.room_id
            GROUP BY r.room_id
            ORDER BY r.room_number";

$roomList = $con->query($sqlRooms);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room List | StayEase</title>
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
            <a href="rooms.php" class="active">Rooms</a>
            <a href="billing.php">Billing</a>
            <a href="maintenance.php">Maintenance</a>
            <a href="violations.php">Violations</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="page-content">
        <section class="page-hero">
            <div>
                <p class="section-label">Room Management</p>
                <h1>Room List</h1>
                <p>Track rooms and view bed occupancy status across your boarding house.</p>
            </div>
            <a href="beds.php" class="btn-secondary">Manage Beds</a>
        </section>

        <section class="panel form-panel">
            <div class="panel-heading">
                <h2><?php echo $edit_room ? 'Edit Room' : 'Add Room'; ?></h2>
            </div>
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" action="rooms.php<?php echo $edit_room ? '?edit_id=' . intval($edit_room['room_id']) : ''; ?>" class="form-grid">
                <?php if ($edit_room): ?>
                    <input type="hidden" name="room_id" value="<?php echo intval($edit_room['room_id']); ?>">
                <?php endif; ?>
                <div class="input-group">
                    <label for="room_number">Room Number</label>
                    <input type="text" id="room_number" name="room_number" required value="<?php echo $edit_room ? htmlspecialchars($edit_room['room_number']) : ''; ?>">
                </div>
                <div class="input-group">
                    <label for="capacity">Capacity</label>
                    <input type="number" id="capacity" name="capacity" min="0" value="<?php echo $edit_room ? intval($edit_room['capacity']) : '0'; ?>" required>
                </div>
                <!-- Status is automatic and not editable here -->
                <div class="input-group input-full actions-row">
                    <?php if ($edit_room): ?>
                        <button type="submit" class="btn-primary" name="btnUpdateRoom">Update Room</button>
                        <a href="rooms.php" class="btn-secondary" style="margin-left:12px;">Cancel</a>
                    <?php else: ?>
                        <button type="submit" class="btn-primary" name="btnAddRoom">Add Room</button>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Room Inventory</h2>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Occupancy</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($roomList && $roomList->num_rows > 0): ?>
                            <?php while ($row = $roomList->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['room_number']); ?></td>
                                    <td><?php echo intval($row['capacity']); ?></td>
                                    <td><?php echo htmlspecialchars($row['status'] === 'full' ? 'Fully occupied' : ucfirst($row['status'])); ?></td>
                                    <td>
                                        <?php
                                            if ($row['status'] === 'maintenance') {
                                                echo '-';
                                            } else {
                                                $occ = intval($row['occupied_beds']);
                                                $cap = intval($row['capacity']);
                                                $cap = $cap >= 0 ? $cap : 0;
                                                $pct = ($cap > 0) ? round(($occ / $cap) * 100) : 0;
                                                echo $occ . '/' . $cap;
                                                ?>
                                                <div class="occupancy-bar" aria-hidden="true">
                                                    <div class="occupancy-fill" style="width: <?php echo htmlspecialchars($pct); ?>%; background: <?php echo ($pct >= 100 ? '#c0392b' : ($pct >= 70 ? '#f39c12' : '#27ae60')); ?>;"></div>
                                                </div>
                                                <?php
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="rooms.php?edit_id=<?php echo intval($row['room_id']); ?>" class="link-button">Edit</a>
                                        <?php if ($row['status'] !== 'maintenance'): ?>
                                            <?php if (intval($row['vacant_beds']) > 0): ?>
                                                <a href="boarder_register.php?room_id=<?php echo intval($row['room_id']); ?>" class="btn-primary" style="margin-left:8px;">Add Tenant</a>
                                            <?php endif; ?>
                                            <form method="post" action="rooms.php" style="display:inline-block; margin-left:8px;">
                                                <input type="hidden" name="room_id" value="<?php echo intval($row['room_id']); ?>">
                                                <button type="submit" name="toggleMaintenance" class="btn-secondary">Mark Maintenance</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="rooms.php" style="display:inline-block; margin-left:8px;">
                                                <input type="hidden" name="room_id" value="<?php echo intval($row['room_id']); ?>">
                                                <button type="submit" name="toggleMaintenance" class="btn-primary">Clear Maintenance</button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="post" action="rooms.php" style="display:inline-block; margin-left:8px;" onsubmit="return confirm('Delete this room? This action cannot be undone.');">
                                            <input type="hidden" name="room_id" value="<?php echo intval($row['room_id']); ?>">
                                            <button type="submit" name="deleteRoom" class="btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">No rooms found yet. Add a room to get started.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
