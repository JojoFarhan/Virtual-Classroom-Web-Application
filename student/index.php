
<?php
// student/index.php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../classes/User.php';
require_once '../classes/Course.php';
require_once '../classes/Assignment.php';

// Require login
requireLogin();

// Load user data
$user = new User($pdo);
$user->getUserById($_SESSION['user_id']);

// Load upcoming assignments
$assignment = new Assignment($pdo);
$upcoming_assignments = $assignment->getAssignmentsByUser($_SESSION['user_id']);

// Load enrolled courses
$course = new Course($pdo);
$enrolled_courses = $course->getCoursesByUser($_SESSION['user_id']);

$pageTitle = "Student Dashboard";
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Welcome, <?php echo htmlspecialchars($user->getFirstName()); ?>!</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Upcoming Assignments</h5>
                <a href="<?php echo BASE_URL; ?>/student/assignments.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_assignments)): ?>
                    <p class="text-muted">You have no upcoming assignments.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Assignment</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $count = 0;
                                foreach ($upcoming_assignments as $assignment): 
                                    if ($count >= 5) break; // Limit to 5 assignments
                                    $count++;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assignment['course_name']); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/student/assignment-details.php?id=<?php echo $assignment['assignment_id']; ?>">
                                            <?php echo htmlspecialchars($assignment['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo formatDate($assignment['due_date']); ?></td>
                                    <td>
                                        <?php if (isset($assignment['submission_id'])): ?>
                                            <span class="badge bg-success">Submitted</span>
                                        <?php elseif (strtotime($assignment['due_date']) < time()): ?>
                                            <span class="badge bg-danger">Overdue</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php endif; ?>
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">My Courses</h5>
                <a href="<?php echo BASE_URL; ?>/student/courses.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($enrolled_courses)): ?>
                    <p class="text-muted">You are not enrolled in any courses.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php 
                        $count = 0;
                        foreach ($enrolled_courses as $course): 
                            if ($count >= 5) break; // Limit to 5 courses
                            $count++;
                        ?>
                        <a href="<?php echo BASE_URL; ?>/student/course-details.php?id=<?php echo $course['course_id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?php echo htmlspecialchars($course['course_name']); ?></h5>
                                <small><?php echo htmlspecialchars($course['course_code']); ?></small>
                            </div>
                            <p class="mb-1"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . (strlen($course['description']) > 100 ? '...' : ''); ?></p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>