<?php
/**
 * User Authentication - FR-02
 * Password Security - NFR-03 (bcrypt verification)
 */

require_once 'config.php';
require_once 'database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            // Verify password with bcrypt (NFR-03)
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['last_activity'] = time();
                
                $db->logTransaction($user['id'], 'user_login', "User logged in: $email", 'success');
                
                // Redirect based on role
                if ($user['is_admin']) {
                    header('Location: admin/index.php');
                } else {
                    header('Location: index.php');
                }
                exit();
            } else {
                $error = 'Invalid email or password.';
                $db->logTransaction(null, 'user_login', "Failed login attempt: $email", 'failed');
            }
        } catch(PDOException $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h1>Login</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" autocomplete="on">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>"
                           autocomplete="email">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <p class="text-center mt-3">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
            
            <div class="demo-credentials">
                <p><strong>Demo Credentials:</strong></p>
                <p>Admin: admin@bookstore.com / admin123</p>
            </div>
        </div>
    </div>
</body>
</html>
