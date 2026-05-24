<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Allow preselecting a room via GET param (rooms.php?room_id=NN)
$selected_room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
if ($selected_room_id > 0) {
    $stmtBeds = $con->prepare("SELECT bed.bed_id, bed.bed_number, room.room_number FROM bed LEFT JOIN room ON bed.room_id = room.room_id WHERE (bed.occupancy_status IS NULL OR bed.occupancy_status <> 'occupied') AND bed.room_id = ? ORDER BY room.room_number, bed.bed_number");
    $stmtBeds->bind_param('i', $selected_room_id);
    $stmtBeds->execute();
    $bedResult = $stmtBeds->get_result();
    $stmtBeds->close();
} else {
    $bedResult = $con->query("SELECT bed.bed_id, bed.bed_number, room.room_number FROM bed LEFT JOIN room ON bed.room_id = room.room_id WHERE bed.occupancy_status IS NULL OR bed.occupancy_status <> 'occupied' ORDER BY room.room_number, bed.bed_number");
}

function refresh_room_status_by_bed($con, $bed_id) {
    $stmtRoom = $con->prepare("SELECT room_id FROM bed WHERE bed_id = ? LIMIT 1");
    $stmtRoom->bind_param('i', $bed_id);
    $stmtRoom->execute();
    $roomResult = $stmtRoom->get_result();
    $roomData = $roomResult->fetch_assoc();
    $stmtRoom->close();

    if (!$roomData || !intval($roomData['room_id'])) {
        return;
    }

    $room_id = intval($roomData['room_id']);
    $stmtCount = $con->prepare("SELECT r.capacity,
                                      COALESCE(SUM(CASE WHEN b.occupancy_status = 'occupied' THEN 1 ELSE 0 END), 0) AS occupied_beds
                               FROM room r
                               LEFT JOIN bed b ON r.room_id = b.room_id
                               WHERE r.room_id = ?
                               GROUP BY r.room_id");
    $stmtCount->bind_param('i', $room_id);
    $stmtCount->execute();
    $countResult = $stmtCount->get_result();
    $countData = $countResult->fetch_assoc();
    $stmtCount->close();

    if (!$countData) {
        return;
    }

    $newStatus = 'available';
    if (intval($countData['occupied_beds']) > 0) {
        if (intval($countData['capacity']) > 0 && intval($countData['occupied_beds']) >= intval($countData['capacity'])) {
            $newStatus = 'full';
        } else {
            $newStatus = 'occupied';
        }
    }

    $stmtUpdate = $con->prepare("UPDATE room SET status = ? WHERE room_id = ?");
    $stmtUpdate->bind_param('si', $newStatus, $room_id);
    $stmtUpdate->execute();
    $stmtUpdate->close();
}

if (isset($_POST['btnRegister'])) {
    $first_name = mysqli_real_escape_string($con, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($con, $_POST['last_name']);
    $gender = mysqli_real_escape_string($con, $_POST['gender']);
    $birthdate = mysqli_real_escape_string($con, $_POST['birthdate']);
    $contact_number = mysqli_real_escape_string($con, $_POST['contact_number']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $address = mysqli_real_escape_string($con, $_POST['address']);
    $bed_id = intval($_POST['bed_id']);
    $start_date = mysqli_real_escape_string($con, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($con, $_POST['end_date']);
    $monthly_rate = mysqli_real_escape_string($con, $_POST['monthly_rate']);
    $status = mysqli_real_escape_string($con, $_POST['status']);

    if (!$first_name || !$last_name || !$gender || !$birthdate || !$contact_number || !$bed_id || !$start_date || !$end_date || !$monthly_rate) {
        $error = 'Please fill in all required fields.';
    } else {
        $stmt = $con->prepare("INSERT INTO boarder (first_name, last_name, gender, birthdate, contact_number, email, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssss', $first_name, $last_name, $gender, $birthdate, $contact_number, $email, $address);
        if ($stmt->execute()) {
            $boarder_id = $con->insert_id;
            $stmt2 = $con->prepare("INSERT INTO rental_agreement (boarder_id, bed_id, start_date, end_date, monthly_rate, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param('iissds', $boarder_id, $bed_id, $start_date, $end_date, $monthly_rate, $status);
            if ($stmt2->execute()) {
                $con->query("UPDATE bed SET occupancy_status = 'occupied' WHERE bed_id = $bed_id");
                refresh_room_status_by_bed($con, $bed_id);
                $message = 'Boarder registered successfully and assigned to a bed.';
                $error = '';
            } else {
                $error = 'Unable to create rental agreement. Please try again.';
            }
        } else {
            $error = 'Unable to register boarder. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Boarder | StayEase</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="app-header">
        <div class="header-brand">STAYEASE</div>
        <nav class="app-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="boarders.php">Boarders</a>
            <a href="boarder_register.php" class="active">Add Boarder</a>
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
                <p class="section-label">New Boarder</p>
                <h1>Register a Boarder</h1>
                <p>Assign a bed and create the rental agreement in one step.</p>
            </div>
            <a href="boarders.php" class="btn-secondary">View Boarders</a>
        </section>

        <section class="panel form-panel">
            <div class="panel-heading">
                <h2>Boarder Registration</h2>
            </div>
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" action="boarder_register.php" class="form-grid">
                <div class="input-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="input-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <div class="input-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="birthdate">Birthdate</label>
                    <input type="date" id="birthdate" name="birthdate" required>
                </div>
                <div class="input-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="text" id="contact_number" name="contact_number" required>
                </div>
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email">
                </div>
                <div class="input-group input-full">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"></textarea>
                </div>
                <div class="input-group">
                    <label for="bed_id">Assign Bed</label>
                    <select id="bed_id" name="bed_id" required>
                        <option value="">Select available bed</option>
                        <?php if ($bedResult && $bedResult->num_rows): ?>
                            <?php while ($bed = $bedResult->fetch_assoc()): ?>
                                <option value="<?php echo $bed['bed_id']; ?>">Room <?php echo htmlspecialchars($bed['room_number']); ?> — Bed <?php echo htmlspecialchars($bed['bed_number']); ?></option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="">No available beds</option>
                        <?php endif; ?>
                    </select>
                    <?php if (!$bedResult || $bedResult->num_rows === 0): ?>
                        <p class="form-note">No beds available yet. <a href="beds.php">Add a bed</a> first.</p>
                    <?php endif; ?>
                </div>
                <div class="input-group">
                    <label for="start_date">Agreement Start</label>
                    <input type="date" id="start_date" name="start_date" required>
                </div>
                <div class="input-group">
                    <label for="end_date">Agreement End</label>
                    <input type="date" id="end_date" name="end_date" required>
                </div>
                <div class="input-group">
                    <label for="monthly_rate">Monthly Rate</label>
                    <input type="number" step="0.01" id="monthly_rate" name="monthly_rate" required>
                </div>
                <div class="input-group">
                    <label for="status">Agreement Status</label>
                    <select id="status" name="status" required>
                        <option value="Active">Active</option>
                        <option value="Pending">Pending</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <div class="input-group input-full actions-row">
                    <button type="submit" class="btn-primary" name="btnRegister">Register Boarder</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
