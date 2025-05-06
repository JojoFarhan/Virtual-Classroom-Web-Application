<?php
// student/discussions.php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require student role
if (!hasRole('student')) {
    header('Location: ../login.php?error=unauthorized');
    exit;
}

// Get enrolled courses
$stmt = $pdo->prepare("
    SELECT c.course_id, c.course_name, c.course_code
    FROM courses c
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE e.user_id = ? AND e.enrollment_status = 'active'
    ORDER BY c.course_name
");
$stmt->execute([$_SESSION['user_id']]);
$enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle course filter
$selected_course = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Get discussions
$discussions_query = "
    SELECT d.discussion_id, d.title, d.description, d.created_at, 
           c.course_name, c.course_code, 
           u.first_name, u.last_name,
           COUNT(DISTINCT com.comment_id) as comment_count
    FROM discussions d
    JOIN courses c ON d.course_id = c.course_id
    JOIN users u ON d.creator_id = u.user_id
    LEFT JOIN comments com ON d.discussion_id = com.discussion_id
    WHERE c.course_id IN (
        SELECT e.course_id FROM enrollments e WHERE e.user_id = ? AND e.enrollment_status = 'active'
    )
";

// Add course filter if selected
if ($selected_course > 0) {
    $discussions_query .= " AND c.course_id = ?";
    $params = [$_SESSION['user_id'], $selected_course];
} else {
    $params = [$_SESSION['user_id']];
}

$discussions_query .= " GROUP BY d.discussion_id ORDER BY d.created_at DESC";
$stmt = $pdo->prepare($discussions_query);
$stmt->execute($params);
$discussions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle single discussion view
$view_discussion = isset($_GET['id']) ? intval($_GET['id']) : 0;
$single_discussion = null;
$comments = [];

if ($view_discussion > 0) {
    // Get discussion details
    $stmt = $pdo->prepare("
        SELECT d.discussion_id, d.title, d.description, d.created_at, 
               c.course_id, c.course_name, c.course_code, 
               u.user_id, u.first_name, u.last_name
        FROM discussions d
        JOIN courses c ON d.course_id = c.course_id
        JOIN users u ON d.creator_id = u.user_id
        WHERE d.discussion_id = ?
    ");
    $stmt->execute([$view_discussion]);
    $single_discussion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($single_discussion) {
        // Check if user is enrolled in this course
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM enrollments 
            WHERE user_id = ? AND course_id = ? AND enrollment_status = 'active'
        ");
        $stmt->execute([$_SESSION['user_id'], $single_discussion['course_id']]);
        $is_enrolled = $stmt->fetchColumn();
        
        if (!$is_enrolled) {
            // Redirect if not enrolled
            header('Location: discussions.php?error=unauthorized');
            exit;
        }
        
        // Get comments for this discussion
        $stmt = $pdo->prepare("
            SELECT c.comment_id, c.parent_id, c.content, c.posted_at,
                   u.user_id, u.first_name, u.last_name, u.username
            FROM comments c
            JOIN users u ON c.user_id = u.user_id
            WHERE c.discussion_id = ?
            ORDER BY c.parent_id ASC, c.posted_at ASC
        ");
        $stmt->execute([$view_discussion]);
        $all_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize comments as a tree
        $comments = [];
        $comment_map = [];
        
        foreach ($all_comments as $comment) {
            $comment['replies'] = [];
            $comment_map[$comment['comment_id']] = count($comments);
            
            if ($comment['parent_id'] === null) {
                $comments[] = $comment;
            } else {
                foreach ($comments as &$parent_comment) {
                    if ($parent_comment['comment_id'] == $comment['parent_id']) {
                        $parent_comment['replies'][] = $comment;
                        break;
                    }
                }
            }
        }
        
        // Handle new comment submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'comment') {
            $content = isset($_POST['content']) ? sanitizeInput($_POST['content']) : '';
            $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
            
            if (!empty($content)) {
                $stmt = $pdo->prepare("
                    INSERT INTO comments (parent_id, user_id, discussion_id, content)
                    VALUES (?, ?, ?, ?)
                ");
                
                $parent_id = $parent_id > 0 ? $parent_id : null;
                $stmt->execute([$parent_id, $_SESSION['user_id'], $view_discussion, $content]);
                
                // Redirect to avoid form resubmission
                header("Location: discussions.php?id=$view_discussion");
                exit;
            }
        }
    } else {
        // Invalid discussion ID
        header('Location: discussions.php?error=not_found');
        exit;
    }
}

$pageTitle = $single_discussion ? "Discussion: " . $single_discussion['title'] : "Discussions";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <?php if (!$single_discussion): ?>
                    <li class="breadcrumb-item active">Discussions</li>
                    <?php else: ?>
                    <li class="breadcrumb-item"><a href="discussions.php">Discussions</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($single_discussion['title']); ?></li>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!$single_discussion): ?>
    <!-- Discussions List View -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Discussions</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form method="get" class="form-inline">
                                <div class="form-group mr-2">
                                    <label for="course_id" class="mr-2">Filter by Course:</label>
                                    <select name="course_id" id="course_id" class="form-select" onchange="this.form.submit()">
                                        <option value="0">All Courses</option>
                                        <?php foreach ($enrolled_courses as $course): ?>
                                        <option value="<?php echo $course['course_id']; ?>" <?php echo $selected_course == $course['course_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php if (empty($discussions)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No discussions found.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Course</th>
                                    <th>Created By</th>
                                    <th>Date</th>
                                    <th>Comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($discussions as $discussion): ?>
                                <tr>
                                    <td>
                                        <a href="discussions.php?id=<?php echo $discussion['discussion_id']; ?>">
                                            <?php echo htmlspecialchars($discussion['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($discussion['course_code']); ?></td>
                                    <td><?php echo htmlspecialchars($discussion['first_name'] . ' ' . $discussion['last_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($discussion['created_at'])); ?></td>
                                    <td><?php echo $discussion['comment_count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Single Discussion View -->
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?php echo htmlspecialchars($single_discussion['title']); ?>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($single_discussion['course_code']); ?></span>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="text-muted">
                            Posted by <?php echo htmlspecialchars($single_discussion['first_name'] . ' ' . $single_discussion['last_name']); ?> on
                            <?php echo date('M j, Y \a\t g:i A', strtotime($single_discussion['created_at'])); ?>
                        </p>
                    </div>
                    <div class="mb-4">
                        <?php echo nl2br(htmlspecialchars($single_discussion['description'])); ?>
                    </div>
                    
                    <hr />
                    
                    <h5><?php echo count($comments) > 0 ? 'Responses' : 'No responses yet'; ?></h5>
                    
                    <!-- Display comments -->
                    <div class="comments-section mt-4">
                        <?php foreach ($comments as $comment): ?>
                        <div class="comment-card mb-3">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <strong><?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?></strong> 
                                    <span class="text-muted"><?php echo date('M j, Y g:i A', strtotime($comment['posted_at'])); ?></span>
                                </div>
                                <div class="card-body">
                                    <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                    
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-primary reply-toggle" data-parent="<?php echo $comment['comment_id']; ?>">
                                            <i class="fas fa-reply"></i> Reply
                                        </button>
                                    </div>
                                    
                                    <!-- Reply form (hidden by default) -->
                                    <div class="reply-form mt-3" id="reply-form-<?php echo $comment['comment_id']; ?>" style="display: none;">
                                        <form method="post">
                                            <input type="hidden" name="action" value="comment">
                                            <input type="hidden" name="parent_id" value="<?php echo $comment['comment_id']; ?>">
                                            <div class="form-group">
                                                <textarea name="content" class="form-control" rows="3" required></textarea>
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-primary btn-sm">Submit Reply</button>
                                                <button type="button" class="btn btn-secondary btn-sm cancel-reply" data-parent="<?php echo $comment['comment_id']; ?>">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <!-- Replies -->
                                    <?php if (!empty($comment['replies'])): ?>
                                    <div class="replies mt-3 ml-4 border-left pl-3">
                                        <?php foreach ($comment['replies'] as $reply): ?>
                                        <div class="reply mb-3">
                                            <div class="card">
                                                <div class="card-header bg-light">
                                                    <strong><?php echo htmlspecialchars($reply['first_name'] . ' ' . $reply['last_name']); ?></strong>
                                                    <span class="text-muted"><?php echo date('M j, Y g:i A', strtotime($reply['posted_at'])); ?></span>
                                                </div>
                                                <div class="card-body">
                                                    <p><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Add new comment form -->
                    <div class="add-comment mt-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Add Your Response</h6>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="action" value="comment">
                                    <div class="form-group">
                                        <textarea name="content" class="form-control" rows="4" placeholder="Add your thoughts to the discussion..." required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">Submit Response</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- JavaScript for reply buttons -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/hide reply forms
    const replyToggles = document.querySelectorAll('.reply-toggle');
    replyToggles.forEach(button => {
        button.addEventListener('click', function() {
            const parentId = this.getAttribute('data-parent');
            const replyForm = document.getElementById('reply-form-' + parentId);
            replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
        });
    });
    
    // Cancel reply
    const cancelButtons = document.querySelectorAll('.cancel-reply');
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            const parentId = this.getAttribute('data-parent');
            const replyForm = document.getElementById('reply-form-' + parentId);
            replyForm.style.display = 'none';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>