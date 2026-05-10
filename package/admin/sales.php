<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit();
}

$username = $_SESSION['user']['username'];

$conn = new mysqli("localhost", "root", "", "login");
if ($conn->connect_error) die("DB Error");



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['add_sale'])) {
        $product_id      = intval($_POST['product_id']);
        $qty             = intval($_POST['quantity']);
        $selected_addons = $_POST['addon_ids'] ?? [];

        if ($product_id <= 0 || $qty <= 0) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid product or quantity.'];
            header("Location: sales.php"); exit();
        }

        // Fetch product details
        $stmt = $conn->prepare("SELECT product_name, price, image FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->bind_result($product_name, $price, $image);
        $stmt->fetch();
        $stmt->close();

        if (!$product_name) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Product not found.'];
            header("Location: sales.php"); exit();
        }

        // Fetch selected add-ons and compute add-on total
        $addon_total = 0;
        $addons_data = [];
        if (!empty($selected_addons)) {
            $ids = implode(',', array_map('intval', $selected_addons));
            $res = $conn->query("SELECT addon_id, addon_name, price FROM addons WHERE addon_id IN ($ids) AND status='Available'");
            while ($a = $res->fetch_assoc()) {
                $addon_total += $a['price'];
                $addons_data[] = $a;
            }
        }

        // Compute total: (base price + addons) * quantity
        $total = ($qty * $price) + ($addon_total * $qty);

        // Insert sale record
        $stmt = $conn->prepare(
            "INSERT INTO sales (product_id, product_name, product_image, quantity, total, status)
             VALUES (?, ?, ?, ?, ?, 'Completed')"
        );
        $stmt->bind_param("issid", $product_id, $product_name, $image, $qty, $total);
        $stmt->execute();
        $sale_id = $stmt->insert_id;
        $stmt->close();

        // Insert add-ons for this sale if any were selected
        if (!empty($addons_data)) {
            $stmt = $conn->prepare(
                "INSERT INTO sale_addons (sale_id, addon_id, addon_name, price) VALUES (?, ?, ?, ?)"
            );
            foreach ($addons_data as $a) {
                $stmt->bind_param("iisd", $sale_id, $a['addon_id'], $a['addon_name'], $a['price']);
                $stmt->execute();
            }
            $stmt->close();
        }

        $_SESSION['flash'] = ['type' => 'success', 'msg' => "Sale recorded: {$product_name} x {$qty}"];
        header("Location: sales.php"); exit();
    }

    // Cancel sale (sets status to Cancelled, only if currently Completed)
    if (isset($_POST['cancel_sale'])) {
        $id = intval($_POST['id']);

        $stmt = $conn->prepare("SELECT sales_id FROM sales WHERE sales_id = ? AND status = 'Completed'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($found_id);
        $found = $stmt->fetch();
        $stmt->close();

        if ($found) {
            $stmt = $conn->prepare("UPDATE sales SET status = 'Cancelled' WHERE sales_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Order cancelled.'];
        }

        header("Location: sales.php"); exit();
    }

    if (isset($_POST['void_sale'])) {
        $id     = intval($_POST['id']);
        $reason = trim($_POST['reason'] ?? '');

        if ($id <= 0) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid sale.'];
            header("Location: sales.php"); exit();
        }

        $stmt = $conn->prepare("UPDATE sales SET status = 'VOID' WHERE sales_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO sales_void (sale_id, reason) VALUES (?, ?)");
        $stmt->bind_param("is", $id, $reason);
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Sale voided.'];
        header("Location: sales.php"); exit();
    }

    // Reset all sales history
    if (isset($_POST['reset_sales'])) {
        $conn->query("DELETE FROM sale_addons");
        $conn->query("DELETE FROM sales_void");
        $conn->query("DELETE FROM sales");
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'All sales history reset.'];
        header("Location: sales.php"); exit();
    }
}


$search = $_GET['search'] ?? '';
$date   = $_GET['date']   ?? '';
$where  = "1=1";

if (!empty($search)) {
    $safe   = $conn->real_escape_string($search);
    $where .= " AND s.product_name LIKE '%$safe%'";
}
if (!empty($date)) {
    $safe   = $conn->real_escape_string($date);
    $where .= " AND DATE(s.created_at) = '$safe'";
}

// Fetch sales with product category
$sales = $conn->query("
    SELECT s.*, p.category AS product_category
    FROM sales s
    LEFT JOIN products p ON p.product_id = s.product_id
    WHERE $where
    ORDER BY s.created_at DESC
");

$all_addons = [];
$res = $conn->query("SELECT sale_id, addon_name, price FROM sale_addons ORDER BY sale_id");
while ($row = $res->fetch_assoc()) {
    $all_addons[$row['sale_id']][] = $row;
}

// Summary totals (Completed sales)
$totalSales = $conn->query("
    SELECT SUM(total) as total FROM sales WHERE status = 'Completed'
")->fetch_assoc()['total'] ?? 0;

$totalTransactions = $conn->query("
    SELECT COUNT(*) as c FROM sales WHERE status = 'Completed'
")->fetch_assoc()['c'] ?? 0;

$products = $conn->query(
    "SELECT product_id, product_name, category, price
     FROM products WHERE status = 'Available' ORDER BY category, product_name"
);

$addons_res  = $conn->query(
    "SELECT addon_id, addon_name, price FROM addons WHERE status = 'Available' ORDER BY addon_name"
);
$addons_list = [];
while ($a = $addons_res->fetch_assoc()) $addons_list[] = $a;

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <!-- SEO -->
    <meta name="author" content="Al Coffee">
     <meta name="description" content="Al Coffee Sales Management - Track transactions, monitor income for efficient sales operations.">
     <!-- SEO -->

    <link rel="stylesheet" href="../../resources/css/general.css">
    <link rel="stylesheet" href="../../resources/css/design_tokens/primitives.css">
    <link rel="stylesheet" href="../../resources/css/design_tokens/mapping.css">
    <link rel="stylesheet" href="../../resources/css/partials/header.css">
    <link rel="stylesheet" href="../../resources/css/partials/side_nav.css">
    <link rel="stylesheet" href="../../resources/css/main_interface.css">
     <link rel="stylesheet" href="../../resources/css/sales.css">

    <link rel="icon" type="image/svg+xml" href="../../resources/images/logo/logo_black.svg">
    
    <title>Sales Management - Al Coffee</title>    
</head>
<body>

<div class="container">
    
    <?php include '../partials/header.php'; ?>
    <?php require '../partials/sidenav.php'; ?>

    <div class="main">

        <h1>Sales Management</h1>

        <!-- STATISTICS CONTAINER -->
        <div class="statistics-container statistics-container--grid">
            <div class="statistics_card statistics_card--flex">
                <div class="stat_card-label">Total Sales</div>
                <div class="stat-value">&#8369;<?= number_format($totalSales, 2) ?></div>
            </div>
            <div class="statistics_card statistics_card--flex">
                <div class="stat_card-label">Transactions</div>
                <div class="stat-value"><?= $totalTransactions ?></div>
            </div>
        </div>

        <!-- New sale entry form -->
        <div class="sale-form">
            <h3>New Sale Entry</h3>
            <form method="POST">
                <table class="entry-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Add-ons <span class="hint">(optional)</span></th>
                            <th>Quantity</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>

                            <!-- Product dropdown -->
                            <td class="entry-cell">
                                <select name="product_id" required>
                                    <option value="">Select Product</option>
                                    <?php
                                    $currentCategory = '';
                                    while ($p = $products->fetch_assoc()):
                                        if ($p['category'] !== $currentCategory):
                                            if ($currentCategory !== '') echo '</optgroup>';
                                            echo '<optgroup label="' . htmlspecialchars($p['category']) . '">';
                                            $currentCategory = $p['category'];
                                        endif;
                                    ?>
                                        <option value="<?= $p['product_id'] ?>">
                                            <?= htmlspecialchars($p['product_name']) ?> — &#8369;<?= number_format($p['price'], 2) ?>
                                        </option>
                                    <?php endwhile; if ($currentCategory !== '') echo '</optgroup>'; ?>
                                </select>
                            </td>

                            <!-- Add-on checkboxes -->
                            <td class="entry-cell">
                                <?php if (!empty($addons_list)): ?>
                                    <div class="addon-list">
                                        <?php foreach ($addons_list as $a): ?>
                                            <div class="addon-chip">
                                                <input type="checkbox"
                                                       name="addon_ids[]"
                                                       value="<?= $a['addon_id'] ?>"
                                                       id="addon_<?= $a['addon_id'] ?>">
                                                <label for="addon_<?= $a['addon_id'] ?>">
                                                    <?= htmlspecialchars($a['addon_name']) ?>
                                                    <span class="addon-price">+&#8369;<?= number_format($a['price'], 2) ?></span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="no-data">No add-ons available</span>
                                <?php endif; ?>
                            </td>
                            <td class="entry-cell">
                                <input type="number" name="quantity" min="1" value="1" required>
                            </td>
                            <td class="entry-cell">
                                <button type="submit" name="add_sale" class="add-btn">Add Sale</button>
                            </td>

                        </tr>
                    </tbody>
                </table>
            </form>
        </div>

        <!-- Sales list -->
        <h3>Sales List</h3>
        
          <!-- Reset all sales history -->
        <form method="POST" onsubmit="return confirm('Reset all sales history? This cannot be undone.');">
            <button type="submit" name="reset_sales" class="reset-btn">Reset Sales History</button>
        </form>

        <table class="sales-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th style="text-align: left; padding-left: 16px;">Product</th>
                    <th>Add-ons</th>
                    <th>Unit Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $sales->fetch_assoc()):
                $status     = strtolower($row['status']);
                $badgeClass = match($status) {
                    'completed'             => 'badge-completed',
                    'void'                  => 'badge-void',
                    'cancelled', 'canceled' => 'badge-cancelled',
                    default                 => 'badge-pending',
                };
                $imageFile   = $row['product_image'] ?? '';
                $sale_addons = $all_addons[$row['sales_id']] ?? [];
            ?>
                <tr>
                    <td><?= $row['sales_id'] ?></td>

                    <!-- Product name and image -->
                    <td>
                        <div class="product-cell">
                            <?php if (!empty($imageFile)): ?>
                                <img src="../../resources/images/uploads/<?= htmlspecialchars($imageFile) ?>"
                                     alt="<?= htmlspecialchars($row['product_name']) ?>"
                                     class="product-thumb">
                            <?php else: ?>
                                <div class="product-thumb-placeholder">&#9749;</div>
                            <?php endif; ?>
                            <div>
                                <div class="product-name"><?= htmlspecialchars($row['product_name']) ?></div>
                                <?php if (!empty($row['product_category'])): ?>
                                    <div class="product-category"><?= htmlspecialchars($row['product_category']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>

                    <!-- Add-ons for this sale -->
                    <td>
                        <?php if (!empty($sale_addons)): ?>
                            <?php foreach ($sale_addons as $sa): ?>
                                <span class="addon-tag">
                                    <?= htmlspecialchars($sa['addon_name']) ?> +&#8369;<?= number_format($sa['price'], 2) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="no-data">—</span>
                        <?php endif; ?>
                    </td>

                    <!-- Unit pricing-->
                    <td>&#8369;<?= number_format($row['total'] / max($row['quantity'], 1), 2) ?></td>

                    <!-- Quantity -->
                    <td><?= $row['quantity'] ?></td>

                    <!-- Total -->
                    <td>&#8369;<?= number_format($row['total'], 2) ?></td>

                    <!-- Date -->
                    <td><?= $row['created_at'] ?></td>

                    <td>
                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($row['status']) ?></span>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'Completed'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?= $row['sales_id'] ?>">
                                <button type="submit" name="cancel_sale" class="cancel-btn">Cancel</button>
                            </form>
                        <?php else: ?>
                            <span class="no-data">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

    </div>
</div>
        <script src="../../resources/js/partials/sidebar.js" type="text/javascript" defer></script>
        <script src="../../resources/js/partials/menu_mobile.js" type="text/javascript" defer></script>
</body>
</html>