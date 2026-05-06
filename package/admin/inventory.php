<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit();
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "login");
if ($conn->connect_error) die("Connection failed");

// Generate CSRF token if not yet set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

// Validate CSRF token for all POST requests
function validateCsrf(): void {
    if (
        !isset($_POST['csrf_token']) ||
        $_POST['csrf_token'] !== $_SESSION['csrf_token']
    ) {
        die("Invalid CSRF token.");
    }
}

// Handle image upload, returns filename on success or empty string
function handleUpload(): string {
    if (empty($_FILES['image']['name'])) {
        return "";
    }

    // Check upload error
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        die("File upload failed.");
    }

    // Validate file size (max 2MB)
    $max_size = 2 * 1024 * 1024;
    if ($_FILES['image']['size'] > $max_size) {
        die("File too large. Max size is 2MB.");
    }

    // Validate actual MIME type using finfo (not spoofable unlike $_FILES['type'])
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['image']['tmp_name']);

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowed_types)) {
        die("Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.");
    }

    // Sanitize filename
    $filename    = time() . "_" . preg_replace('/[^a-zA-Z0-9.\-_]/', '_', basename($_FILES['image']['name']));
    $destination = "../../resources/images/uploads/" . $filename;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
        die("Failed to save uploaded file.");
    }

    return $filename;
}

// Delete image file from uploads folder
function deleteImage(string $image): void {
    $path = "../../resources/images/uploads/" . $image;
    if (!empty($image) && file_exists($path)) {
        unlink($path);
    }
}

// Add new ingredient
if (isset($_POST['add_ingredient'])) {
    validateCsrf();

    $name  = trim($_POST['ingredient_name']);
    $stock = intval($_POST['stock']);
    $low   = intval($_POST['low_stock_threshold']);
    $unit  = $_POST['unit'];

    // Validate stock and threshold values
    if ($stock < 0 || $low < 0) {
        die("Stock and threshold values must not be negative.");
    }

    $image = handleUpload();

    $stmt = $conn->prepare("
        INSERT INTO ingredients (ingredient_name, stock, low_stock_threshold, unit, image)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("siiss", $name, $stock, $low, $unit, $image);
    $stmt->execute();
    $stmt->close();

    $_SESSION['notif'] = ['type' => 'added', 'name' => $name];
    header("Location: inventory.php");
    exit();
}

// Delete an ingredient
if (isset($_POST['delete_ing'])) {
    validateCsrf();

    $delId = intval($_POST['id']);

    // Fetch ingredient name and image before deleting
    $stmt = $conn->prepare("SELECT ingredient_name, image FROM ingredients WHERE ingredient_id = ?");
    $stmt->bind_param("i", $delId);
    $stmt->execute();
    $row     = $stmt->get_result()->fetch_assoc();
    $delName = $row ? $row['ingredient_name'] : 'Ingredient';
    $stmt->close();

    // Delete image file from server
    if ($row && !empty($row['image'])) {
        deleteImage($row['image']);
    }

    $stmt = $conn->prepare("DELETE FROM ingredients WHERE ingredient_id = ?");
    $stmt->bind_param("i", $delId);
    $stmt->execute();
    $stmt->close();

    $_SESSION['notif'] = ['type' => 'deleted', 'name' => $delName];
    header("Location: inventory.php");
    exit();
}

// Load ingredient data for edit form
$editIng = null;

if (isset($_POST['edit_ing'])) {
    validateCsrf();

    $editId = intval($_POST['id']);

    $stmt = $conn->prepare("SELECT * FROM ingredients WHERE ingredient_id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editIng = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Update an existing ingredient
if (isset($_POST['update_ing'])) {
    validateCsrf();

    $id    = intval($_POST['id']);
    $name  = trim($_POST['ingredient_name']);
    $stock = intval($_POST['stock']);
    $low   = intval($_POST['low_stock_threshold']);
    $unit  = $_POST['unit'];

    // Validate stock and threshold values
    if ($stock < 0 || $low < 0) {
        die("Stock and threshold values must not be negative.");
    }

    $newImage = handleUpload();

    if (!empty($newImage)) {
        // Fetch and delete old image before replacing
        $stmt = $conn->prepare("SELECT image FROM ingredients WHERE ingredient_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($old && !empty($old['image'])) {
            deleteImage($old['image']);
        }

        // Update with new image using a proper prepared statement
        $stmt = $conn->prepare("
            UPDATE ingredients
            SET ingredient_name = ?, stock = ?, low_stock_threshold = ?, unit = ?, image = ?
            WHERE ingredient_id = ?
        ");
        $stmt->bind_param("siissi", $name, $stock, $low, $unit, $newImage, $id);
    } else {
        // Update without changing image
        $stmt = $conn->prepare("
            UPDATE ingredients
            SET ingredient_name = ?, stock = ?, low_stock_threshold = ?, unit = ?
            WHERE ingredient_id = ?
        ");
        $stmt->bind_param("siisi", $name, $stock, $low, $unit, $id);
    }

    $stmt->execute();
    $stmt->close();

    $_SESSION['notif'] = ['type' => 'updated', 'name' => $name];
    header("Location: inventory.php");
    exit();
}

// Get filter values from GET params
$search     = trim($_GET['search']      ?? '');
$stockLevel = trim($_GET['stock_level'] ?? '');
$category   = trim($_GET['category']    ?? '');

// Build dynamic WHERE clause for ingredient search/filter
$conditions = [];
$ingParams  = [];
$ingTypes   = "";

if (!empty($search)) {
    $conditions[] = "ingredient_name LIKE ?";
    $ingParams[]  = "%" . $search . "%";
    $ingTypes    .= "s";
}

if (!empty($category)) {
    $conditions[] = "unit = ?";
    $ingParams[]  = $category;
    $ingTypes    .= "s";
}

if ($stockLevel === 'low') {
    $conditions[] = "stock <= low_stock_threshold";
} elseif ($stockLevel === 'mid') {
    $conditions[] = "stock > low_stock_threshold AND stock <= low_stock_threshold * 3";
} elseif ($stockLevel === 'high') {
    $conditions[] = "stock > low_stock_threshold * 3";
}

$sql = "SELECT * FROM ingredients";
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Fetch filtered ingredients
$ingStmt = $conn->prepare($sql);
if (!empty($ingParams)) {
    $ingStmt->bind_param($ingTypes, ...$ingParams);
}
$ingStmt->execute();
$ingredients = $ingStmt->get_result();

if (!$ingredients) {
    die("Query failed: " . $conn->error);
}

$ingStmt->close();

// Fetch low stock alerts
$lowStockAlerts = [];

$lowIngStmt = $conn->prepare("
    SELECT ingredient_name AS label, stock, low_stock_threshold 
    FROM ingredients 
    WHERE stock <= low_stock_threshold 
    ORDER BY ingredient_name
");
$lowIngStmt->execute();
$lowIngResult = $lowIngStmt->get_result();
$lowIngStmt->close();

while ($r = $lowIngResult->fetch_assoc()) {
    $lowStockAlerts[] = $r;
}

// Unit/category options
$unitOptions = ["Can-based", "Bottle-based", "Box-based", "Plastic-based", "Pack-based"];

// Read and clear session notification
$notif = null;
if (!empty($_SESSION['notif'])) {
    $notif = $_SESSION['notif'];
    unset($_SESSION['notif']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
      <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <!-- SEO -->
    <meta name="author" content="Al Coffee">
    <meta name="description" content="Al Coffee Inventory System - Efficiently manage stock levels, record inventory transactions, track product availability, and generate real-time insights to support accurate and streamlined inventory operations.">
     <!-- SEO -->

    <link rel="stylesheet" href="../../resources/css/general.css">
    <link rel="stylesheet" href="../../resources/css/design_tokens/primitives.css">
    <link rel="stylesheet" href="../../resources/css/design_tokens/mapping.css">
    <link rel="stylesheet" href="../../resources/css/partials/header.css">
    <link rel="stylesheet" href="../../resources/css/partials/side_nav.css">
    <link rel="stylesheet" href="../../resources/css/main_interface.css">
     <link rel="stylesheet" href="../../resources/css/inventory.css">

    <link rel="icon" type="image/svg+xml" href="../../resources/images/logo/logo_black.svg">
    
    <title>Inventory Management - Al Coffee</title>
</head>

<body>

<div class="container">
    
 <?php include '../partials/header.php'; ?>
 <?php require '../partials/sidenav.php'; ?>


    <div class="main">

        <h1>INVENTORY</h1>

        <!-- Low stock alert banner -->
        <?php if (!empty($lowStockAlerts)): ?>
        <div id="low-stock-alert">
            <div class="alert-header">
                <span class="bell-icon">🔔</span>
                Low Stock Alert —
                <?= count($lowStockAlerts) ?> ingredient<?= count($lowStockAlerts) > 1 ? 's' : '' ?>
                need<?= count($lowStockAlerts) === 1 ? 's' : '' ?> restocking
            </div>
            <div class="alert-items">
                <?php foreach ($lowStockAlerts as $alert): ?>
                    <span class="alert-tag"
                          title="Stock: <?= intval($alert['stock']) ?> / Threshold: <?= intval($alert['low_stock_threshold']) ?>">
                        🧪 <?= htmlspecialchars($alert['label']) ?> (<?= intval($alert['stock']) ?>)
                    </span>
                <?php endforeach; ?>
            </div>
            <div class="alert-count">
                <a href="inventory.php?stock_level=low">View all low stock →</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Search and filter bar -->
        <form method="GET" class="filter-bar">
            <input type="text" name="search"
                   value="<?= htmlspecialchars($search) ?>"
                   placeholder="🔍 Search ingredient...">

            <select name="category">
                <option value="">Select Category</option>
                <?php foreach ($unitOptions as $opt): ?>
                    <option value="<?= htmlspecialchars($opt) ?>"
                        <?= $category === $opt ? 'selected' : '' ?>>
                        <?= htmlspecialchars($opt) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="stock_level">
                <option value="">Select Stock Level</option>
                <option value="low"  <?= $stockLevel === 'low'  ? 'selected' : '' ?>>🔴 Low</option>
                <option value="mid"  <?= $stockLevel === 'mid'  ? 'selected' : '' ?>>🟠 Mid</option>
                <option value="high" <?= $stockLevel === 'high' ? 'selected' : '' ?>>🟢 High</option>
            </select>

            <button type="submit" class="filter-search-btn">Search</button>
            <a href="inventory.php" class="filter-clear-btn">Clear</a>
        </form>

        <h2>Ingredients Inventory</h2>

        <!-- Add ingredient form -->
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <input type="text"   name="ingredient_name"    placeholder="Name"          required>
            <input type="number" name="stock"               placeholder="Stock"   min="0" required>
            <input type="number" name="low_stock_threshold" placeholder="Low Threshold" min="0" required>

            <select name="unit">
                <?php foreach ($unitOptions as $opt): ?>
                    <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                <?php endforeach; ?>
            </select>

            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
            <button name="add_ingredient">Add</button>
        </form>

        <!-- Edit ingredient form (shown only when edit is triggered) -->
        <?php if ($editIng): ?>
        <h3>Edit Ingredient</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="id" value="<?= intval($editIng['ingredient_id']) ?>">

            <input type="text"   name="ingredient_name"    value="<?= htmlspecialchars($editIng['ingredient_name']) ?>" required>
            <input type="number" name="stock"               value="<?= intval($editIng['stock']) ?>"               min="0" required>
            <input type="number" name="low_stock_threshold" value="<?= intval($editIng['low_stock_threshold']) ?>" min="0" required>

            <select name="unit">
                <?php foreach ($unitOptions as $opt): ?>
                    <option value="<?= htmlspecialchars($opt) ?>"
                        <?= $editIng['unit'] === $opt ? 'selected' : '' ?>>
                        <?= htmlspecialchars($opt) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
            <button name="update_ing">Save</button>
            <a href="inventory.php"><button type="button">Cancel</button></a>
        </form>
        <?php endif; ?>

        <!-- Ingredient cards list -->
        <div class="ingredient-grid">
            <?php while ($row = $ingredients->fetch_assoc()):

                // Determine stock level class and icon
                if ($row['stock'] <= $row['low_stock_threshold']) {
                    $cls      = 'low';
                    $barClass = 'bar-low';
                    $icon     = '⚠️';
                } elseif ($row['stock'] <= $row['low_stock_threshold'] * 3) {
                    $cls      = 'mid';
                    $barClass = 'bar-mid';
                    $icon     = '⚠️';
                } else {
                    $cls      = 'high';
                    $barClass = 'bar-high';
                    $icon     = '✅';
                }

                // Calculate stock bar fill percentage
                $maxRef = max($row['low_stock_threshold'] * 3, 1);
                $pct    = min(100, round(($row['stock'] / $maxRef) * 100));
            ?>

            <div class="ingredient-card">

                <!-- Ingredient image or placeholder -->
                <?php if (!empty($row['image'])): ?>
                    <img src="../../resources/images/uploads/<?= htmlspecialchars($row['image']) ?>"
                         alt="<?= htmlspecialchars($row['ingredient_name']) ?>">
                <?php else: ?>
                    <div class="img-placeholder">📦</div>
                <?php endif; ?>

                <div class="card-info">
                    <div class="card-name"><?= htmlspecialchars($row['ingredient_name']) ?></div>
                    <div class="card-stock-label">Stock Level:</div>
                    <div class="stock-bar-wrap">
                        <div class="stock-bar-fill <?= $barClass ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                    <div class="card-stock-count <?= $cls ?>">
                        <?= $icon ?> <?= intval($row['stock']) ?> LEFT
                    </div>
                </div>

                <div class="card-right">
                    <div class="card-limit">Limit: <?= intval($row['low_stock_threshold']) ?></div>
                    <div class="card-unit"><?= htmlspecialchars($row['unit']) ?></div>
                    <div class="card-actions">

                        <!-- Edit button -->
                        <form method="POST" class="form-inline">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <input type="hidden" name="id" value="<?= intval($row['ingredient_id']) ?>">
                            <button name="edit_ing" class="btn-edit">Edit</button>
                        </form>

                        <!-- Delete button with confirmation -->
                        <form method="POST" class="form-inline"
                              onsubmit="return confirm('Delete ingredient?')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <input type="hidden" name="id" value="<?= intval($row['ingredient_id']) ?>">
                            <button name="delete_ing" class="btn-delete">Delete</button>
                        </form>

                    </div>
                </div>

            </div>
            <?php endwhile; ?>
        </div>

    </div>
</div>

<!-- Toast notifications -->
<?php $hasNotifs = ($notif !== null) || !empty($lowStockAlerts); ?>
<?php if ($hasNotifs): ?>
<div id="notif-container">

    <!-- Action notification (added, updated, deleted) -->
    <?php if ($notif !== null): ?>
        <?php if ($notif['type'] === 'deleted'): ?>
        <div class="notif-card notif-deleted">
            <span class="notif-icon">ℹ️</span>
            <div class="notif-body">
                <div class="notif-title">Deleted Successfully</div>
                <div class="notif-msg">
                    <?= htmlspecialchars($notif['name']) ?> has been successfully removed.
                </div>
            </div>
            <a href="inventory.php" class="notif-close-link">✕</a>
        </div>

        <?php elseif ($notif['type'] === 'added'): ?>
        <div class="notif-card notif-added">
            <span class="notif-icon">ℹ️</span>
            <div class="notif-body">
                <div class="notif-title">Added Successfully</div>
                <div class="notif-msg">
                    <?= htmlspecialchars($notif['name']) ?> has been successfully added.
                </div>
            </div>
            <a href="inventory.php" class="notif-close-link">✕</a>
        </div>

        <?php elseif ($notif['type'] === 'updated'): ?>
        <div class="notif-card notif-updated">
            <span class="notif-icon">ℹ️</span>
            <div class="notif-body">
                <div class="notif-title">Updated Successfully</div>
                <div class="notif-msg">
                    <?= htmlspecialchars($notif['name']) ?> has been successfully updated.
                </div>
            </div>
            <a href="inventory.php" class="notif-close-link">✕</a>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Low stock notifications per ingredient -->
    <?php foreach ($lowStockAlerts as $alert): ?>
    <div class="notif-card notif-lowstock">
        <span class="notif-icon">ℹ️</span>
        <div class="notif-body">
            <div class="notif-title">Low Stock Alert!</div>
            <div class="notif-msg">
                <?= htmlspecialchars($alert['label']) ?> is running low.
                Only <strong><?= intval($alert['stock']) ?> units remaining.</strong>
            </div>
        </div>
        <a href="inventory.php?stock_level=low" class="notif-close-link">✕</a>
    </div>
    <?php endforeach; ?>

</div>
<?php endif; ?>
        <script src="../../resources/js/partials/sidebar.js" type="text/javascript" defer></script>
        <script src="../../resources/js/partials/menu_mobile.js" type="text/javascript" defer></script>
</body>
</html>