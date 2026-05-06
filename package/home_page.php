<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "login");
if ($conn->connect_error) die("DB Error");

$username = $_SESSION['user']['username'];

// Daily sales total
$daily = $conn->query("
    SELECT SUM(total) as total
    FROM sales
    WHERE created_at >= CURDATE()
      AND created_at < CURDATE() + INTERVAL 1 DAY
")->fetch_assoc()['total'] ?? 0;

// Weekly sales total (last 7 days including today)
$weekly = $conn->query("
    SELECT SUM(total) as total
    FROM sales
    WHERE created_at >= CURDATE() - INTERVAL 6 DAY
      AND created_at < CURDATE() + INTERVAL 1 DAY
")->fetch_assoc()['total'] ?? 0;

// Monthly sales total (current month)
$monthly = $conn->query("
    SELECT SUM(total) as total
    FROM sales
    WHERE MONTH(created_at) = MONTH(CURDATE())
      AND YEAR(created_at) = YEAR(CURDATE())
")->fetch_assoc()['total'] ?? 0;

// Number of orders placed today
$today_orders = $conn->query("
    SELECT COUNT(*) as c
    FROM sales
    WHERE created_at >= CURDATE()
      AND created_at < CURDATE() + INTERVAL 1 DAY
")->fetch_assoc()['c'] ?? 0;

// Monthly revenue estimate (10% above current month)
$forecast = $monthly * 1.1;

// All ingredients ordered by stock level (ascending)
$ingredients = $conn->query("SELECT * FROM ingredients ORDER BY stock ASC");

// Top selling products today (products with images only)
$best_daily = $conn->query("
    SELECT s.product_id, s.product_name, SUM(s.quantity) as qty, SUM(s.total) as revenue,
           p.image, p.price, p.category
    FROM sales s
    LEFT JOIN products p ON p.product_id = s.product_id
    WHERE s.created_at >= CURDATE()
      AND s.created_at < CURDATE() + INTERVAL 1 DAY
      AND (p.image IS NOT NULL AND p.image != '')
    GROUP BY s.product_id, s.product_name, p.image, p.price, p.category
    ORDER BY qty DESC
    LIMIT 10
");

// Top selling products this week
$best_weekly = $conn->query("
    SELECT s.product_id, s.product_name, SUM(s.quantity) as qty, SUM(s.total) as revenue,
           p.image, p.price, p.category
    FROM sales s
    LEFT JOIN products p ON p.product_id = s.product_id
    WHERE s.created_at >= CURDATE() - INTERVAL 6 DAY
      AND s.created_at < CURDATE() + INTERVAL 1 DAY
      AND (p.image IS NOT NULL AND p.image != '')
    GROUP BY s.product_id, s.product_name, p.image, p.price, p.category
    ORDER BY qty DESC
    LIMIT 10
");

// Top selling products this month
$best_monthly = $conn->query("
    SELECT s.product_id, s.product_name, SUM(s.quantity) as qty, SUM(s.total) as revenue,
           p.image, p.price, p.category
    FROM sales s
    LEFT JOIN products p ON p.product_id = s.product_id
    WHERE MONTH(s.created_at) = MONTH(CURDATE())
      AND YEAR(s.created_at)  = YEAR(CURDATE())
      AND (p.image IS NOT NULL AND p.image != '')
    GROUP BY s.product_id, s.product_name, p.image, p.price, p.category
    ORDER BY qty DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <!-- SEO -->
    <meta name="author" content="Al Coffee">
     <meta name="description" content="Al Coffee System Dashboard & Inventory - Monitor daily sales, track inventory levels, and analyze top-selling products. Access real-time insights to optimize your coffee business operations.">
     <!-- SEO -->

    <link rel="stylesheet" href="../resources/css/general.css">
    <link rel="stylesheet" href="../resources/css/design_tokens/primitives.css">
    <link rel="stylesheet" href="../resources/css/design_tokens/mapping.css">
    <link rel="stylesheet" href="../resources/css/partials/header.css">
<link rel="stylesheet" href="../resources/css/partials/side_nav.css">
     <link rel="stylesheet" href="../resources/css/dashboard.css">

    <link rel="icon" type="image/svg+xml" href="../resources/images/logo/logo_black.svg">
    
    <title>Dashboard - Al Coffee</title>
</head>
<body>

<header class="header--flex">
    <div class="header_left-container header_left-container--flex">
        
            <img class="burger_icon_mobile" src="../resources/images/icons/Menu/Hamburger_LG.svg" alt="Menu">

    </div>

    <div class="header_right-container header_right-container--flex"> 
        <a href="admin/admin.php">
             <img src="../resources/images/logo/Logo_Black.svg" alt="Admin Menu" class="business_logo_mobile">
        </a>
    </div>
</header>

<div class="container">
    <?php require 'partials/header.php'; ?>
    <?php require 'partials/sidenav.php'; ?>
    <!-- Main content -->
    <div class="main">

        <!-- Today's sales hero -->
        <div class="hero">
            <div class="hero-label">Today's Sales</div>
            <div class="hero-amount">₱<?= number_format($daily, 0) ?></div>
        </div>

        <!-- Summary stats -->
        <div class="stat-strip">
            <div class="stat-item">
                <div class="s-label">This Week's Sales</div>
                <div class="s-value">₱<?= number_format($weekly, 0) ?></div>
            </div>
            <div class="stat-item">
                <div class="s-label">This Month's Sales</div>
                <div class="s-value">₱<?= number_format($monthly, 0) ?></div>
            </div>
            <div class="stat-item">
                <div class="s-label">Today's Orders</div>
                <div class="s-value"><?= $today_orders ?></div>
            </div>
            <div class="stat-item">
                <div class="s-label">Monthly Estimate</div>
                <div class="s-value">₱<?= number_format($forecast, 0) ?></div>
            </div>
        </div>

        <!-- Inventory status table -->
        <div class="section-title">
            Inventory Status
            <a href="admin/inventory.php">Manage All</a>
        </div>

        <table>
            <tr>
                <th>Ingredient</th>
                <th>Unit</th>
                <th>Stock</th>
                <th>Limit</th>
                <th>Status</th>
            </tr>
            <?php while ($i = $ingredients->fetch_assoc()): ?>
                <?php
                // Determine stock status based on threshold
                $thr = $i['low_stock_threshold'] ?? 5;
                if ($i['stock'] <= $thr) {
                    $sc = "stock-bad";  $icon = "⚠️"; $label = "Low";
                } elseif ($i['stock'] <= $thr * 3) {
                    $sc = "stock-mid";  $icon = "⚠️"; $label = "Mid";
                } else {
                    $sc = "stock-good"; $icon = "✅"; $label = "Good";
                }
                ?>
                <tr>
                    <td style="text-align: left;">
                        <?php if (!empty($i['image'])): ?>
                            <img src="../resources/images/uploads/<?= htmlspecialchars($i['image']) ?>"
                                 style="width: 36px; height: 36px; object-fit: cover; border-radius: 4px; vertical-align: middle; margin-right: 8px;">
                        <?php endif; ?>
                        <?= htmlspecialchars($i['ingredient_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($i['unit'] ?? '') ?></td>
                    <td><?= $i['stock'] ?></td>
                    <td><?= $thr ?></td>
                    <td><span class="<?= $sc ?>"><?= $icon ?> <?= $label ?></span></td>
                </tr>
            <?php endwhile; ?>
        </table>

        <!-- Top selling products -->
        <div class="section-title" style="margin-top: 28px;">Top Selling Products</div>

        <!-- Daily top sellers -->
        <div class="period-heading">Daily</div>
        <table>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Sold</th>
                <th>Revenue</th>
            </tr>
            <?php while ($b = $best_daily->fetch_assoc()): ?>
                <tr>
                    <td style="text-align: left;">
                        <img src="../resources/images/uploads/<?= htmlspecialchars($b['image']) ?>"
                             class="prod-img-thumb">
                        &nbsp;<?= htmlspecialchars($b['product_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($b['category'] ?? '—') ?></td>
                    <td>₱<?= number_format($b['price'] ?? 0, 0) ?></td>
                    <td><?= $b['qty'] ?></td>
                    <td>₱<?= number_format($b['revenue'], 0) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>

        <!-- Weekly top sellers -->
        <div class="period-heading">Weekly</div>
        <table>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Sold</th>
                <th>Revenue</th>
            </tr>
            <?php while ($b = $best_weekly->fetch_assoc()): ?>
                <tr>
                    <td style="text-align: left;">
                        <img src="../resources/images/uploads/<?= htmlspecialchars($b['image']) ?>"
                             class="prod-img-thumb">
                        &nbsp;<?= htmlspecialchars($b['product_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($b['category'] ?? '—') ?></td>
                    <td>₱<?= number_format($b['price'] ?? 0, 0) ?></td>
                    <td><?= $b['qty'] ?></td>
                    <td>₱<?= number_format($b['revenue'], 0) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>

        <!-- Monthly top sellers -->
        <div class="period-heading">Monthly</div>
        <table>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Sold</th>
                <th>Revenue</th>
            </tr>
            <?php while ($b = $best_monthly->fetch_assoc()): ?>
                <tr>
                    <td style="text-align: left;">
                        <img src="../resources/images/uploads/<?= htmlspecialchars($b['image']) ?>"
                             class="prod-img-thumb">
                        &nbsp;<?= htmlspecialchars($b['product_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($b['category'] ?? '—') ?></td>
                    <td>₱<?= number_format($b['price'] ?? 0, 0) ?></td>
                    <td><?= $b['qty'] ?></td>
                    <td>₱<?= number_format($b['revenue'], 0) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>

    </div>
</div>
<!-- Container Main -->
        
<script src="../resources/js/partials/sidebar.js" type="text/javascript" defer></script>
<script src="../resources/js/partials/menu_mobile.js" type="text/javascript" defer></script>
</body>
</html>
