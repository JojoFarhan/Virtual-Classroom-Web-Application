<?php
// admin/index.php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../classes/User.php';
require_once '../classes/Course.php';

// Require admin role
requireRole('admin');

// Load user data
$user = new User($pdo);
$user->getUserById($_SESSION['user_id']);

// Get some stats for the dashboard
$user_model = new User($pdo);
$course_model = new Course($pdo);

// Get total counts
$total_users = $user_model->getTotalUsers();
$total_courses = $course_model->getTotalCourses();

// Get recent users
$recent_users = $user_model->getRecentUsers(5);

// Get recent courses
$recent_courses = $course_model->getRecentCourses(5);

$pageTitle = "Admin Dashboard";
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Admin Dashboard</h1>
    </div>
</div>

<div class="row">
    <!-- Stats Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2 stats-card primary">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Users</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300 stats-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2 stats-card success">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Courses</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_courses; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-book fa-2x text-gray-300 stats-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2 stats-card warning">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Active Students</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php 
                                $stmt = $pdo->query("SELECT COUNT(*) FROM user_roles ur JOIN roles r ON ur.role_id = r.role_id WHERE r.role_name = 'student'");
                                echo $stmt->fetchColumn();
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-graduate fa-2x text-gray-300 stats-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2 stats-card primary">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Active Teachers</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php 
                                $stmt = $pdo->query("SELECT COUNT(*) FROM user_roles ur JOIN roles r ON ur.role_id = r.role_id WHERE r.role_name = 'teacher'");
                                echo $stmt->fetchColumn();
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chalkboard-teacher fa-2x text-gray-300 stats-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Users -->
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Recent Users</h6>
                <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Status</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td>
                                    <?php if ($u['user_status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php elseif ($u['user_status'] == 'suspended'): ?>
                                        <span class="badge bg-danger">Suspended</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($u['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Courses -->
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Recent Courses</h6>
                <a href="<?php echo BASE_URL; ?>/admin/courses.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Code</th>
                                <th>Creator</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_courses as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($c['course_code']); ?></td>
                                <td>
                                    <?php 
                                        $creator = $user->getUserById($c['creator_id']);
                                        echo $creator ? htmlspecialchars($creator['first_name'] . ' ' . $creator['last_name']) : 'Unknown'; 
                                    ?>
                                </td>
                                <td><?php echo formatDate($c['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="<?php echo BASE_URL; ?>/admin/users.php?action=create" class="list-group-item list-group-item-action d-flex gap-3 py-3">
                        <div class="d-flex gap-2 w-100 justify-content-between">
                            <div>
                                <h6 class="mb-0">Create New User</h6>
                                <p class="mb-0 opacity-75">Add a student, teacher, or administrator</p>
                            </div>
                        </div>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/courses.php?action=create" class="list-group-item list-group-item-action d-flex gap-3 py-3">
                        <div class="d-flex gap-2 w-100 justify-content-between">
                            <div>
                                <h6 class="mb-0">Create New Course</h6>
                                <p class="mb-0 opacity-75">Set up a new course in the system</p>
                            </div>
                        </div>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="list-group-item list-group-item-action d-flex gap-3 py-3">
                        <div class="d-flex gap-2 w-100 justify-content-between">
                            <div>
                                <h6 class="mb-0">View Reports</h6>
                                <p class="mb-0 opacity-75">Access system statistics and reports</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- System Info -->
    <div class="col-md-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">System Information</h6>
            </div>
            <div class="card-body">
                <table class="table">
                    <tbody>
                        <tr>
                            <th scope="row">PHP Version</th>
                            <td><?php echo phpversion(); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Server</th>
                            <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Database</th>
                            <td>MySQL <?php echo $pdo->query('select version()')->fetchColumn(); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">System Time</th>
                            <td><?php echo date('Y-m-d H:i:s'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>