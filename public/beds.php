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

// --- LOGIC FOR ADDING A NEW BED ---
if (isset($_POST['btnAddBed'])) {
    $bed_number = mysqli_real_escape_string($con, trim($_POST['bed_number']));
    $room_number = mysqli_real_escape_string($con, trim($_POST['room_number']));
    $occupancy_status = mysqli_real_escape_string($con, $_POST['occupancy_status']);

    if (!$bed_number) {
        $error = 'Bed number is required.';
    } else {
        $room_id = null;

        // Smart Room Check
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

// --- LOGIC FOR UPDATING AN EXISTING BED ---
if (isset($_POST['btnUpdateBed'])) {
    $bed_id = intval($_POST['bed_id']);
    $bed_number = mysqli_real_escape_string($con, trim($_POST['bed_number']));
    $room_number = mysqli_real_escape_string($con, trim($_POST['room_number']));
    $occupancy_status = mysqli_real_escape_string($con, $_POST['occupancy_status']);

    if (!$bed_number || !$bed_id) {
        $error = 'Bed number is required.';
    } else {
        $room_id = null;

        // Smart Room Check (Same as adding)
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

        // Execute the Update
        if ($room_id !== null) {
            $stmtUpdate = $con->prepare("UPDATE bed SET room_id = ?, bed_number = ?, occupancy_status = ? WHERE bed_id = ?");
            $stmtUpdate->bind_param('issi', $room_id, $bed_number, $occupancy_status, $bed_id);
        } else {
            $stmtUpdate = $con->prepare("UPDATE bed SET room_id = NULL, bed_number = ?, occupancy_status = ? WHERE bed_id = ?");
            $stmtUpdate->bind_param('ssi', $bed_number, $occupancy_status, $bed_id);
        }

        if ($stmtUpdate->execute()) {
            $stmtUpdate->close();
            // PRG REDIRECT: Snap back to clean URL
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
        $stmt = $con->prepare("SELECT bed.bed_id, bed.bed_number, bed.occupancy_status, room.room_number FROM bed LEFT JOIN room ON bed.room_id = room.room_id WHERE bed.bed_id = ? LIMIT 1");
        $stmt->bind_param('i', $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $edit_bed = $result->fetch_assoc();
        $stmt->close();
    }
}

// --- FETCH ALL BEDS FOR THE TABLE ---
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
                <h2><?php echo $edit_bed ? 'Edit Bed' : 'Add New Bed'; ?></h2>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="post" action="beds.php<?php echo $edit_bed ? '?edit_id=' . intval($edit_bed['bed_id']) : ''; ?>" class="form-grid">
                
                <?php if ($edit_bed): ?>
                    <input type="hidden" name="bed_id" value="<?php echo intval($edit_bed['bed_id']); ?>">
                <?php endif; ?>

                <div class="input-group">
                    <label for="bed_number">Bed Number</label>
                    <input type="text" id="bed_number" name="bed_number" required value="<?php echo $edit_bed ? htmlspecialchars($edit_bed['bed_number']) : ''; ?>">
                </div>
                
                <div class="input-group">
                    <label for="room_number">Room Number (optional)</label>
                    <input type="text" id="room_number" name="room_number" placeholder="Example: 101" value="<?php echo $edit_bed && $edit_bed['room_number'] ? htmlspecialchars($edit_bed['room_number']) : ''; ?>">
                </div>
                
                <div class="input-group">
                    <label for="occupancy_status">Occupancy Status</label>
                    <select id="occupancy_status" name="occupancy_status" required>
                        <option value="vacant" <?php echo $edit_bed && $edit_bed['occupancy_status'] === 'vacant' ? 'selected' : ''; ?>>Vacant</option>
                        <option value="occupied" <?php echo $edit_bed && $edit_bed['occupancy_status'] === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
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
                                    <td>
                                        <a href="beds.php?edit_id=<?php echo intval($row['bed_id']); ?>" class="link-button">Edit</a>
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
</html>c