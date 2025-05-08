<?php
// teacher/index.php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../classes/User.php';
require_once '../classes/Course.php';
require_once '../classes/Assignment.php';

// Require teacher role
requireRole('teacher');

// Load user data
$user = new User($pdo);
$user->getUserById($_SESSION['user_id']);

// Load courses created by this teacher
$course = new Course($pdo);
$teaching_courses = $course->getCoursesByUser($_SESSION['user_id'], 'teacher');

$pageTitle = "Teacher Dashboard";
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Teacher Dashboard</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">My Courses</h5>
                <a href="<?php echo BASE_URL; ?>/teacher/create-course.php" class="btn btn-sm btn-primary">Create Course</a>
            </div>
            <div class="card-body">
                <?php if (empty($teaching_courses)): ?>
                    <p class="text-muted">You haven't created any courses yet.</p>
                    <a href="<?php echo BASE_URL; ?>/teacher/create-course.php" class="btn btn-primary">Create Your First Course</a>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Code</th>
                                    <th>Created</th>
                                    <th>Students</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teaching_courses as $c): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($c['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($c['course_code']); ?></td>
                                    <td><?php echo formatDate($c['created_at']); ?></td>
                                    <td>
                                        <?php 
                                        // Get student count (simplified for example)
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ? AND enrollment_type = 'student'");
                                        $stmt->execute([$c['course_id']]);
                                        echo $stmt->fetchColumn();
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo BASE_URL; ?>/teacher/course-details.php?id=<?php echo $c['course_id']; ?>" class="btn btn-outline-primary">Manage</a>
                                            <a href="<?php echo BASE_URL; ?>/teacher/create-assignment.php?course_id=<?php echo $c['course_id']; ?>" class="btn btn-outline-success">Add Assignment</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="<?php echo BASE_URL; ?>/teacher/create-course.php" class="list-group-item list-group-item-action d-flex gap-3 py-3">
                        <div class="d-flex gap-2 w-100 justify-content-between">
                            <div>
                                <h6 class="mb-0">Create a New Course</h6>
                                <p class="mb-0 opacity-75">Set up a new course for your students</p>
                            </div>
                        </div>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/teacher/assignments.php" class="list-group-item list-group-item-action d-flex gap-3 py-3">
                        <div class="d-flex gap-2 w-100 justify-content-between">
                            <div>
                                <h6 class="mb-0">Manage Assignments</h6>
                                <p class="mb-0 opacity-75">Create and grade assignments</p>
                            </div>
                        </div>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/teacher/grades.php" class="list-group-item list-group-item-action d-flex gap-3 py-3">
                        <div class="d-flex gap-2 w-100 justify-content-between">
                            <div>
                                <h6 class="mb-0">Grade Book</h6>
                                <p class="mb-0 opacity-75">Review and update student grades</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>