 <!-- Mobile Nav -->
<?php
$currentPage = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
$activePage = $currentPage;

require_once __DIR__ . '/low_stock_badge.php';
?>


 <header class="header--flex">
    <div class="header_left-container header_left-container--flex">
        
            <img class="burger_icon_mobile" src="../../resources/images/icons/Menu/Hamburger_LG.svg" alt="Menu">

    </div>

    <div class="header_right-container header_right-container--flex"> 
        <a href="admin.php">
             <img src="../../resources/images/logo/Logo_Black.svg" alt="Admin Menu" class="business_logo_mobile">
        </a>
    </div>
</header>

      <div class="sidenav_mobile">

    <div class="sidenav_mobile_main-container">

             <a href="home_page.php" class="sidenav_mobile_main <?= ($activePage === 'home_page.php') ? 'sidenav_mobile_main--active' : '' ?>" aria-current="<?= ($activePage === 'home_page.php') ? 'page' : 'false' ?>">
                <p>Dashboard</p>
            </a>
          
             <a href="products.php" class="sidenav_mobile_main <?= ($activePage === 'products.php') ? 'sidenav_mobile_main--active' : '' ?>" aria-current="<?= ($activePage === 'products.php') ? 'page' : 'false' ?>">
                <p>Products</p>
            </a>
      
              <a href="inventory.php" class="sidenav_mobile_main <?= ($activePage === 'inventory.php') ? 'sidenav_mobile_main--active' : '' ?>" aria-current="<?= ($activePage === 'inventory.php') ? 'page' : 'false' ?>">
                 <?php if (!empty($lowStockCount) && intval($lowStockCount) > 0): ?>
                     <span class="alert-nav-badge"><?= intval($lowStockCount) ?></span>
                 <?php endif; ?>
                <p>Inventory</p>
            </a>
      
             <a href="sales.php" class="sidenav_mobile_main <?= ($activePage === 'sales.php') ? 'sidenav_mobile_main--active' : '' ?>" aria-current="<?= ($activePage === 'sales.php') ? 'page' : 'false' ?>">
                <p>Sales</p>
            </a>
    
             <a href="reports_analysis.php" class="sidenav_mobile_main <?= ($activePage === 'reports_analysis.php') ? 'sidenav_mobile_main--active' : '' ?>" aria-current="<?= ($activePage === 'reports_analysis.php') ? 'page' : 'false' ?>">
                <p>Reports</p>
            </a>
           
             <a href="admin.php" class="sidenav_mobile_main <?= ($activePage === 'admin.php') ? 'sidenav_mobile_main--active' : '' ?>" aria-current="<?= ($activePage === 'admin.php') ? 'page' : 'false' ?>">
                <p>Admin</p>
            </a>


        <a href="logout.php"
           onclick="return confirm('Are you sure you want to log out?')" class="sidenav_mobile_main sidenav_mobile_logout">
           <p>Logout</p>
        </a>
         </div>
        <!-- Sidenav_Main_Container -->
    </div>
    <!-- SideNav -->
     <!-- Mobile Copy -->