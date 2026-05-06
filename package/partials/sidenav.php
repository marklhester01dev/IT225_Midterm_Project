<?php
//  sidenav active link (expanded + icon/collapsed)
$currentPage = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
$activePage = $currentPage;

// Low stock badge count for Inventory linkage
// Must have $conn defined or the include will create a fallback connection.
require_once __DIR__ . '/low_stock_badge.php';
?>

 <!-- sidenav -->
    <div class="sidenav">

     <div class="sidenav_header-container sidenav_header-container--flex">

         <a href="admin.php" class="sidenav_logo-admin sidenav_logo-admin--flex">
             <img src="../../resources/images/logo/Logo_Black.svg" alt="Admin Menu" class="business_logo_desktop">
             <p>al coffee</p>
        </a>

        <div class="sidenav_header_icon-container">
               <img class="drawer_desktop" src="../../resources/images/icons/Arrow/Chevron_Left_MD.svg" alt="Toggle Menu">
            <span class="tooltip tooltip_collapse">Collapse Menu</span>
        </div>
    </div>

   
     <span class="line-separator"></span>

    <div class="sidenav_main-container">
             <a href="home_page.php" class="sidenav_main <?= ($activePage === 'home_page.php') ? 'sidenav_main--active' : '' ?>" aria-current="<?= ($activePage === 'home_page.php') ? 'page' : 'false' ?>">
                 <img src="../../resources/images/icons/Interface/Dashboard.svg" alt="Dashboard">
                <p>Dashboard</p>
                 <span class="tooltip tooltip_dashboard">Dashboard</span>
            </a>
          
             <a href="products.php" class="sidenav_main <?= ($activePage === 'products.php') ? 'sidenav_main--active' : '' ?>" aria-current="<?= ($activePage === 'products.php') ? 'page' : 'false' ?>">
                  <img src="../../resources/images/icons/Environment/Coffe_To_Go.svg" alt="Products">
                <p>Products</p>
                 <span class="tooltip tooltip_products">Products</span>
            </a>
      
              <a href="inventory.php" class="sidenav_main <?= ($activePage === 'inventory.php') ? 'sidenav_main--active' : '' ?>" aria-current="<?= ($activePage === 'inventory.php') ? 'page' : 'false' ?>">
                 <?php if (!empty($lowStockCount) && intval($lowStockCount) > 0): ?>
                     <span class="alert-nav-badge"><?= intval($lowStockCount) ?></span>
                 <?php endif; ?>
                 <img src="../../resources/images/icons/File/Archive.svg" alt="Inventory">
                <p>Inventory</p>
                 <span class="tooltip tooltip_inventory"
                 >Inventory</span>
            </a>
      
             <a href="sales.php" class="sidenav_main <?= ($activePage === 'sales.php') ? 'sidenav_main--active' : '' ?>" aria-current="<?= ($activePage === 'sales.php') ? 'page' : 'false' ?>">
                 <img src="../../resources/images/icons/Interface/Cash.svg" alt="Sales">
                <p>Sales</p>
                 <span class="tooltip tooltip_sales">Sales</span>
            </a>
    
             <a href="reports_analysis.php" class="sidenav_main <?= ($activePage === 'reports_analysis.php') ? 'sidenav_main--active' : '' ?>" aria-current="<?= ($activePage === 'reports_analysis.php') ? 'page' : 'false' ?>">
                   <img src="../../resources/images/icons/Interface/Vertical_Bar.svg" alt="Reports">
                <p>Reports</p>
                 <span class="tooltip tooltip_reports">Reports</span>
            </a>
           
             <a href="admin.php" class="sidenav_main <?= ($activePage === 'admin.php') ? 'sidenav_main--active' : '' ?>" aria-current="<?= ($activePage === 'admin.php') ? 'page' : 'false' ?>">
                 <img src="../../resources/images/icons/User/User_03.svg" alt="Admin">
                <p>Admin</p>
                 <span class="tooltip tooltip_admin">Admin</span>
            </a>
         </div>
        <!-- Sidenav_Main_Container -->
          
     <span class="line-separator"></span>

     <div class="sidenav_footer-container sidenav_footer-container--flex">
         <a href="logout.php"
           onclick="return confirm('Are you sure you want to log out?')" class="logout_container logout_container--flex">
             <img src="../../resources/images/icons/Interface/Log_Out.svg" alt="Logout">
           <p>Logout</p>
            <span class="tooltip tooltip_logout">Logout</span>
        </a>
     </div>
     <!-- Footer Container -->
    
    </div>
    <!-- SideNav -->
