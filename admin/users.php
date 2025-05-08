<?php
// admin/users.php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../classes/User.php';

// Require admin role
requireRole('admin');

// Load user data
$user_model = new User($pdo);

// Handle actions
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        // case 'create':
        //     // Create new user
        //     $username = sanitizeInput($_POST['username']);
        //     $email = sanitizeInput($_POST['email']);
        //     $password = $_POST['password'];
        //     $first_name = sanitizeInput($_POST['first_name']);
        //     $last_name = sanitizeInput($_POST['last_name']);
        //     $role = sanitizeInput($_POST['role']);
            
        //     if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        //         $error = "All fields are required";
        //     } else {
        //         $result = $user_model->createUser($username, $email, $password, $first_name, $last_name);
                
        //         if ($result['success']) {
        //             // Assign role
        //             $user_model->assignRole($result['user_id'], $role);
                    
        //             redirectWithMessage(BASE_URL . "/admin/users.php", "User created successfully!");
        //         } else {
        //             $error = $result['message'];
        //         }
        //     }
        //     break;
        
        // Update the create section in admin/users.php
    case 'create':
        // Create new user
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $role = sanitizeInput($_POST['role']);
        
        if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
            $error = "All fields are required";
        } else {
            $user_id = $user_model->createUser($username, $email, $password, $first_name, $last_name);
            
            if ($user_id) {
                // Assign role
                $user_model->assignRole($user_id, $role);
                
                redirectWithMessage(BASE_URL . "/admin/users.php", "User created successfully!");
            } else {
                $error = "Failed to create user. Username or email may already exist.";
            }
        }
        break;
            
        case 'edit':
            // Update user
            $username = sanitizeInput($_POST['username']);
            $email = sanitizeInput($_POST['email']);
            $first_name = sanitizeInput($_POST['first_name']);
            $last_name = sanitizeInput($_POST['last_name']);
            $status = sanitizeInput($_POST['status']);
            $roles = isset($_POST['roles']) ? $_POST['roles'] : [];
            
            if (empty($username) || empty($email) || empty($first_name) || empty($last_name)) {
                $error = "Required fields cannot be empty";
            } else {
                $result = $user_model->updateUser($user_id, $username, $email, $first_name, $last_name, $status);
                
                if ($result['success']) {
                    // Update roles
                    $user_model->updateUserRoles($user_id, $roles);
                    
                    $success = "User updated successfully!";
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'delete':
            // Delete user
            $confirm = isset($_POST['confirm']) ? true : false;
            
            if ($confirm) {
                $result = $user_model->deleteUser($user_id);
                
                if ($result['success']) {
                    redirectWithMessage(BASE_URL . "/admin/users.php", "User deleted successfully!");
                } else {
                    $error = $result['message'];
                }
            } else {
                $error = "Please confirm deletion";
            }
            break;
            
        case 'reset-password':
            // Reset user password
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($password) || $password !== $confirm_password) {
                $error = "Passwords do not match or are empty";
            } else {
                $result = $user_model->resetPassword($user_id, $password);
                
                if ($result['success']) {
                    $success = "Password reset successfully!";
                } else {
                    $error = $result['message'];
                }
            }
            break;
    }
}

// Get all users for list view
$users = [];
if ($action === 'list') {
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $role_filter = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';
    $status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
    
    $users = $user_model->getUsers($search, $role_filter, $status_filter);
}

// Get specific user for edit view
$edit_user = null;
if ($action === 'edit' || $action === 'delete' || $action === 'reset-password') {
    $edit_user = $user_model->getUserById($user_id);
    
    if (!$edit_user) {
        redirectWithMessage(BASE_URL . "/admin/users.php", "User not found!", "danger");
    }
    
    // Get user roles for edit view
    if ($action === 'edit') {
        $user_roles = $user_model->getUserRoles($user_id);
    }
}

// Get all roles for forms
$stmt = $pdo->query("SELECT * FROM roles ORDER BY role_name");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "User Management";
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">User Management</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'list'): ?>
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">User List</h6>
        <a href="<?php echo BASE_URL; ?>/admin/users.php?action=create" class="btn btn-primary btn-sm">
            <i class="fas fa-user-plus"></i> Add New User
        </a>
    </div>
    <div class="card-body">
        <!-- Search and Filter Form -->
        <form class="mb-4" method="get" action="">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Search users..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="role">
                        <option value="">Filter by Role</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['role_id']; ?>" <?php echo isset($_GET['role']) && $_GET['role'] == $role['role_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($role['role_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">Filter by Status</option>
                        <option value="active" <?php echo isset($_GET['status']) && $_GET['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo isset($_GET['status']) && $_GET['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo isset($_GET['status']) && $_GET['status'] == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>
    
        <!-- Users Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Roles</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo $u['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
                            <?php if ($u['user_status'] == 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php elseif ($u['user_status'] == 'suspended'): ?>
                                <span class="badge bg-danger">Suspended</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                $user_roles = $user_model->getUserRoles($u['user_id']);
                                $role_names = array_column($user_roles, 'role_name');
                                echo implode(', ', array_map('ucfirst', $role_names));
                            ?>
                        </td>
                        <td><?php echo formatDate($u['created_at']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo BASE_URL; ?>/admin/users.php?action=edit&id=<?php echo $u['user_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/admin/users.php?action=reset-password&id=<?php echo $u['user_id']; ?>" class="btn btn-warning">
                                    <i class="fas fa-key"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/admin/users.php?action=delete&id=<?php echo $u['user_id']; ?>" class="btn btn-danger">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No users found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($action === 'create'): ?>
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Create New User</h6>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo BASE_URL; ?>/admin/users.php?action=create">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                </div>
                <div class="col-md-6">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="col-md-6">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['role_id']; ?>"><?php echo ucfirst(htmlspecialchars($role['role_name'])); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'edit'): ?>
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Edit User: <?php echo htmlspecialchars($edit_user['first_name'] . ' ' . $edit_user['last_name']); ?></h6>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo BASE_URL; ?>/admin/users.php?action=edit&id=<?php echo $user_id; ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($edit_user['username']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($edit_user['first_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($edit_user['last_name']); ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="active" <?php echo $edit_user['user_status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $edit_user['user_status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo $edit_user['user_status'] == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Roles</label>
                    <div class="d-flex flex-wrap">
                        <?php foreach ($roles as $role): ?>
                            <div class="form-check me-3 mb-2">
                                <input class="form-check-input" type="checkbox" id="role_<?php echo $role['role_id']; ?>" name="roles[]" value="<?php echo $role['role_id']; ?>"
                                    <?php 
                                        foreach ($user_roles as $user_role) {
                                            if ($user_role['role_id'] == $role['role_id']) {
                                                echo 'checked';
                                                break;
                                            }
                                        }
                                    ?>
                                >
                                <label class="form-check-label" for="role_<?php echo $role['role_id']; ?>">
                                    <?php echo ucfirst(htmlspecialchars($role['role_name'])); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'reset-password'): ?>
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Reset Password: <?php echo htmlspecialchars($edit_user['first_name'] . ' ' . $edit_user['last_name']); ?></h6>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo BASE_URL; ?>/admin/users.php?action=reset-password&id=<?php echo $user_id; ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-warning">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'delete'): ?>
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Delete User: <?php echo htmlspecialchars($edit_user['first_name'] . ' ' . $edit_user['last_name']); ?></h6>
    </div>
    <div class="card-body">
        <div class="alert alert-danger">
            <p><strong>Warning:</strong> You are about to delete this user. This action cannot be undone.</p>
            <p>All associated data including enrollments, submissions, and comments will be permanently removed.</p>
        </div>
        
        <form method="post" action="<?php echo BASE_URL; ?>/admin/users.php?action=delete&id=<?php echo $user_id; ?>">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="confirm" name="confirm" required>
                <label class="form-check-label" for="confirm">
                    I confirm that I want to delete this user and all associated data.
                </label>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-danger">Delete User</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>