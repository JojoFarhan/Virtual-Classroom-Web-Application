<?php
// admin/reports.php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require admin role
requireRole('admin');

// Handle report type
$report_type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'user_activity';
$date_range = isset($_GET['date_range']) ? sanitizeInput($_GET['date_range']) : 'week'; // week, month, year

// Initialize data arrays
$report_data = [];
$chart_data = [];

// Generate report based on type
switch ($report_type) {
    case 'user_activity':
        generateUserActivityReport($pdo, $date_range, $report_data, $chart_data);
        break;
    
    case 'course_enrollment':
        generateCourseEnrollmentReport($pdo, $report_data, $chart_data);
        break;
    
    case 'assignment_completion':
        generateAssignmentCompletionReport($pdo, $report_data, $chart_data);
        break;
    
    case 'user_roles':
        generateUserRolesReport($pdo, $report_data, $chart_data);
        break;
}

// Function to generate user activity report
function generateUserActivityReport($pdo, $date_range, &$report_data, &$chart_data) {
    // Set date range for query
    switch ($date_range) {
        case 'week':
            $date_limit = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'month':
            $date_limit = date('Y-m-d', strtotime('-30 days'));
            break;
        case 'year':
            $date_limit = date('Y-m-d', strtotime('-365 days'));
            break;
        default:
            $date_limit = date('Y-m-d', strtotime('-7 days'));
    }

    // Query for logins per day
    $stmt = $pdo->prepare("
        SELECT DATE(last_login) as login_date, COUNT(*) as login_count
        FROM users
        WHERE last_login >= ?
        GROUP BY DATE(last_login)
        ORDER BY login_date
    ");
    $stmt->execute([$date_limit]);
    
    $login_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Query for new user registrations per day
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as register_date, COUNT(*) as register_count
        FROM users
        WHERE created_at >= ?
        GROUP BY DATE(created_at)
        ORDER BY register_date
    ");
    $stmt->execute([$date_limit]);
    
    $registration_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare data for chart
    $dates = [];
    $logins = [];
    $registrations = [];
    
    // Get all dates in range
    $current = new DateTime($date_limit);
    $now = new DateTime();
    while ($current <= $now) {
        $date_str = $current->format('Y-m-d');
        $dates[] = $date_str;
        $logins[$date_str] = 0;
        $registrations[$date_str] = 0;
        $current->modify('+1 day');
    }
    
    // Fill in actual login data
    foreach ($login_data as $item) {
        if (isset($logins[$item['login_date']])) {
            $logins[$item['login_date']] = (int)$item['login_count'];
        }
    }
    
    // Fill in actual registration data
    foreach ($registration_data as $item) {
        if (isset($registrations[$item['register_date']])) {
            $registrations[$item['register_date']] = (int)$item['register_count'];
        }
    }
    
    // Format dates for display
    $display_dates = array_map(function($date) {
        return date('M j', strtotime($date));
    }, $dates);
    
    // Prepare chart data
    $chart_data = [
        'labels' => $display_dates,
        'datasets' => [
            [
                'label' => 'Logins',
                'data' => array_values($logins),
                'borderColor' => 'rgba(54, 162, 235, 1)',
                'backgroundColor' => 'rgba(54, 162, 235, 0.2)'
            ],
            [
                'label' => 'New Registrations',
                'data' => array_values($registrations),
                'borderColor' => 'rgba(255, 99, 132, 1)',
                'backgroundColor' => 'rgba(255, 99, 132, 0.2)'
            ]
        ]
    ];
    
    // Get recent login activity
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.first_name, u.last_name, u.last_login
        FROM users u
        WHERE u.last_login IS NOT NULL
        ORDER BY u.last_login DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_logins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as user_count FROM users");
    $stmt->execute();
    $user_count = $stmt->fetch(PDO::FETCH_ASSOC)['user_count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_users FROM users WHERE last_login >= ?");
    $stmt->execute([$date_limit]);
    $active_users = $stmt->fetch(PDO::FETCH_ASSOC)['active_users'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as new_users FROM users WHERE created_at >= ?");
    $stmt->execute([$date_limit]);
    $new_users = $stmt->fetch(PDO::FETCH_ASSOC)['new_users'];
    
    // Prepare report data
    $report_data = [
        'summary' => [
            'total_users' => $user_count,
            'active_users' => $active_users,
            'new_users' => $new_users,
            'inactive_percentage' => ($user_count > 0) ? round(100 - ($active_users / $user_count * 100), 1) : 0
        ],
        'recent_logins' => $recent_logins
    ];
}

// Function to generate course enrollment report
function generateCourseEnrollmentReport($pdo, &$report_data, &$chart_data) {
    // Get top courses by enrollment
    $stmt = $pdo->query("
        SELECT c.course_id, c.course_name, c.course_code,
               COUNT(e.enrollment_id) as enrollment_count
        FROM courses c
        LEFT JOIN enrollments e ON c.course_id = e.course_id
        WHERE e.enrollment_status = 'active'
        GROUP BY c.course_id
        ORDER BY enrollment_count DESC
        LIMIT 10
    ");
    
    $top_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare chart data
    $chart_data = [
        'labels' => array_map(function($course) {
            return $course['course_code'];
        }, $top_courses),
        'datasets' => [
            [
                'label' => 'Active Enrollments',
                'data' => array_map(function($course) {
                    return $course['enrollment_count'];
                }, $top_courses),
                'backgroundColor' => [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)',
                    'rgba(199, 199, 199, 0.6)',
                    'rgba(83, 102, 255, 0.6)',
                    'rgba(40, 159, 64, 0.6)',
                    'rgba(210, 199, 199, 0.6)'
                ]
            ]
        ]
    ];
    
    // Get enrollment statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT course_id) as total_courses,
            COUNT(DISTINCT user_id) as enrolled_users,
            COUNT(*) as total_enrollments,
            AVG(t.course_count) as avg_courses_per_student
        FROM enrollments e,
        (SELECT user_id, COUNT(*) as course_count
         FROM enrollments
         WHERE enrollment_type = 'student'
         GROUP BY user_id) t
        WHERE e.enrollment_status = 'active'
    ");
    
    $enrollment_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get enrollment counts by role
    $stmt = $pdo->query("
        SELECT enrollment_type, COUNT(*) as count
        FROM enrollments
        WHERE enrollment_status = 'active'
        GROUP BY enrollment_type
    ");
    
    $enrollment_by_role = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare report data
    $report_data = [
        'summary' => $enrollment_stats,
        'top_courses' => $top_courses,
        'enrollment_by_role' => $enrollment_by_role
    ];
}

// Function to generate assignment completion report
function generateAssignmentCompletionReport($pdo, &$report_data, &$chart_data) {
    // Get assignment completion rates
    $stmt = $pdo->query("
        SELECT c.course_name, c.course_code, a.title as assignment_title,
               COUNT(DISTINCT e.user_id) as total_students,
               COUNT(s.submission_id) as submission_count,
               (COUNT(s.submission_id) * 100.0 / COUNT(DISTINCT e.user_id)) as completion_rate
        FROM assignments a
        JOIN courses c ON a.course_id = c.course_id
        JOIN enrollments e ON c.course_id = e.course_id
        LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND e.user_id = s.user_id
        WHERE e.enrollment_type = 'student' AND e.enrollment_status = 'active'
        GROUP BY a.assignment_id
        ORDER BY completion_rate DESC
        LIMIT 10
    ");
    
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare chart data
    $chart_data = [
        'labels' => array_map(function($assignment) {
            return $assignment['course_code'] . ': ' . mb_substr($assignment['assignment_title'], 0, 15) . (mb_strlen($assignment['assignment_title']) > 15 ? '...' : '');
        }, $assignments),
        'datasets' => [
            [
                'label' => 'Completion Rate (%)',
                'data' => array_map(function($assignment) {
                    return round($assignment['completion_rate'], 1);
                }, $assignments),
                'backgroundColor' => 'rgba(75, 192, 192, 0.6)',
                'borderColor' => 'rgba(75, 192, 192, 1)',
                'borderWidth' => 1
            ]
        ]
    ];
    
    // Get overall assignment statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(a.assignment_id) as total_assignments,
            COUNT(s.submission_id) as total_submissions,
            AVG(CASE WHEN s.score IS NOT NULL THEN s.score ELSE NULL END) as avg_score,
            COUNT(CASE WHEN s.submission_status = 'graded' THEN s.submission_id ELSE NULL END) as graded_submissions
        FROM assignments a
        LEFT JOIN submissions s ON a.assignment_id = s.assignment_id
    ");
    
    $assignment_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get late submission counts
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN s.submitted_at <= a.due_date THEN s.submission_id ELSE NULL END) as on_time_submissions,
            COUNT(CASE WHEN s.submitted_at > a.due_date THEN s.submission_id ELSE NULL END) as late_submissions
        FROM submissions s
        JOIN assignments a ON s.assignment_id = a.assignment_id
    ");
    
    $submission_timing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Prepare report data
    $report_data = [
        'summary' => array_merge($assignment_stats, $submission_timing),
        'assignments' => $assignments
    ];
}

// Function to generate user roles report
function generateUserRolesReport($pdo, &$report_data, &$chart_data) {
    // Get user counts by role
    $stmt = $pdo->query("
        SELECT r.role_name, COUNT(ur.user_id) as user_count
        FROM roles r
        LEFT JOIN user_roles ur ON r.role_id = ur.role_id
        GROUP BY r.role_id
        ORDER BY user_count DESC
    ");
    
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare chart data
    $chart_data = [
        'labels' => array_map(function($role) {
            return ucfirst($role['role_name']);
        }, $roles),
        'datasets' => [
            [
                'label' => 'Users',
                'data' => array_map(function($role) {
                    return $role['user_count'];
                }, $roles),
                'backgroundColor' => [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)'
                ]
            ]
        ]
    ];
    
    // Get users with multiple roles
    $stmt = $pdo->query("
        SELECT u.user_id, u.username, u.first_name, u.last_name, 
               COUNT(ur.role_id) as role_count,
               GROUP_CONCAT(r.role_name SEPARATOR ', ') as roles
        FROM users u
        JOIN user_roles ur ON u.user_id = ur.user_id
        JOIN roles r ON ur.role_id = r.role_id
        GROUP BY u.user_id
        HAVING role_count > 1
        ORDER BY role_count DESC
        LIMIT 10
    ");
    
    $multi_role_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get role statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT u.user_id) as total_users,
            COUNT(DISTINCT CASE WHEN ur.role_id IS NOT NULL THEN u.user_id ELSE NULL END) as users_with_roles,
            AVG(t.role_count) as avg_roles_per_user
        FROM users u
        LEFT JOIN user_roles ur ON u.user_id = ur.user_id,
        (SELECT user_id, COUNT(*) as role_count
         FROM user_roles
         GROUP BY user_id) t
    ");
    
    $role_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Prepare report data
    $report_data = [
        'summary' => $role_stats,
        'roles' => $roles,
        'multi_role_users' => $multi_role_users
    ];
}

$pageTitle = "System Reports";
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">System Reports</h1>
    </div>
</div>

<!-- Report Type Navigation -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Report Selection</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <ul class="nav nav-pills mb-3">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $report_type === 'user_activity' ? 'active' : ''; ?>" href="?type=user_activity">
                            <i class="fas fa-users"></i> User Activity
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $report_type === 'course_enrollment' ? 'active' : ''; ?>" href="?type=course_enrollment">
                            <i class="fas fa-graduation-cap"></i> Course Enrollment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $report_type === 'assignment_completion' ? 'active' : ''; ?>" href="?type=assignment_completion">
                            <i class="fas fa-tasks"></i> Assignment Completion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $report_type === 'user_roles' ? 'active' : ''; ?>" href="?type=user_roles">
                            <i class="fas fa-user-tag"></i> User Roles
                        </a>
                    </li>
                </ul>
                
                <?php if ($report_type === 'user_activity'): ?>
                <div class="mb-3">
                    <form method="get" class="form-inline">
                        <input type="hidden" name="type" value="user_activity">
                        <div class="form-group">
                            <label for="date_range" class="mr-2">Date Range:</label>
                            <select class="form-select" id="date_range" name="date_range" onchange="this.form.submit()">
                                <option value="week" <?php echo $date_range === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="month" <?php echo $date_range === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                                <option value="year" <?php echo $date_range === 'year' ? 'selected' : ''; ?>>Last Year</option>
                            </select>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Report Content -->
<?php if ($report_type === 'user_activity'): ?>
<div class="row">
    <!-- Summary Statistics -->
    <div class="col-md-12 mb-4">
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($report_data['summary']['total_users']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($report_data['summary']['active_users']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">New Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($report_data['summary']['new_users']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Inactive Rate</div>
                                <div class="row no-gutters align-items-center">
                                    <div class="col-auto">
                                        <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $report_data['summary']['inactive_percentage']; ?>%</div>
                                    </div>
                                    <div class="col">
                                        <div class="progress progress-sm mr-2">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $report_data['summary']['inactive_percentage']; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Chart -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">User Activity Trends</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Logins -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent User Activity</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Last Login</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['recent_logins'] as $login): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($login['first_name'] . ' ' . $login['last_name']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($login['last_login'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($report_type === 'course_enrollment'): ?>
<div class="row">
    <!-- Summary Statistics -->
    <div class="col-md-12 mb-4">
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Courses</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($report_data['summary']['total_courses']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-book fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Enrolled Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($report_data['summary']['enrolled_users']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Enrollments</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($report_data['summary']['total_enrollments']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Avg. Courses per Student</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($report_data['summary']['avg_courses_per_student'], 1); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Chart -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Top Courses by Enrollment</h6>
            </div>
            <div class="card-body">
                <div class="chart-bar">
                    <canvas id="enrollmentChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrollment by Role -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Enrollment by Role</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['enrollment_by_role'] as $role): ?>
                            <tr>
                                <td><?php echo ucfirst(htmlspecialchars($role['enrollment_type'])); ?></td>
                                <td><?php echo number_format($role['count']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Course Table -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Top Courses Details</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Enrollment Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['top_courses'] as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                <td><?php echo number_format($course['enrollment_count']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($report_type === 'assignment_completion'): ?>
<div class="row">
    <!-- Summary Statistics -->
    <div class="col-md-12 mb-4">
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Assignments</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($report_data['summary']['total_assignments']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-tasks fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Submissions</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($report_data['summary']['total_submissions']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Average Score</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($report_data['summary']['avg_score'], 1); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">On-Time vs Late</div>
                                <div class="row no-gutters align-items-center">
                                    <div class="col-auto">
                                        <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                            <?php 
                                                $total = $report_data['summary']['on_time_submissions'] + $report_data['summary']['late_submissions'];
                                                $on_time_percentage = ($total > 0) ? round(($report_data['summary']['on_time_submissions'] / $total) * 100, 1) : 0;
                                                echo $on_time_percentage . '%';
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="progress progress-sm mr-2">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $on_time_percentage; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Chart -->
    <div class="col-xl-12 col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Assignment Completion Rates</h6>
            </div>
            <div class="card-body">
                <div class="chart-bar">
                    <canvas id="completionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assignment Table -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Assignment Completion Details</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Assignment</th>
                                <th>Total Students</th>
                                <th>Submissions</th>
                                <th>Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['assignments'] as $assignment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($assignment['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['assignment_title']); ?></td>
                                <td><?php echo number_format($assignment['total_students']); ?></td>
                                <td><?php echo number_format($assignment['submission_count']); ?></td>
                                <td><?php echo number_format($assignment['completion_rate'], 1) . '%'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($report_type === 'user_roles'): ?>
<div class="row">
    <!-- Summary Statistics -->
    <div class="col-md-12 mb-4">
        <div class="row">
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($report_data['summary']['total_users']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Users with Roles</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($report_data['summary']['users_with_roles']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-tag fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Avg. Roles per User</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($report_data['summary']['avg_roles_per_user'], 1); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-cog fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Chart -->
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Users by Role</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie">
                    <canvas id="rolesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Users with Multiple Roles -->
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Users with Multiple Roles</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Role Count</th>
                                <th>Roles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['multi_role_users'] as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo $user['role_count']; ?></td>
                                <td><?php echo htmlspecialchars($user['roles']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Roles Table -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Role Distribution</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>User Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_users_with_roles = 0;
                            foreach ($report_data['roles'] as $role) {
                                $total_users_with_roles += $role['user_count'];
                            }
                            foreach ($report_data['roles'] as $role): 
                                $percentage = ($total_users_with_roles > 0) ? round(($role['user_count'] / $total_users_with_roles) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo ucfirst(htmlspecialchars($role['role_name'])); ?></td>
                                <td><?php echo number_format($role['user_count']); ?></td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%;"><?php echo $percentage; ?>%</div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Include chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Initialize Chart
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($report_type === 'user_activity'): ?>
    // Activity Chart
    const activityCtx = document.getElementById('activityChart').getContext('2d');
    const activityChart = new Chart(activityCtx, {
        type: 'line',
        data: <?php echo json_encode($chart_data); ?>,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    <?php elseif ($report_type === 'course_enrollment'): ?>
    // Enrollment Chart
    const enrollmentCtx = document.getElementById('enrollmentChart').getContext('2d');
    const enrollmentChart = new Chart(enrollmentCtx, {
        type: 'bar',
        data: <?php echo json_encode($chart_data); ?>,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    <?php elseif ($report_type === 'assignment_completion'): ?>
    // Completion Chart
    const completionCtx = document.getElementById('completionChart').getContext('2d');
    const completionChart = new Chart(completionCtx, {
        type: 'bar',
        data: <?php echo json_encode($chart_data); ?>,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    <?php elseif ($report_type === 'user_roles'): ?>
    // Roles Chart
    const rolesCtx = document.getElementById('rolesChart').getContext('2d');
    const rolesChart = new Chart(rolesCtx, {
        type: 'pie',
        data: <?php echo json_encode($chart_data); ?>,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php
include '../includes/footer.php';
?>