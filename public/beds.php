<?php
session_start();
require_once 'config.php';

// Security Gatekeeper
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';
$edit_bed = null;

// --- PRG PATTERN: CATCH SUCCESS REDIRECT ---
if (isset($_GET['status']) && $_GET['status'] === 'updated') {
    $message = 'Bed updated successfully.';
}

// --- FETCH ALL EXISTING ROOMS FOR THE DROPDOWN ---
$roomList = $con->query("SELECT room_id, room_number FROM room ORDER BY room_number ASC");

// --- LOGIC FOR ADDING A NEW BED ---
if (isset($_POST['btnAddBed'])) {
    $bed_number      = mysqli_real_escape_string($con, trim($_POST['bed_number']));
    $room_id         = !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
    $occupancy_status = mysqli_real_escape_string($con, $_POST['occupancy_status']);

    if (!$bed_number) {
        $error = 'Bed number is required.';
    } else {
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

// --- LOGIC FOR UPDATING AN EXISTING BED ---
if (isset($_POST['btnUpdateBed'])) {
    $bed_id          = intval($_POST['bed_id']);
    $bed_number      = mysqli_real_escape_string($con, trim($_POST['bed_number']));
    $room_id         = !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
    $occupancy_status = mysqli_real_escape_string($con, $_POST['occupancy_status']);

    if (!$bed_number || !$bed_id) {
        $error = 'Bed number is required.';
    } else {
        if ($room_id !== null) {
            $stmtUpdate = $con->prepare("UPDATE bed SET room_id = ?, bed_number = ?, occupancy_status = ? WHERE bed_id = ?");
            $stmtUpdate->bind_param('issi', $room_id, $bed_number, $occupancy_status, $bed_id);
        } else {
            $stmtUpdate = $con->prepare("UPDATE bed SET room_id = NULL, bed_number = ?, occupancy_status = ? WHERE bed_id = ?");
            $stmtUpdate->bind_param('ssi', $bed_number, $occupancy_status, $bed_id);
        }

        if ($stmtUpdate->execute()) {
            $stmtUpdate->close();
            header("Location: beds.php?status=updated");
            exit();
        } else {
            $error = 'Unable to update the bed. Please try again.';
            $stmtUpdate->close();
        }
    }
}

// --- FETCH DATA IF WE ARE IN EDIT MODE ---
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    if ($edit_id > 0) {
        $stmt = $con->prepare("SELECT bed_id, bed_number, occupancy_status, room_id FROM bed WHERE bed_id = ? LIMIT 1");
        $stmt->bind_param('i', $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_bed = $result->fetch_assoc();
        $stmt->close();
    }
}

// --- FETCH ALL BEDS FOR THE TABLE ---
$bedList = $con->query("
    SELECT bed.bed_id, bed.bed_number, bed.occupancy_status, room.room_number
    FROM bed
    LEFT JOIN room ON bed.room_id = room.room_id
    ORDER BY room.room_number, bed.bed_number
");
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
        </section>

        <section class="panel form-panel">
            <div class="panel-heading">
                <h2><?= $edit_bed ? 'Edit Bed' : 'Add New Bed' ?></h2>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="beds.php<?= $edit_bed ? '?edit_id=' . intval($edit_bed['bed_id']) : '' ?>" class="form-grid">

                <?php if ($edit_bed): ?>
                    <input type="hidden" name="bed_id" value="<?= intval($edit_bed['bed_id']) ?>">
                <?php endif; ?>

                <div class="input-group">
                    <label for="bed_number">Bed Number</label>
                    <input type="text" id="bed_number" name="bed_number" required
                           value="<?= $edit_bed ? htmlspecialchars($edit_bed['bed_number']) : '' ?>">
                </div>

                <div class="input-group">
                    <label for="room_id">Assign to Room</label>
                    <select id="room_id" name="room_id">
                        <option value="">— Unassigned —</option>
                        <?php
                        // Rewind the result set in case it was already iterated
                        if ($roomList && $roomList->num_rows > 0):
                            $roomList->data_seek(0);
                            while ($room = $roomList->fetch_assoc()):
                                $selected = ($edit_bed && $edit_bed['room_id'] == $room['room_id']) ? 'selected' : '';
                        ?>
                            <option value="<?= intval($room['room_id']) ?>" <?= $selected ?>>
                                Room <?= htmlspecialchars($room['room_number']) ?>
                            </option>
                        <?php
                            endwhile;
                        endif;
                        ?>
                    </select>
                </div>

                <div class="input-group">
                    <label for="occupancy_status">Occupancy Status</label>
                    <select id="occupancy_status" name="occupancy_status" required>
                        <option value="vacant"   <?= $edit_bed && $edit_bed['occupancy_status'] === 'vacant'   ? 'selected' : '' ?>>Vacant</option>
                        <option value="occupied" <?= $edit_bed && $edit_bed['occupancy_status'] === 'occupied' ? 'selected' : '' ?>>Occupied</option>
                    </select>
                </div>

                <div class="input-group input-full actions-row">
                    <?php if ($edit_bed): ?>
                        <button type="submit" class="btn-primary" name="btnUpdateBed">Update Bed</button>
                        <a href="beds.php" class="btn-secondary" style="margin-left:12px;">Cancel</a>
                    <?php else: ?>
                        <button type="submit" class="btn-primary" name="btnAddBed">Create Bed</button>
                    <?php endif; ?>
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
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bedList && $bedList->num_rows > 0): ?>
                            <?php while ($row = $bedList->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['bed_id']) ?></td>
                                    <td><?= htmlspecialchars($row['bed_number']) ?></td>
                                    <td><?= $row['room_number'] ? 'Room ' . htmlspecialchars($row['room_number']) : 'Unassigned' ?></td>
                                    <td>
                                        <?php if ($row['occupancy_status'] === 'occupied'): ?>
                                            <span class="tag tag-warning">Occupied</span>
                                        <?php else: ?>
                                            <span class="tag">Vacant</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="beds.php?edit_id=<?= intval($row['bed_id']) ?>" class="link-button">Edit</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">No beds have been added yet. Create a bed to make it available for boarders.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>