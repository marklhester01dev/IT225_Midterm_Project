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


$filter = $_GET['filter'] ?? 'daily';

if ($filter === 'daily') {
    $dateCondition = "DATE(created_at) = CURDATE()";
} elseif ($filter === 'weekly') {
    $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'monthly') {
    $dateCondition = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
} else {
    $dateCondition = "1=1";
}


// Summary totals based on selected filter
$salesData = $conn->query("
    SELECT SUM(total) as total_sales, COUNT(*) as total_transactions
    FROM sales WHERE status='Completed' AND $dateCondition
")->fetch_assoc();
$totalSales        = $salesData['total_sales']        ?? 0;
$totalTransactions = $salesData['total_transactions'] ?? 0;

// Fixed stat strip values
$weeklySales = $conn->query("
    SELECT SUM(total) as total FROM sales
    WHERE status='Completed'
    AND created_at >= CURDATE() - INTERVAL 6 DAY
    AND created_at < CURDATE() + INTERVAL 1 DAY
")->fetch_assoc()['total'] ?? 0;

$monthlySales = $conn->query("
    SELECT SUM(total) as total FROM sales
    WHERE status='Completed'
    AND MONTH(created_at) = MONTH(CURDATE())
    AND YEAR(created_at)  = YEAR(CURDATE())
")->fetch_assoc()['total'] ?? 0;

$todayOrders = $conn->query("
    SELECT COUNT(*) as c FROM sales
    WHERE status='Completed' AND DATE(created_at) = CURDATE()
")->fetch_assoc()['c'] ?? 0;

// Sales transaction rows for the current filter (used in Excel export)
$salesRows = [];
$res = $conn->query("
    SELECT product_name, quantity, total, created_at, status
    FROM sales WHERE status='Completed' AND $dateCondition
    ORDER BY created_at DESC
");
while ($row = $res->fetch_assoc()) $salesRows[] = $row;


// TOP SELLING PRODUCTS (daily / weekly / monthly)
$topDaily = [];
$res = $conn->query("
    SELECT s.product_id, s.product_name,
           SUM(s.quantity) as qty_sold, SUM(s.total) as revenue,
           p.image, p.price, p.category
    FROM sales s
    LEFT JOIN products p ON p.product_id = s.product_id
    WHERE s.status='Completed' AND DATE(s.created_at) = CURDATE()
    GROUP BY s.product_id, s.product_name, p.image, p.price, p.category
    ORDER BY qty_sold DESC LIMIT 10
");
while ($row = $res->fetch_assoc()) $topDaily[] = $row;

$topWeekly = [];
$res = $conn->query("
    SELECT s.product_id, s.product_name,
           SUM(s.quantity) as qty_sold, SUM(s.total) as revenue,
           p.image, p.price, p.category
    FROM sales s
    LEFT JOIN products p ON p.product_id = s.product_id
    WHERE s.status='Completed'
    AND s.created_at >= CURDATE() - INTERVAL 6 DAY
    AND s.created_at < CURDATE() + INTERVAL 1 DAY
    GROUP BY s.product_id, s.product_name, p.image, p.price, p.category
    ORDER BY qty_sold DESC LIMIT 10
");
while ($row = $res->fetch_assoc()) $topWeekly[] = $row;

$topMonthly = [];
$res = $conn->query("
    SELECT s.product_id, s.product_name,
           SUM(s.quantity) as qty_sold, SUM(s.total) as revenue,
           p.image, p.price, p.category
    FROM sales s
    LEFT JOIN products p ON p.product_id = s.product_id
    WHERE s.status='Completed'
    AND MONTH(s.created_at) = MONTH(CURDATE())
    AND YEAR(s.created_at)  = YEAR(CURDATE())
    GROUP BY s.product_id, s.product_name, p.image, p.price, p.category
    ORDER BY qty_sold DESC LIMIT 10
");
while ($row = $res->fetch_assoc()) $topMonthly[] = $row;


// BEST SELLING CATEGORY
$categoryRows = [];
$res = $conn->query("
    SELECT p.category, SUM(s.quantity) as total_quantity, SUM(s.total) as total_sales
    FROM sales s
    JOIN products p ON s.product_id = p.product_id
    WHERE s.status='Completed'
    GROUP BY p.category
    ORDER BY total_sales DESC
");
while ($row = $res->fetch_assoc()) $categoryRows[] = $row;


// INVENTORY STATUS
$allIngredients = [];
$res = $conn->query("SELECT * FROM ingredients ORDER BY stock ASC");
while ($row = $res->fetch_assoc()) $allIngredients[] = $row;

// Build unit category list for filter dropdown
$allUnitCats = [];
foreach ($allIngredients as $i) {
    $unit = ucfirst($i['unit'] ?? 'Other') . '-based';
    if (!in_array($unit, $allUnitCats)) $allUnitCats[] = $unit;
}

$invStatus   = $_GET['inv_status']   ?? '';
$invCategory = $_GET['inv_category'] ?? '';

$filteredIngredients = [];
foreach ($allIngredients as $i) {
    $thr = $i['low_stock_threshold'] ?? 5;

    if ($i['stock'] <= $thr)         $cls = 'badge-bad';
    elseif ($i['stock'] <= $thr * 3) $cls = 'badge-mid';
    else                             $cls = 'badge-good';

    $unitLabel = ucfirst($i['unit'] ?? 'Other') . '-based';

    if ($invStatus   !== '' && $cls       !== $invStatus)   continue;
    if ($invCategory !== '' && $unitLabel !== $invCategory) continue;

    $i['_cls']       = $cls;
    $i['_unitLabel'] = $unitLabel;
    $filteredIngredients[] = $i;
}


// EXCEL EXPORT
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filterLabel   = ucfirst($filter);
    $dateGenerated = date('Y-m-d H:i:s');

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="Report_' . $filterLabel . '_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office"
               xmlns:x="urn:schemas-microsoft-com:office:excel"
               xmlns="http://www.w3.org/TR/REC-html40">
    <head><meta charset="UTF-8"></head><body>';
    echo '<table border="1" style="border-collapse:collapse;font-family:Arial;font-size:13px;">';
    echo '<tr><td colspan="5" style="background:#222;color:white;font-size:15px;font-weight:bold;padding:10px;">Al Coffee\'s Sales and Inventory Management System</td></tr>';
    echo '<tr><td colspan="5" style="padding:6px;">Report Period: <b>' . $filterLabel . '</b> &nbsp; Generated: ' . $dateGenerated . '</td></tr>';
    echo '<tr><td colspan="5"></td></tr>';

    echo '<tr><td colspan="5" style="background:#444;color:white;font-weight:bold;padding:8px;">SALES SUMMARY</td></tr>';
    echo '<tr><td style="background:#ddd;font-weight:bold;">Total Sales</td><td style="background:#ddd;font-weight:bold;">Total Transactions</td><td colspan="3"></td></tr>';
    echo '<tr><td>&#8369;' . number_format($totalSales, 2) . '</td><td>' . $totalTransactions . '</td><td colspan="3"></td></tr>';
    echo '<tr><td colspan="5"></td></tr>';

    echo '<tr><td colspan="5" style="background:#444;color:white;font-weight:bold;padding:8px;">SALES TRANSACTIONS (' . $filterLabel . ')</td></tr>';
    echo '<tr><td style="background:#ddd;font-weight:bold;">Product</td><td style="background:#ddd;font-weight:bold;">Qty</td><td style="background:#ddd;font-weight:bold;">Total</td><td style="background:#ddd;font-weight:bold;">Date</td><td style="background:#ddd;font-weight:bold;">Status</td></tr>';
    if (empty($salesRows)) {
        echo '<tr><td colspan="5" style="color:gray;">No transactions for this period.</td></tr>';
    } else {
        foreach ($salesRows as $row) {
            echo '<tr>'
               . '<td>' . htmlspecialchars($row['product_name']) . '</td>'
               . '<td>' . $row['quantity'] . '</td>'
               . '<td>&#8369;' . number_format($row['total'], 2) . '</td>'
               . '<td>' . $row['created_at'] . '</td>'
               . '<td>' . $row['status'] . '</td>'
               . '</tr>';
        }
    }
    echo '</table></body></html>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
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
     <link rel="stylesheet" href="../../resources/css/reports.css">

    <link rel="icon" type="image/svg+xml" href="../../resources/images/logo/logo_black.svg">
     
<title>Reports & Analysis — Al Coffee</title>
</head>
<body>

<div class="title-bar">
    <div class="filter-links">
        <a href="?filter=daily"    class="<?= $filter === 'daily'   ? 'active-filter' : '' ?>">Daily</a>
        <a href="?filter=weekly"   class="<?= $filter === 'weekly'  ? 'active-filter' : '' ?>">Weekly</a>
        <a href="?filter=monthly"  class="<?= $filter === 'monthly' ? 'active-filter' : '' ?>">Monthly</a>
        <a href="reports_analysis.php" class="<?= !in_array($filter, ['daily','weekly','monthly']) ? 'active-filter' : '' ?>">All</a>
        <a href="?filter=<?= $filter ?>&export=excel" class="btn-excel">&#11015; Excel</a>
    </div>
</div>

<div class="container">
    
    <?php include '../partials/header.php'; ?>
    <?php require '../partials/sidenav.php'; ?>

    <div class="main">

        <div class="hero hero--flex">
            <div class="hero-label"><?= ucfirst($filter) ?> Sales</div>
            <div class="hero-amount">
                <p>&#8369;<?= number_format($totalSales, 0) ?></p></div>
        </div>

        <div class="statistics-container statistics-container--grid">
            <div class="statistics_card statistics_card--flex">
                <div class="stat_card-label">Weekly Sales</div>
                <div class="stat-value">&#8369;<?= number_format($weeklySales, 0) ?></div>
            </div>
            <div class="statistics_card statistics_card--flex">
                <div class="stat_card-label">Monthly Sales</div>
                <div class="stat-value">&#8369;<?= number_format($monthlySales, 0) ?></div>
            </div>
            <div class="statistics_card statistics_card--flex">
                <div class="stat_card-label">Today's Orders</div>
                <div class="stat-value"><?= $todayOrders ?></div>
            </div>
            <div class="statistics_card statistics_card--flex">
                <div class="stat_card-label">Total Transactions</div>
                <div class="stat-value"><?= $totalTransactions ?></div>
            </div>
        </div>

        <div class="table_container table_container--flex">
        <!-- BEST SELLING CATEGORY -->
        <div class="section-title section-title--flex">
            <p>Best Selling Category</p></div>
        <table>
            <tr>
                <th>Rank</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Total Sales</th>
            </tr>
            <?php $rank = 1; foreach ($categoryRows as $row): ?>
            <tr>
                <td class="product_ranking">#<?= $rank++ ?></td>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td><?= number_format($row['total_quantity']) ?></td>
                <td class="product_sales">&#8369;<?= number_format($row['total_sales'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        </div>
        <!-- Table Container -->

        <div class="table_container table_container--flex">
        <!-- TOP SELLING PRODUCTS -->
        <div class="section-title section-title--flex">
            <p>Top Selling Products</p></div>

        <?php
        // Reusable helper to render a top products table
        $renderTopTable = function(array $products, string $emptyMsg) {
        ?>
            <?php if (empty($products)): ?>
                <span class="no-data"><?= $emptyMsg ?></span>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Sold</th>
                        <th>Revenue</th>
                    </tr>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td class="info-image_column info-image-column--flex">
                            <?php if (!empty($p['image'])): ?>
                                <img src="../../resources/images/uploads/<?= htmlspecialchars($p['image']) ?>" class="prod-img-thumb">
                            <?php endif; ?>
                            <?= htmlspecialchars($p['product_name']) ?>
                        </td>
                        <td><?= htmlspecialchars($p['category'] ?? '—') ?></td>
                        <td>&#8369;<?= number_format($p['price'] ?? 0, 2) ?></td>
                        <td><?= $p['qty_sold'] ?></td>
                        <td class="product_revenue">&#8369;<?= number_format($p['revenue'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif;
        };
        ?>
           <div class="period_wrapper period_wrapper--flex">
         <div class="period_container period-container--flex">
        <div class="period-heading">
            <p>Daily</p></div>
        <?php $renderTopTable($topDaily, 'No data for today.'); ?>
         </div>
           
        <div class="period_container period-container--flex">
        <div class="period-heading">
            <p>Weekly</p></div>
        <?php $renderTopTable($topWeekly, 'No data this week.'); ?>
        </div>

        <div class="period_container period-container--flex">
        <div class="period-heading">
            <p>Monthly</p></div>
        <?php $renderTopTable($topMonthly, 'No data this month.'); ?>
        </div>
        </div>
        <!-- Period Wrapper Flex -->
         </div>
         <!-- Table Container -->

          <div class="table_container table_container--flex">
        <!-- INVENTORY STATUS -->
         <div class="inventory_filter-container inventory_filter-container--flex">
        <div class="section-title section-title--flex">
            <p>Inventory Status</p>
            <a href="inventory.php">Manage All</a>
        </div>

        <!-- Inventory filter form -->
        <form method="GET" action="reports_analysis.php">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <div class="inv-filters">
                <select name="inv_status">
                    <option value="">Filter Status</option>
                    <option value="badge-bad"  <?= $invStatus === 'badge-bad'  ? 'selected' : '' ?>> Low</option>
                    <option value="badge-mid"  <?= $invStatus === 'badge-mid'  ? 'selected' : '' ?>>Medium</option>
                    <option value="badge-good" <?= $invStatus === 'badge-good' ? 'selected' : '' ?>>Good</option>
                </select>
                <select name="inv_category">
                    <option value="">All Categories</option>
                    <?php foreach ($allUnitCats as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $invCategory === $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="inv-submit-btn">Apply Filter</button>
                <?php if ($invStatus !== '' || $invCategory !== ''): ?>
                    <a href="?filter=<?= htmlspecialchars($filter) ?>" class="inv-clear-btn"><span>Clear</span></a>
                <?php endif; ?>
            </div>
            </div>
            <!-- Inventory Filter Container -->

            <!-- Active filter tags -->
            <?php if ($invStatus !== '' || $invCategory !== ''): ?>
            <div class="filter-container filter-container--flex">
                <?php if ($invStatus !== ''):
                    $statusLabel = ['badge-bad' => 'Low', 'badge-mid' => 'Normal', 'badge-good' => 'Good'][$invStatus] ?? $invStatus;
                ?>
                <span class="filter-active-tag filter-active-tag--flex">
                    Status: <?= htmlspecialchars($statusLabel) ?>
                    <a href="?filter=<?= htmlspecialchars($filter) ?>&inv_category=<?= urlencode($invCategory) ?>">
                        <img src="../../resources/images/icons/Menu/Close_MD.svg" alt="Remove Status">
                    </a>
                </span>
                <?php endif; ?>
                <?php if ($invCategory !== ''): ?>
                <span class="filter-active-tag filter-active-tag--flex">
                    Category: <?= htmlspecialchars($invCategory) ?>
                    <a href="?filter=<?= htmlspecialchars($filter) ?>&inv_status=<?= urlencode($invStatus) ?>">
                        <img src="../../resources/images/icons/Menu/Close_MD.svg" alt="Remove Status">
                    </a>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </form>

        <!-- Inventory table -->
        <?php if (empty($filteredIngredients)): ?>
            <span class="no-data">No ingredients match the selected filters.</span>
        <?php else: ?>
        <table>
            <tr>
                <th>Ingredient</th>
                <th>Unit</th>
                <th>Stock</th>
                <th>Low Stock</th>
                <th>Status</th>
            </tr>
            <?php foreach ($filteredIngredients as $i):
                $cls = $i['_cls'];
                $thr = $i['low_stock_threshold'] ?? 5;

                if ($cls === 'badge-bad'){  
                    $label = 'Low';  
                    $sc = 'stock-bad'; 
                }
                elseif ($cls === 'badge-mid'){ 
                    $label = 'Normal';  
                    $sc = 'stock-mid'; 
                }
                else{ 
                    $label = 'High'; 
                    $sc = 'stock-good'; }
            ?>
            <tr>
                <td class="info-image_column info-image_column--flex">
                    <?php if (!empty($i['image'])): ?>
                        <img src="../../resources/images/uploads/<?= htmlspecialchars($i['image']) ?>"
                             class="prod-img-thumb">
                    <?php endif; ?>
                    <?= htmlspecialchars($i['ingredient_name']) ?>
                </td>
                <td><?= htmlspecialchars($i['unit'] ?? '') ?></td>
                <td><?= $i['stock'] ?></td>
                <td><?= $thr ?></td>
                <td class="info-status_column"><span class="<?= $sc ?>"><?= $label ?></span></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        </div>
        <!-- Table Container -->
    </div>
</div>
        <script src="../../resources/js/partials/reports_sidebar.js" type="text/javascript" defer></script>
        <script src="../../resources/js/partials/menu_mobile.js" type="text/javascript" defer></script>
</body>
</html>