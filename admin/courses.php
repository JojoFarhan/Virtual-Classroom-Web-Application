<?php
// admin/courses.php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../classes/Course.php';

// Require admin role
requireRole('admin');

// Load course data
$course_model = new Course($pdo);

// Handle actions
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'edit':
            // Update course
            $course_name = sanitizeInput($_POST['course_name']);
            $course_code = sanitizeInput($_POST['course_code']);
            $description = sanitizeInput($_POST['description']);
            $is_archived = isset($_POST['is_archived']) ? 1 : 0;
            
            if (empty($course_name) || empty($course_code)) {
                $error = "Course name and code are required";
            } else {
                $result = $course_model->updateCourse($course_id, $course_name, $course_code, $description, $is_archived);
                
                if ($result['success']) {
                    $success = "Course updated successfully!";
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'delete':
            // Delete course
            $confirm = isset($_POST['confirm']) ? true : false;
            
            if ($confirm) {
                $result = $course_model->deleteCourse($course_id);
                
                if ($result['success']) {
                    redirectWithMessage(BASE_URL . "/admin/courses.php", "Course deleted successfully!");
                } else {
                    $error = $result['message'];
                }
            } else {
                $error = "Please confirm deletion";
            }
            break;
    }
}

// Get all courses for list view
$courses = [];
if ($action === 'list') {
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $archive_filter = isset($_GET['archived']) ? (int)$_GET['archived'] : -1;
    
    $courses = $course_model->getCourses($search, $archive_filter);
}

// Get specific course for edit view
$edit_course = null;
if ($action === 'edit' || $action === 'delete') {
    $edit_course = $course_model->getCourseById($course_id);
    
    if (!$edit_course) {
        redirectWithMessage(BASE_URL . "/admin/courses.php", "Course not found!", "danger");
    }
}

$pageTitle = "Course Management";
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Course Management</h1>
        
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
        <h6 class="m-0 font-weight-bold text-primary">Course List</h6>
    </div>
    <div class="card-body">
        <!-- Search and Filter Form -->
        <form class="mb-4" method="get" action="">
            <div class="row g-3">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="search" placeholder="Search courses..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="archived">
                        <option value="-1" <?php echo !isset($_GET['archived']) || $_GET['archived'] == -1 ? 'selected' : ''; ?>>All Courses</option>
                        <option value="0" <?php echo isset($_GET['archived']) && $_GET['archived'] == 0 ? 'selected' : ''; ?>>Active Only</option>
                        <option value="1" <?php echo isset($_GET['archived']) && $_GET['archived'] == 1 ? 'selected' : ''; ?>>Archived Only</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>
    
        <!-- Courses Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Creator</th>
                        <th>Students</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $c): ?>
                    <tr>
                        <td><?php echo $c['course_id']; ?></td>
                        <td><?php echo htmlspecialchars($c['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($c['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($c['creator_name']); ?></td>
                        <td><?php echo $c['student_count']; ?></td>
                        <td><?php echo formatDate($c['created_at']); ?></td>
                        <td>
                            <?php if ($c['is_archived']): ?>
                                <span class="badge bg-secondary">Archived</span>
                            <?php else: ?>
                                <span class="badge bg-success">Active</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo BASE_URL; ?>/admin/courses.php?action=edit&id=<?php echo $c['course_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/admin/courses.php?action=delete&id=<?php echo $c['course_id']; ?>" class="btn btn-danger">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($courses)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No courses found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($action === 'edit'): ?>
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Edit Course: <?php echo htmlspecialchars($edit_course['course_name']); ?></h6>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo BASE_URL; ?>/admin/courses.php?action=edit&id=<?php echo $course_id; ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="course_name" class="form-label">Course Name</label>
                    <input type="text" class="form-control" id="course_name" name="course_name" value="<?php echo htmlspecialchars($edit_course['course_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="course_code" class="form-label">Course Code</label>
                    <input type="text" class="form-control" id="course_code" name="course_code" value="<?php echo htmlspecialchars($edit_course['course_code']); ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($edit_course['description']); ?></textarea>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_archived" name="is_archived" <?php echo $edit_course['is_archived'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="is_archived">Archive Course</label>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?php echo BASE_URL; ?>/admin/courses.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Course</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'delete'): ?>
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Delete Course: <?php echo htmlspecialchars($edit_course['course_name']); ?></h6>
    </div>
    <div class="card-body">
        <div class="alert alert-danger">
            <p><strong>Warning:</strong> You are about to delete this course. This action cannot be undone.</p>
            <p>All associated data including enrollments, assignments, submissions, and materials will be permanently removed.</p>
        </div>
        
        <form method="post" action="<?php echo BASE_URL; ?>/admin/courses.php?action=delete&id=<?php echo $course_id; ?>">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="confirm" name="confirm" required>
                <label class="form-check-label" for="confirm">
                    I confirm that I want to delete this course and all associated data.
                </label>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="<?php echo BASE_URL; ?>/admin/courses.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-danger">Delete Course</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>