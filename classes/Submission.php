<?php
// classes/Submission.php
class Submission {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new submission
     * @param int $assignment_id The assignment ID
     * @param int $user_id The user ID
     * @param string $content The submission content
     * @param string $file_path Optional file path for uploaded files
     * @return array Result with success status and message
     */
    public function createSubmission($assignment_id, $user_id, $content, $file_path = null) {
        try {
            // Check if a submission already exists
            $stmt = $this->pdo->prepare("SELECT submission_id FROM submissions WHERE assignment_id = ? AND user_id = ?");
            $stmt->execute([$assignment_id, $user_id]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing submission
                $stmt = $this->pdo->prepare("
                    UPDATE submissions 
                    SET content = ?, file_path = ?, submitted_at = NOW(), submission_status = 'submitted'
                    WHERE assignment_id = ? AND user_id = ?
                ");
                $stmt->execute([$content, $file_path, $assignment_id, $user_id]);
                
                return [
                    'success' => true,
                    'submission_id' => $existing['submission_id'],
                    'message' => 'Submission updated successfully'
                ];
            } else {
                // Create new submission
                $stmt = $this->pdo->prepare("
                    INSERT INTO submissions (assignment_id, user_id, content, file_path, submission_status)
                    VALUES (?, ?, ?, ?, 'submitted')
                ");
                $stmt->execute([$assignment_id, $user_id, $content, $file_path]);
                
                return [
                    'success' => true,
                    'submission_id' => $this->pdo->lastInsertId(),
                    'message' => 'Submission created successfully'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get submission by ID
     * @param int $submission_id The submission ID
     * @return mixed Submission data or false
     */
    public function getSubmissionById($submission_id) {
        $stmt = $this->pdo->prepare("
            SELECT s.*, a.title as assignment_title, a.course_id, a.due_date,
                   u.first_name, u.last_name, u.username, c.course_name
            FROM submissions s
            JOIN assignments a ON s.assignment_id = a.assignment_id
            JOIN users u ON s.user_id = u.user_id
            JOIN courses c ON a.course_id = c.course_id
            WHERE s.submission_id = ?
        ");
        $stmt->execute([$submission_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get submissions by assignment
     * @param int $assignment_id The assignment ID
     * @return array List of submissions
     */
    public function getSubmissionsByAssignment($assignment_id) {
        $stmt = $this->pdo->prepare("
            SELECT s.*, u.first_name, u.last_name, u.username
            FROM submissions s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.assignment_id = ?
            ORDER BY s.submitted_at DESC
        ");
        $stmt->execute([$assignment_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get submissions by student
     * @param int $user_id The user ID
     * @return array List of submissions
     */
    public function getSubmissionsByStudent($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT s.*, a.title as assignment_title, a.due_date, c.course_name, c.course_id
            FROM submissions s
            JOIN assignments a ON s.assignment_id = a.assignment_id
            JOIN courses c ON a.course_id = c.course_id
            WHERE s.user_id = ?
            ORDER BY s.submitted_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Grade a submission
     * @param int $submission_id The submission ID
     * @param int $score The score
     * @param string $feedback The feedback
     * @return array Result with success status and message
     */
    public function gradeSubmission($submission_id, $score, $feedback) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE submissions
                SET score = ?, feedback = ?, submission_status = 'graded'
                WHERE submission_id = ?
            ");
            $stmt->execute([$score, $feedback, $submission_id]);
            
            return [
                'success' => true,
                'message' => 'Submission graded successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Return a graded submission to the student
     * @param int $submission_id The submission ID
     * @return array Result with success status and message
     */
    public function returnSubmission($submission_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE submissions
                SET submission_status = 'returned'
                WHERE submission_id = ? AND submission_status = 'graded'
            ");
            $stmt->execute([$submission_id]);
            
            return [
                'success' => true,
                'message' => 'Submission returned to student'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if submission is late
     * @param int $assignment_id The assignment ID
     * @param string $submission_time The submission timestamp
     * @return bool True if late, false otherwise
     */
    public function isLateSubmission($assignment_id, $submission_time) {
        $stmt = $this->pdo->prepare("
            SELECT due_date, allow_late_submission
            FROM assignments
            WHERE assignment_id = ?
        ");
        $stmt->execute([$assignment_id]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$assignment) {
            return false;
        }
        
        $due_date = new DateTime($assignment['due_date']);
        $submitted = new DateTime($submission_time);
        
        return $submitted > $due_date;
    }
}
?>