<?php
// Start session and check if user is logged in
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit();
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "login");
if ($conn->connect_error) die("DB Error");

include("log.php");

$username = $_SESSION['user']['username'];

// Reset all audit logs
if (isset($_POST['reset_logs'])) {
    $conn->query("DELETE FROM audit_logs");
    logAction($conn, $username, "Reset Logs", "All audit logs cleared");
    header("Location: admin.php?reset=logs");
    exit();
}

// Fetch current system settings
$settings = $conn->query("SELECT * FROM settings WHERE setting_id=1")->fetch_assoc();

// Update system settings
if (isset($_POST['update_settings'])) {
    $store    = $_POST['store_name'];
    $hours    = $_POST['business_hours'];
    $currency = $_POST['currency'];

    $stmt = $conn->prepare("
        UPDATE settings 
        SET store_name=?, business_hours=?, currency=? 
        WHERE setting_id=1
    ");
    $stmt->bind_param("sss", $store, $hours, $currency);
    $stmt->execute();

    logAction($conn, $username, "Update Settings", "System settings updated");

    header("Location: admin.php?success=1");
    exit();
}

// Add new user
if (isset($_POST['add_user'])) {
    $user     = $_POST['username'];
    $pass     = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = $_POST['role'];
    $fullname = $_POST['fullname'];
    $status   = $_POST['status'];

    // Handle avatar upload
    $avatar = "";
    if (!empty($_FILES['avatar']['name'])) {
        $avatar = time() . "_" . $_FILES['avatar']['name'];
        move_uploaded_file($_FILES['avatar']['tmp_name'], "../../resources/images/uploads/" . $avatar);
    }

    $stmt = $conn->prepare("
        INSERT INTO users (username, password, role, fullname, status, avatar)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssss", $user, $pass, $role, $fullname, $status, $avatar);
    $stmt->execute();

    logAction($conn, $username, "Add User", "Added user: $user");
}

// Delete a user
if (isset($_POST['delete_user'])) {
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
    $stmt->bind_param("i", $_POST['id']);
    $stmt->execute();

    logAction($conn, $username, "Delete User", "Deleted user ID: " . $_POST['id']);
}

// Update existing user
if (isset($_POST['update_user'])) {
    $id       = $_POST['id'];
    $fullname = $_POST['fullname'];
    $role     = $_POST['role'];
    $status   = $_POST['status'];

    // Keep existing avatar if no new file is uploaded
    $q = $conn->prepare("SELECT avatar FROM users WHERE user_id=?");
    $q->bind_param("i", $id);
    $q->execute();
    $current = $q->get_result()->fetch_assoc();
    $avatar  = $current['avatar'];

    if (!empty($_FILES['avatar']['name'])) {
        $avatar = time() . "_" . $_FILES['avatar']['name'];
        move_uploaded_file($_FILES['avatar']['tmp_name'], "../../resources/images/uploads/" . $avatar);
    }

    $stmt = $conn->prepare("
        UPDATE users 
        SET fullname=?, role=?, status=?, avatar=? 
        WHERE user_id=?
    ");
    $stmt->bind_param("ssssi", $fullname, $role, $status, $avatar, $id);
    $stmt->execute();

    logAction($conn, $username, "Edit User", "Edited user ID: $id");

    header("Location: admin.php");
    exit();
}

// Fetch user data for editing if edit param is set
$editUser = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $editUser = $stmt->get_result()->fetch_assoc();
}

// Fetch all users
$users = $conn->query("SELECT * FROM users");

// Fetch latest 20 audit logs
$logs = $conn->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 20");
?>

<!DOCTYPE html>
<html>
<head>

 <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <!-- SEO -->
    <meta name="author" content="Al Coffee">
    <meta name="description" content="Al Coffee Reports & Analytics - Generate detailed reports, analyze sales and inventory data, and gain real-time insights to support informed decisions and improve overall business performance.">
     <!-- SEO -->

    <link rel="stylesheet" href="../../resources/css/general.css">
    <link rel="stylesheet" href="../../resources/css/design_tokens/primitives.css">
    <link rel="stylesheet" href="../../resources/css/design_tokens/mapping.css">
    <link rel="stylesheet" href="../../resources/css/partials/header.css">
    <link rel="stylesheet" href="../../resources/css/partials/side_nav.css">
    <link rel="stylesheet" href="../../resources/css/main_interface.css">
     <link rel="stylesheet" href="../../resources/css/admin.css">

    <link rel="icon" type="image/svg+xml" href="../../resources/images/logo/logo_black.svg">
     
<title>Admin — Al Coffee</title>
</head>

<body>

<div class="container">

    <?php include '../partials/header.php'; ?>
    <?php require '../partials/sidenav.php'; ?>

    <div class="main">

        <h1>Admin Panel</h1>

        <!-- Edit user form (shown only when edit is triggered) -->
        <?php if ($editUser): ?>
        <div class="card">
            <h2>Edit User</h2>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $editUser['user_id'] ?>">

                <input type="text" name="fullname" value="<?= htmlspecialchars($editUser['fullname']) ?>" required>

                <select name="role">
                    <option <?= $editUser['role'] == "admin"    ? "selected" : "" ?>>admin</option>
                    <option <?= $editUser['role'] == "staff"    ? "selected" : "" ?>>staff</option>
                    <option <?= $editUser['role'] == "cashier"  ? "selected" : "" ?>>cashier</option>
                </select>

                <select name="status">
                    <option <?= $editUser['status'] == "active"   ? "selected" : "" ?>>active</option>
                    <option <?= $editUser['status'] == "inactive" ? "selected" : "" ?>>inactive</option>
                </select>

                <input type="file" name="avatar">

                <button name="update_user">Update User</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- System settings form -->
        <div class="card">
            <h2>System Settings</h2>

            <form method="POST">
                <input type="text" name="store_name"      value="<?= $settings['store_name']      ?? '' ?>" required>
                <input type="text" name="business_hours"  value="<?= $settings['business_hours']  ?? '' ?>" required>

                <select name="currency">
                    <option value="PHP">PHP</option>
                    <option value="USD">USD</option>
                </select>

                <button name="update_settings">Save</button>
            </form>
        </div>

        <!-- Add new user form -->
        <div class="card">
            <h2>Add User</h2>

            <form method="POST" enctype="multipart/form-data">
                <input type="text"     name="fullname"  placeholder="Full Name" required>
                <input type="text"     name="username"  placeholder="Username"  required>
                <input type="password" name="password"  placeholder="Password"  required>

                <select name="role">
                    <option>admin</option>
                    <option>staff</option>
                    <option>cashier</option>
                </select>

                <select name="status">
                    <option>active</option>
                    <option>inactive</option>
                </select>

                <input type="file" name="avatar">

                <button name="add_user">Add User</button>
            </form>
        </div>

        <!-- Users table -->
        <div class="card">
            <h2>Users</h2>

            <table>
                <tr>
                    <th>Avatar</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>

                <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php if ($u['avatar']): ?>
                            <img class="avatar_img" src="../../resources/images/uploads/<?= $u['avatar'] ?>" alt="Avatar">
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($u['fullname']) ?></td>
                    <td><?= $u['role'] ?></td>
                    <td><?= $u['status'] ?></td>
                    <td>
                        <!-- Edit button -->
                        <a href="admin.php?edit=<?= $u['user_id'] ?>">
                            <button type="button">Edit</button>
                        </a>

                        <!-- Delete button -->
                        <form method="POST" onsubmit="return confirm('Delete this user?')" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $u['user_id'] ?>">
                            <button name="delete_user">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <!-- Audit logs table -->
        <div class="card">
            <h2>Audit Logs</h2>

            <!-- Reset logs button -->
            <form method="POST" onsubmit="return confirm('Delete ALL logs?');">
                <button name="reset_logs" class="reset-btn">Reset Logs</button>
            </form>

            <table>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>Date</th>
                </tr>

                <?php while ($log = $logs->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($log['user'])    ?></td>
                    <td><?= htmlspecialchars($log['action'])  ?></td>
                    <td><?= htmlspecialchars($log['details']) ?></td>
                    <td><?= $log['created_at'] ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

    </div>
</div>
         <script src="../../resources/js/partials/sidebar.js" type="text/javascript" defer></script>
        <script src="../../resources/js/partials/menu_mobile.js" type="text/javascript" defer></script>
</body>
</html>