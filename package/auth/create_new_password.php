<?php
session_start();

if (!isset($_GET['user']) || empty($_GET['user'])) {
    $_SESSION['flash'] = ['type' => 'err', 'text' => 'Invalid link.'];
    header('Location: ../../index.php');
    exit;
}

$username = $_GET['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- SEO -->
    <meta name="description" content="Create New Password - Al Coffee. Set your new password.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Al Coffee">
    <!-- SEO -->

    <!-- SMTags -->
    <meta property="og:title" content="Create New Password - Al Coffee">
    <meta property="og:description" content="Create New Password - Al Coffee. Set your new password.">
    <meta property="og:image" content="../../resources/images/logo/Logo_Black.svg">
    <meta property="twitter:card" content="Create New Password - Al Coffee">
    <meta property="twitter:title" content="Create New Password - Al Coffee">
    <meta property="twitter:description" content="Create New Password - Al Coffee. Set your new password.">
    <meta property="twitter:image" content="../../resources/images/logo/Logo_Black.svg">
    <!-- SMTags -->

    <title>Create New Password - Al Coffee</title>
    <link rel="stylesheet" href="../../resources/css/general.css">
    <link rel="stylesheet" href="../../resources/css/design_tokens/primitives.css">
    <link rel="stylesheet" href="../../resources/css/design_tokens/mapping.css">
    <link rel="stylesheet" href="../../resources/css/auth/create_new.css">


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

                <form action="db.php" method="POST">
                    <input type="hidden" name="action" value="create_new_password">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">

                    <div class="input_container input_container--flex">
                        <div class="label_container label_container--flex">
                            <img src="../../resources/images/icons/Interface/Lock.svg" alt="New Password Icon">
                            <label for="new_password">New Password</label>
                        </div>
                        <input type="password" id="new_password" name="password" placeholder="******" required>
                        <span class="input_message"></span>
                    </div>

                    <div class="input_container input_container--flex">
                        <div class="label_container label_container--flex">
                            <img src="../../resources/images/icons/Interface/Lock.svg" alt="Confirm Password Icon">
                            <label for="confirm_password">Confirm New Password</label>
                        </div>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="******" required>
                        <span class="input_message"></span>
                    </div>

                    <button type="submit" class="login-btn">Change Password</button>
                </form>

                <div class="help_register-container help_register-container--flex">
                    <p>Back to login?</p>
                    <a href="../../index.php">Login</a>
                </div>
            </div>
        
            <div class="form_logo_container">
                <img class="form_logo" src="../../resources/images/stall/Night_Shot_5.svg" alt="al coffee Stall Captured in Night">
            </div>
        </div>
    </div>

    <script src="../../resources/js/auth/create_new.js" type="text/javascript" defer></script>

</body>
</html>

