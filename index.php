<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- SEO -->
    <meta name="description" content="Login to Al Coffee, your go-to destination for premium coffee blends and brewing accessories. Access your account to manage orders, track shipments, and explore exclusive offers.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Al Coffee">
    <!-- SEO -->

    <!-- SMTags -->
    <meta property="og:Title" content="Login to Al Coffee">
    <meta property="og:Description" content="Login to Al Coffee, your go-to destination for premium coffee blends and brewing accessories. Access your account to manage orders, track shipments, and explore exclusive offers.">
    <meta property="og:image" content="resources/images/logo/logo_black.svg">

    <meta property="twitter:card" content="Al Coffee Login Page">
    <meta property="twitter:title" content="Login to Al Coffee">
    <meta property="twitter:description" content="Login to Al Coffee, your go-to destination for premium coffee blends and brewing accessories. Access your account to manage orders, track shipments, and explore exclusive offers.">
    <meta property="twitter:image" content="resources/images/logo/logo_black.svg">
    <!-- SMTags -->

    <title>Login - Al Coffee</title>
    <link rel="stylesheet" href="resources/css/general.css">
    <link rel="stylesheet" href="resources/css/design_tokens/primitives.css">
    <link rel="stylesheet" href="resources/css/design_tokens/mapping.css">
    <link rel="stylesheet" href="resources/css/auth/login.css">

    <link rel="icon" type="image/svg+xml" href="resources/images/logo/logo_black.svg">
    
    
</head>
<body>

     <div class="login_card login_card--flex">
    <div class="flash-message-container">
        <?php
        if (!empty($_SESSION['flash'])) {
            $class = ($_SESSION['flash']['type'] === 'err') ? 'msg-err' : 'msg-ok';
            echo "<div class='message {$class}'>" . htmlspecialchars($_SESSION['flash']['text']) . "</div>";
            unset($_SESSION['flash']);
        }
        ?>
    </div>

    <div class="form_container form_container--flex">
    
    <div class="container container--flex">
        <div class="logo_container logo_container--flex">
            <img class="login_logo" src="resources/images/logo/Brown.svg" alt="Brand Logo">
            <p class="logo_text">
                al coffee
            </p>
        </div>

        <form action="package/db.php" method="POST">
            <input type="hidden" name="action" value="login">

    <div class="input_container input_container--flex">
         <div class="label_container label_container--flex">
            <img src="resources/images/icons/User/User_03.svg" alt="Person Icon">
             <label for="username">Username</label>
            </div>
            <!-- Label Container -->
            <input type="text" name="username" placeholder="juandelacruz0925" id="username" required>
            <span class="input_message"></span>
    </div>
    <!-- Input Container -->
           
     <div class="input_container input_container--flex">
       <div class="label_container label_container--flex">
                <img src="resources/images/icons/Interface/Lock.svg" alt="Lock Icon">
            <label for="password">Password</label>
</div>
            <input type="password" name="password"
            placeholder="******" id="password" required>
              <span class="input_message"></span>
               <a href="package/auth/forgot_password.php">Forgot Password?</a>
</div>
<!-- Input Container -->

            <button type="submit" class="login-btn" id="login-btn">Login</button>
        </form>

        <div class="help_register-container help_register-container--flex">
            <p class="register-text">
            Don't have an account?</p>
            <a href="package/auth/register.php" class="register-link">Create Account</a>
        </div>
    </div>
    <!-- Container -->
    
     <div class="form_logo_container">
        <img class="form_logo" src="resources/images/stall/Night_Shot_5.svg" alt="al coffee Stall Captured in Night">
     </div>
         </div>
         </div>
         <!-- Login Card -->

    <script src="resources/js/auth/login.js" type="text/javascript" defer></script>
</body>
</html>
