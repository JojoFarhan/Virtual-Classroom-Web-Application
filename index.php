<?php
// index.php - Main landing page for Virtual Classroom
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Redirect logged-in users to their dashboard
if (isLoggedIn()) {
    // Check user role and redirect accordingly
    if (hasRole('admin')) {
        header('Location: admin/index.php');
        exit;
    } elseif (hasRole('teacher')) {
        header('Location: teacher/index.php');
        exit;
    } elseif (hasRole('student')) {
        header('Location: student/index.php');
        exit;
    }
}

// Get some stats for the landing page
$stats = [
    'courses' => 0,
    'users' => 0,
    'discussions' => 0
];

// Count total courses
$stmt = $pdo->query("SELECT COUNT(*) FROM courses WHERE is_archived = 0");
$stats['courses'] = $stmt->fetchColumn();

// Count active users
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_status = 'active'");
$stats['users'] = $stmt->fetchColumn();

// Count discussions
$stmt = $pdo->query("SELECT COUNT(*) FROM discussions");
$stats['discussions'] = $stmt->fetchColumn();

// Get featured courses
$stmt = $pdo->query("
    SELECT c.course_id, c.course_name, c.course_code, c.description, 
           u.first_name, u.last_name,
           COUNT(DISTINCT e.user_id) as enrollment_count
    FROM courses c
    JOIN users u ON c.creator_id = u.user_id
    LEFT JOIN enrollments e ON c.course_id = e.course_id
    WHERE c.is_archived = 0
    GROUP BY c.course_id
    ORDER BY enrollment_count DESC
    LIMIT 4
");
$featured_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Welcome to Virtual Classroom";
include 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="jumbotron bg-light shadow-sm">
                <h1 class="display-4">Welcome to Virtual Classroom</h1>
                <p class="lead">Learn, teach, and collaborate in our online learning environment.</p>
                <hr class="my-4">
                <p>Join thousands of students and teachers who are already using our platform.</p>
                <div class="mt-4">
                    <a href="register.php" class="btn btn-primary btn-lg mr-2">Get Started</a>
                    <a href="login.php" class="btn btn-outline-secondary btn-lg">Login</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="row mb-5">
        <div class="col-md-4">
            <div class="card shadow text-center mb-4">
                <div class="card-body">
                    <i class="fas fa-book fa-3x text-primary mb-3"></i>
                    <h2 class="counter"><?php echo $stats['courses']; ?></h2>
                    <p class="lead">Active Courses</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow text-center mb-4">
                <div class="card-body">
                    <i class="fas fa-users fa-3x text-success mb-3"></i>
                    <h2 class="counter"><?php echo $stats['users']; ?></h2>
                    <p class="lead">Active Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow text-center mb-4">
                <div class="card-body">
                    <i class="fas fa-comments fa-3x text-info mb-3"></i>
                    <h2 class="counter"><?php echo $stats['discussions']; ?></h2>
                    <p class="lead">Active Discussions</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Courses -->
    <div class="row mb-5">
        <div class="col-md-12">
            <h2 class="mb-4">Featured Courses</h2>
        </div>
        <?php if (empty($featured_courses)): ?>
        <div class="col-md-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No courses available at the moment.
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($featured_courses as $course): ?>
        <div class="col-md-3">
            <div class="card shadow mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <?php echo htmlspecialchars($course['course_code']); ?>: <?php echo htmlspecialchars($course['course_name']); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        <?php echo htmlspecialchars(substr($course['description'], 0, 100) . '...'); ?>
                    </p>
                    <p class="text-muted">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?>
                    </p>
                    <p class="text-muted">
                        <i class="fas fa-users"></i> <?php echo $course['enrollment_count']; ?> students enrolled
                    </p>
                </div>
                <div class="card-footer bg-white">
                    <a href="login.php" class="btn btn-primary btn-sm">Log in to enroll</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Features Section -->
    <div class="row mb-5">
        <div class="col-md-12">
            <h2 class="mb-4">Platform Features</h2>
        </div>
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-book-open fa-3x text-primary mb-3"></i>
                    <h4>Comprehensive Course Management</h4>
                    <p>Create, manage, and organize courses with ease. Upload materials, assign homework, and track progress.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-tasks fa-3x text-success mb-3"></i>
                    <h4>Assignment System</h4>
                    <p>Create assignments with deadlines, allow submissions, and provide feedback and grades to students.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-comments fa-3x text-info mb-3"></i>
                    <h4>Interactive Discussions</h4>
                    <p>Engage in meaningful discussions with threaded comments and replies for better communication.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Testimonials Section -->
    <div class="row mb-5">
        <div class="col-md-12">
            <h2 class="mb-4">What Our Users Say</h2>
        </div>
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                    </div>
                    <p class="card-text">"Virtual Classroom has transformed how I teach my courses. The platform is intuitive and provides all the tools I need to create engaging learning experiences."</p>
                    <p class="font-weight-bold mb-0">Dr. Sarah Johnson</p>
                    <small class="text-muted">Computer Science Professor</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                    </div>
                    <p class="card-text">"As a student, I love how organized everything is in Virtual Classroom. All my assignments, discussions, and course materials are easy to find and access."</p>
                    <p class="font-weight-bold mb-0">Michael Thompson</p>
                    <small class="text-muted">Engineering Student</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star-half-alt text-warning"></i>
                    </div>
                    <p class="card-text">"Managing multiple classes has never been easier. The dashboard gives me a quick overview of what needs attention, and the discussion feature keeps students engaged."</p>
                    <p class="font-weight-bold mb-0">Prof. Robert Chen</p>
                    <small class="text-muted">Business Administration</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card shadow bg-primary text-white">
                <div class="card-body text-center py-5">
                    <h2 class="mb-3">Ready to Get Started?</h2>
                    <p class="lead mb-4">Join our community of learners and educators today.</p>
                    <a href="register.php" class="btn btn-light btn-lg mr-2">Register Now</a>
                    <a href="login.php" class="btn btn-outline-light btn-lg">Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>