<?php
// student/assignments.php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../classes/Assignment.php';

// Require login
requireLogin();

// Load assignments
$assignment = new Assignment($pdo);
$assignments = $assignment->getAssignmentsByUser($_SESSION['user_id']);

// Categorize assignments
$pending = [];
$submitted = [];
$graded = [];
$overdue = [];

foreach ($assignments as $a) {
    if (isset($a['submission_id'])) {
        if ($a['submission_status'] === 'graded' || $a['submission_status'] === 'returned') {
            $graded[] = $a;
        } else {
            $submitted[] = $a;
        }
    } elseif (strtotime($a['due_date']) < time()) {
        $overdue[] = $a;
    } else {
        $pending[] = $a;
    }
}

$pageTitle = "My Assignments";
include '../includes/header.php';
?>

<h1 class="mb-4">My Assignments</h1>

<ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
            Pending <span class="badge bg-secondary"><?php echo count($pending); ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="submitted-tab" data-bs-toggle="tab" data-bs-target="#submitted" type="button" role="tab">
            Submitted <span class="badge bg-secondary"><?php echo count($submitted); ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="graded-tab" data-bs-toggle="tab" data-bs-target="#graded" type="button" role="tab">
            Graded <span class="badge bg-secondary"><?php echo count($graded); ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="overdue-tab" data-bs-toggle="tab" data-bs-target="#overdue" type="button" role="tab">
            Overdue <span class="badge bg-secondary"><?php echo count($overdue); ?></span>
        </button>
    </li>
</ul>

<div class="tab-content" id="myTabContent">
    <!-- Pending Assignments -->
    <div class="tab-pane fade show active" id="pending" role="tabpanel">
        <?php if (empty($pending)): ?>
            <p class="text-muted">You have no pending assignments.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Assignment</th>
                            <th>Due Date</th>
                            <th>Points</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $a): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($a['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($a['title']); ?></td>
                            <td><?php echo formatDate($a['due_date']); ?></td>
                            <td><?php echo $a['points_possible']; ?></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/student/assignment-details.php?id=<?php echo $a['assignment_id']; ?>" class="btn btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Other tabs would follow the same pattern -->
    <div class="tab-pane fade" id="submitted" role="tabpanel">
        <!-- Submitted assignments table -->
    </div>
    
    <div class="tab-pane fade" id="graded" role="tabpanel">
        <!-- Graded assignments table -->
    </div>
    
    <div class="tab-pane fade" id="overdue" role="tabpanel">
        <!-- Overdue assignments table -->
    </div>
</div>

<?php include '../includes/footer.php'; ?>