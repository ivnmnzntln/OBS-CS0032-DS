<?php
/**
 * User Registration - FR-01
 * Password Security - NFR-03 (bcrypt hashing)
 */

require_once 'config.php';
require_once 'database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8');
    $address = htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES, 'UTF-8');
    
    // Validation
    if (empty($email) || empty($password) || empty($full_name)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Email already registered.';
            } else {
                // Hash password with bcrypt (NFR-03)
                $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
                
                // Insert new user
                $stmt = $conn->prepare("
                    INSERT INTO users (email, password, full_name, phone, address)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$email, $hashed_password, $full_name, $phone, $address]);
                
                $db->logTransaction($conn->lastInsertId(), 'user_registration', "New user registered: $email", 'success');
                
                $success = 'Registration successful! You can now log in.';
            }
        } catch(PDOException $e) {
            $error = 'Registration failed. Please try again.';
            $db->logTransaction(null, 'user_registration', "Failed: " . $e->getMessage(), 'failed');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h1>Create Account</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" autocomplete="on">
                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" required 
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES); ?>"
                           autocomplete="name">
                </div>
                
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>"
                           autocomplete="email">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES); ?>"
                           autocomplete="tel">
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3" autocomplete="street-address"><?php echo htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required 
                           minlength="8" autocomplete="new-password">
                    <small>At least 8 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           minlength="8" autocomplete="new-password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>
            
            <p class="text-center mt-3">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </div>
    </div>
</body>
</html>
