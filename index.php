<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="resources/css/style.css">
</head>
<body>

    <!-- Flash message -->
    <div class="flash-message-container">
        <?php
        if (!empty($_SESSION['flash'])) {
            $class = ($_SESSION['flash']['type'] === 'err') ? 'msg-err' : 'msg-ok';
            echo "<div class='message {$class}'>" . htmlspecialchars($_SESSION['flash']['text']) . "</div>";
            unset($_SESSION['flash']);
        }
        ?>
    </div>

    <div class="container">
        <h2>Login</h2>

        <form action="package/db.php" method="POST">
            <input type="hidden" name="action" value="login">

            <label>Username</label>
            <input type="text" name="username" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit">Login</button>
        </form>

        <br>
        <a href="package/forgot_password.php">Forgot Password?</a>

        <hr>

        <p class="register-text">
            Don't have an account?
            <a href="package/register.php" class="register-link">Create Account</a>
        </p>
    </div>

</body>
</html>
