<?php
// login.php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'classes/User.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_email = sanitizeInput($_POST['username_email']);
    $password = $_POST['password']; // Not sanitizing password
    
    if (empty($username_email) || empty($password)) {
        $error = "Both fields are required";
    } else {
        $user = new User($pdo);
        $result = $user->login($username_email, $password);
        
        if ($result) {
            // Redirect based on role
            if (hasRole('admin')) {
                header("Location: " . BASE_URL . "/admin/");
            } elseif (hasRole('teacher')) {
                header("Location: " . BASE_URL . "/teacher/");
            } else {
                header("Location: " . BASE_URL . "/student/");
            }
            exit;
        } else {
            $error = "Invalid username/email or password";
        }
    }
}

$pageTitle = "Login";
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card mt-5">
            <div class="card-body">
                <h1 class="card-title text-center">Login</h1>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="username_email" class="form-label">Username or Email</label>
                        <input type="text" class="form-control" id="username_email" name="username_email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p>Don't have an account? <a href="<?php echo BASE_URL; ?>/register.php">Register</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


