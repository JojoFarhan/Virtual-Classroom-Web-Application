<?php
// classes/Assignment.php
class Assignment {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function createAssignment($course_id, $title, $description, $points, $due_date, $allow_late) {
        $stmt = $this->pdo->prepare("
            INSERT INTO assignments (course_id, title, description, points_possible, due_date, allow_late_submission)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $course_id, $title, $description, $points, $due_date, $allow_late
        ]);
        
        if ($result) {
            return ['success' => true, 'assignment_id' => $this->pdo->lastInsertId()];
        } else {
            return ['success' => false, 'message' => 'Failed to create assignment'];
        }
    }
    
    public function getAssignmentById($assignment_id) {
        $stmt = $this->pdo->prepare("
            SELECT a.*, c.course_name
            FROM assignments a
            JOIN courses c ON a.course_id = c.course_id
            WHERE a.assignment_id = ?
        ");
        $stmt->execute([$assignment_id]);
        return $stmt->fetch();
    }
    
    public function getAssignmentsByCourse($course_id) {
        $stmt = $this->pdo->prepare("
            SELECT * 
            FROM assignments
            WHERE course_id = ?
            ORDER BY due_date ASC
        ");
        $stmt->execute([$course_id]);
        return $stmt->fetchAll();
    }
    
    public function getAssignmentsByUser($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT a.*, c.course_name, s.submission_id, s.submitted_at, s.score, s.submission_status
            FROM assignments a
            JOIN courses c ON a.course_id = c.course_id
            JOIN enrollments e ON c.course_id = e.course_id AND e.user_id = ?
            LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.user_id = ?
            WHERE e.enrollment_status = 'active'
            ORDER BY a.due_date ASC
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll();
    }
    
    public function getSubmissionStatus($assignment_id, $user_id) {
        $stmt = $this->pdo->prepare("
            SELECT submission_id, submitted_at, score, submission_status
            FROM submissions
            WHERE assignment_id = ? AND user_id = ?
        ");
        $stmt->execute([$assignment_id, $user_id]);
        return $stmt->fetch();
    }
} ?>