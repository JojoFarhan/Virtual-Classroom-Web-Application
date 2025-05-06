<?php
// student/courses.php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../classes/Course.php';

// Require login
requireLogin();

// Load enrolled courses
$course = new Course($pdo);
$enrolled_courses = $course->getCoursesByUser($_SESSION['user_id']);

$pageTitle = "My Courses";
include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>My Courses</h1>
    
    <form class="d-flex" action="find-courses.php" method="get">
        <input class="form-control me-2" type="search" name="q" placeholder="Find a course" aria-label="Search">
        <button class="btn btn-outline-success" type="submit">Search</button>
    </form>
</div>

<?php if (empty($enrolled_courses)): ?>
    <div class="alert alert-info">
        <p>You are not enrolled in any courses yet.</p>
        <a href="<?php echo BASE_URL; ?>/student/find-courses.php" class="btn btn-primary">Find Courses</a>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($enrolled_courses as $course): ?>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($course['course_name']); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($course['course_code']); ?></h6>
                        <p class="card-text"><?php echo htmlspecialchars(substr($course['description'], 0, 150)) . (strlen($course['description']) > 150 ? '...' : ''); ?></p>
                    </div>
                    <div class="card-footer">
                        <a href="<?php echo BASE_URL; ?>/student/course-details.php?id=<?php echo $course['course_id']; ?>" class="btn btn-primary">View Course</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>