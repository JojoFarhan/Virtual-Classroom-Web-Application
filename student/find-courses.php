<?php
// student/enroll.php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../classes/Course.php';

// Require student role
requireRole('student');

$error = '';
$success = '';
$course = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    $enrollment_code = isset($_POST['enrollment_code']) ? sanitizeInput($_POST['enrollment_code']) : '';
    
    if ($course_id <= 0) {
        $error = "Invalid course ID";
    } else {
        // Check if course exists
        $course_model = new Course($pdo);
        $course = $course_model->getCourseById($course_id);
        
        if (!$course) {
            $error = "Course not found";
        } elseif ($course['is_archived']) {
            $error = "This course is archived and not available for enrollment";
        } else {
            // Check if enrollment code is required and valid
            if (!empty($course['enrollment_code']) && $course['enrollment_code'] !== $enrollment_code) {
                $error = "Invalid enrollment code";
            } else {
                // Check if already enrolled
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM enrollments 
                    WHERE user_id = ? AND course_id = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $course_id]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = "You are already enrolled in this course";
                } else {
                    // Process enrollment
                    $stmt = $pdo->prepare("
                        INSERT INTO enrollments (user_id, course_id, enrollment_type, enrollment_status)
                        VALUES (?, ?, 'student', 'active')
                    ");
                    
                    $result = $stmt->execute([$_SESSION['user_id'], $course_id]);
                    
                    if ($result) {
                        // Log enrollment activity
                        $stmt = $pdo->prepare("
                            INSERT INTO activity_logs (user_id, activity_type, related_id, details)
                            VALUES (?, 'enrollment', ?, ?)
                        ");
                        $details = "Enrolled in course: " . $course['course_name'];
                        $stmt->execute([$_SESSION['user_id'], $course_id, $details]);
                        
                        redirectWithMessage(BASE_URL . "/student/courses.php", "Successfully enrolled in " . $course['course_name']);
                    } else {
                        $error = "Failed to enroll in the course";
                    }
                }
            }
        }
    }
}

// Handle course ID from GET request (for pre-filling the form)
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($course_id > 0) {
    $course_model = new Course($pdo);
    $course = $course_model->getCourseById($course_id);
    
    if (!$course) {
        $error = "Course not found";
    }
}

$pageTitle = "Course Enrollment";
include '../includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Course Enrollment</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Enroll in a Course</h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="course_id" class="form-label">Course ID</label>
                            <input type="number" class="form-control" id="course_id" name="course_id" 
                                value="<?php echo $course ? $course['course_id'] : ''; ?>" required>
                            <div class="form-text">Enter the ID of the course you want to enroll in</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="enrollment_code" class="form-label">Enrollment Code (if required)</label>
                            <input type="text" class="form-control" id="enrollment_code" name="enrollment_code">
                            <div class="form-text">Some courses require an enrollment code provided by the instructor</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Enroll in Course</button>
                            <a href="<?php echo BASE_URL; ?>/student/courses.php" class="btn btn-secondary">Back to My Courses</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Browse Available Courses</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Course Name</th>
                                    <th>Code</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get available courses (not archived)
                                $stmt = $pdo->prepare("
                                    SELECT c.course_id, c.course_name, c.course_code 
                                    FROM courses c
                                    WHERE c.is_archived = 0
                                    AND c.course_id NOT IN (
                                        SELECT e.course_id 
                                        FROM enrollments e 
                                        WHERE e.user_id = ?
                                    )
                                    ORDER BY c.course_name
                                    LIMIT 10
                                ");
                                $stmt->execute([$_SESSION['user_id']]);
                                $available_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($available_courses as $c):
                                ?>
                                <tr>
                                    <td><?php echo $c['course_id']; ?></td>
                                    <td><?php echo htmlspecialchars($c['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($c['course_code']); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/student/find-courses.php?id=<?php echo $c['course_id']; ?>" class="btn btn-sm btn-primary">
                                            Select
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($available_courses)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No available courses found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <a href="<?php echo BASE_URL; ?>/student/search-courses.php" class="btn btn-outline-primary btn-sm">View All Available Courses</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>