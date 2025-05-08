<?php
// teacher/create-course.php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../classes/Course.php';

// Require teacher role
requireRole('teacher');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_name = sanitizeInput($_POST['course_name']);
    $course_code = sanitizeInput($_POST['course_code']);
    $description = sanitizeInput($_POST['description']);
    $creator_id = $_SESSION['user_id'];
    
    if (empty($course_name) || empty($course_code)) {
        $error = "Course name and code are required";
    } else {
        $course = new Course($pdo);
        $result = $course->createCourse($course_name, $course_code, $description, $creator_id);
        
        if ($result['success']) {
            redirectWithMessage(
                BASE_URL . "/teacher/course-details.php?id=" . $result['course_id'],
                "Course created successfully!"
            );
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = "Create Course";
include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h1 class="card-title">Create New Course</h1>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="course_name" class="form-label">Course Name</label>
                        <input type="text" class="form-control" id="course_name" name="course_name" required>
                        <div class="form-text">Enter a descriptive name for your course</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="course_code" class="form-label">Course Code</label>
                        <input type="text" class="form-control" id="course_code" name="course_code" required>
                        <div class="form-text">Enter a unique code for your course (e.g., CS101)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo BASE_URL; ?>/teacher/" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
