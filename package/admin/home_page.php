<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user'])) {
    header("Location: ../../index.php");
    exit();
}

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

$today_orders = $conn->query("
    SELECT COUNT(*) as c
    FROM sales
    WHERE created_at >= CURDATE()
      AND created_at < CURDATE() + INTERVAL 1 DAY
")->fetch_assoc()['c'] ?? 0;

// Monthly revenue estimate (10% above current month)
$forecast = $monthly * 1.1;

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
     <meta name="description" content="Al Coffee System Dashboard & Inventory - Monitor daily sales, track inventory levels, and analyze top-selling products. Access real-time insights to optimize coffee business operations.">
     <!-- SEO -->

    <link rel="stylesheet" href="../../resources/css/general.css">
    <link rel="stylesheet" href="../../resources/css/design_tokens/primitives.css">
    <link rel="stylesheet" href="../../resources/css/design_tokens/mapping.css">
    <link rel="stylesheet" href="../../resources/css/partials/header.css">
    <link rel="stylesheet" href="../../resources/css/partials/side_nav.css">
     <link rel="stylesheet" href="../../resources/css/main_interface.css">
     <link rel="stylesheet" href="../../resources/css/dashboard.css">

    <link rel="icon" type="image/svg+xml" href="../../resources/images/logo/logo_black.svg">
    
    <title>Dashboard - Al Coffee</title>
</head>
<body>

<div class="container">
    <?php include '../partials/header.php'; ?>
    <?php require '../partials/sidenav.php'; ?>
    
    <div class="main">

        <div class="hero hero--flex">
            <div class="hero-label">Today's Sales</div>
            <div class="hero-amount">
                <p>₱<?= number_format($daily, 0) ?></p></div>
        </div>

        <div class="statistics-container statistics-container--grid">
            <div class="statistics_card statistics_card--flex">
                <div class="stat_card-label">Weekly Sales</div>
                <div class="stat-value">₱<?= number_format($weekly, 0) ?></div>
            </div>
            <div class="statistics_card statistics_card--flex">
                <div class="stat_card-label">Monthly Sales</div>
                <div class="stat-value">₱<?= number_format($monthly, 0) ?></div>
            </div>
            <div class="statistics_card statistics_card--flex">
                <div class="stat_card-label">Today's Orders</div>
                <div class="stat-value"><?= $today_orders ?></div>
            </div>
            <div class="statistics_card statistics_card--flex">
                <div class="stat_card-label">Monthly Estimate</div>
                <div class="stat-value">₱<?= number_format($forecast, 0) ?></div>
            </div>
        </div>

        <div class="table_container table_container--flex">
        <!-- Inventory status table -->
        <div class="section-title section-title--flex">
            <p>Inventory Status</p>
            <a href="inventory.php">Manage All</a>
        </div>

        <table>
            <tr>
                <th>Ingredient</th>
                <th>Unit</th>
                <th>Stock</th>
                <th>Low Stock</th>
                <th>Status</th>
            </tr>
            <?php while ($i = $ingredients->fetch_assoc()): ?>
                <?php
                $thr = $i['low_stock_threshold'] ?? 5;
                if ($i['stock'] <= $thr) {
                    $sc = "stock-bad";   
                    $label = "Low";
                } 
                elseif ($i['stock'] <= $thr * 3) {
                    $sc = "stock-mid";  
                    $label = "Normal";
                } else {
                    $sc = "stock-good"; 
                    $label = "High";
                }
                ?>
                <tr>
                    <td class="info-image_column info-image-column--flex">
                        <?php if (!empty($i['image'])): ?>
                            <img src="../../resources/images/uploads/<?= htmlspecialchars($i['image']) ?>"
                            class="prod-img-thumb">
                        <?php endif; ?>
                        <?= htmlspecialchars($i['ingredient_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($i['unit'] ?? '') ?></td>
                    <td><?= $i['stock'] ?></td>
                    <td><?= $thr ?></td>
                    <td class="info-status_column"><span class="<?= $sc ?>"> <?= $label ?></span></td>
                </tr>
            <?php endwhile; ?>
        </table>
        </div>
        <!-- Table Container -->

        <div class="table_container table_container--flex">
        <div class="section-title section-title--flex">
            <p>Top Selling Products</p></div>

        <!-- Daily top sellers -->
         <div class="period_wrapper period_wrapper--flex">
         <div class="period_container period-container--flex">
        <div class="period-heading">
            <p>Daily<p></div>
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
                    <td class="info-image_column info-image-column--flex">
                        <img src="../../resources/images/uploads/<?= htmlspecialchars($b['image']) ?>"
                             class="prod-img-thumb">
                        &nbsp;<?= htmlspecialchars($b['product_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($b['category'] ?? '—') ?></td>
                    <td>₱<?= number_format($b['price'] ?? 0, 0) ?></td>
                    <td><?= $b['qty'] ?></td>
                    <td class="product_revenue">₱<?= number_format($b['revenue'], 0) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
        </div>

        <!-- Weekly top sellers -->
            <div class="period_container period-container--flex">
        <div class="period-heading">
            <p>Weekly</p></div>
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
                    <td class="info-image_column info-image-column--flex">
                        <img src="../../resources/images/uploads/<?= htmlspecialchars($b['image']) ?>"
                             class="prod-img-thumb">
                        &nbsp;<?= htmlspecialchars($b['product_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($b['category'] ?? '—') ?></td>
                    <td>₱<?= number_format($b['price'] ?? 0, 0) ?></td>
                    <td><?= $b['qty'] ?></td>
                    <td class="product_revenue">₱<?= number_format($b['revenue'], 0) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
            </div>

        <!-- Monthly top sellers -->
            <div class="period_container period-container--flex">
        <div class="period-heading">
            <p>Monthly</p></div>
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
                    <td class="info-image_column info-image-column--flex">
                        <img src="../../resources/images/uploads/<?= htmlspecialchars($b['image']) ?>"
                             class="prod-img-thumb">
                        &nbsp;<?= htmlspecialchars($b['product_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($b['category'] ?? '—') ?></td>
                    <td>₱<?= number_format($b['price'] ?? 0, 0) ?></td>
                    <td><?= $b['qty'] ?></td>
                    <td class="product_revenue">₱<?= number_format($b['revenue'], 0) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
        </div>
        <!-- Table Container -->
        </div>
            </div>
            <!-- Period Wrapper Flex -->

     </div>
</div>
<!-- Container Main -->
        
<script src="../../resources/js/partials/sidebar.js" type="text/javascript" defer></script>
<script src="../../resources/js/partials/menu_mobile.js" type="text/javascript" defer></script>
</body>
</html>
