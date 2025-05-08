<?php
// teacher/create-assignment.php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../classes/Course.php';
require_once '../classes/Assignment.php';

// Require teacher role
requireRole('teacher');

// Check if course_id is provided
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    redirectWithMessage(BASE_URL . "/teacher/", "Invalid course ID", "danger");
}

$course_id = (int)$_GET['course_id'];

// Verify that the user is the teacher of this course
$course = new Course($pdo);
$course_data = $course->getCourseById($course_id);

if (!$course_data || $course_data['creator_id'] != $_SESSION['user_id']) {
    redirectWithMessage(BASE_URL . "/teacher/", "You don't have permission to add assignments to this course", "danger");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $points = isset($_POST['points']) ? (int)$_POST['points'] : 100;
    $due_date = sanitizeInput($_POST['due_date']);
    $allow_late = isset($_POST['allow_late']) ? 1 : 0;
    
    if (empty($title) || empty($due_date)) {
        $error = "Title and due date are required";
    } else {
        $assignment = new Assignment($pdo);
        $result = $assignment->createAssignment(
            $course_id, 
            $title, 
            $description, 
            $points,
            $due_date,
            $allow_late
        );
        
        if ($result['success']) {
            redirectWithMessage(
                BASE_URL . "/teacher/course-details.php?id=" . $course_id,
                "Assignment created successfully!"
            );
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = "Create Assignment";
include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h1 class="card-title">Create New Assignment</h1>
                <h6 class="card-subtitle mb-4 text-muted">For: <?php echo htmlspecialchars($course_data['course_name']); ?></h6>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="title" class="form-label">Assignment Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Instructions</label>
                        <textarea class="form-control" id="description" name="description" rows="5"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="points" class="form-label">Points</label>
                            <input type="number" class="form-control" id="points" name="points" value="100" min="0" max="1000">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="datetime-local" class="form-control" id="due_date" name="due_date" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="allow_late" name="allow_late">
                        <label class="form-check-label" for="allow_late">Allow Late Submissions</label>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo BASE_URL; ?>/teacher/course-details.php?id=<?php echo $course_id; ?>" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Assignment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>