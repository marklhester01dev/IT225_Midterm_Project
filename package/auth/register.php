<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- SEO -->
    <meta name="description" content="Register - Al Coffee. Create your account to start enjoying premium coffee.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Al Coffee">
    <!-- SEO -->

    <!-- SMTags -->
    <meta property="og:title" content="Register - Al Coffee">
    <meta property="og:description" content="Register - Al Coffee. Create your account to start enjoying premium coffee.">
    <meta property="og:image" content="../../resources/images/logo/Logo_Black.svg">
    <meta property="twitter:card" content="Register - Al Coffee">
    <meta property="twitter:title" content="Register - Al Coffee">
    <meta property="twitter:description" content="Register - Al Coffee. Create your account to start enjoying premium coffee.">
    <meta property="twitter:image" content="../../resources/images/logo/Logo_Black.svg">
    <!-- SMTags -->

    <title>Register - Al Coffee</title>
    <link rel="stylesheet" href="../../resources/css/general.css">
    <link rel="stylesheet" href="../../resources/css/design_tokens/primitives.css">
    <link rel="stylesheet" href="../../resources/css/design_tokens/mapping.css">
    <link rel="stylesheet" href="../../resources/css/auth/register.css">

    <link rel="icon" type="image/svg+xml" href="../../resources/images/logo/Logo_Black.svg">
</head>
<body>

    <!-- Flash message -->
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
                    <img class="login_logo" src="../../resources/images/logo/Brown.svg" alt="Brand Logo">
                    <p class="logo_text">al coffee</p>
                </div>

                <form action="../db.php" method="POST">
                    <input type="hidden" name="action" value="register">

                    <div class="input_container input_container--flex">
                        <div class="label_container label_container--flex">
                            <img src="../../resources/images/icons/User/User_03.svg" alt="User Icon">
                            <label for="fullname">Full Name</label>
                        </div>
                        <input type="text" name="fullname" id="fullname" placeholder="Juan Dela Cruz" required>
                        <span class="input_message"></span>
                    </div>

                    <div class="input_container input_container--flex">
                        <div class="label_container label_container--flex">
                            <img src="../../resources/images/icons/User/User_03.svg" alt="Username Icon">
                            <label for="username">Username</label>
                        </div>
                        <input type="text" name="username" id="username" placeholder="juandelacruz0925" required>
                        <span class="input_message"></span>
                    </div>

                    <div class="input_container input_container--flex">
                        <div class="label_container label_container--flex">
                            <img src="../../resources/images/icons/Interface/Lock.svg" alt="Password Icon">
                            <label for="password">Password</label>
                        </div>
                        <input type="password" name="password" id="password" placeholder="*******" required>
                        <span class="input_message"></span>
                    </div>

                    <div class="input_container input_container--flex">
                        <div class="label_container label_container--flex">
                            <img src="../../resources/images/icons/Interface/Lock.svg" alt="Confirm Password Icon">
                            <label for="confirm_password">Confirm Password</label>
                        </div>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="*******" required>
                        <span class="input_message"></span>
                    </div>

                    <button type="submit" class="login-btn">Create Account</button>
                </form>

                <div class="help_register-container help_register-container--flex">
                    <p>Already have an account?</p>
                    <a href="../../index.php">Login</a>
                </div>
            </div>
        
            <div class="form_logo_container">
                <img class="form_logo" src="../../resources/images/stall/Night_Shot_5.svg" alt="al coffee Stall Captured in Night">
            </div>
        </div>
    </div>

    <script src="../../resources/js/auth/register.js" type="text/javascript" defer></script>
</body>
</html>

