<?php
// classes/Discussion.php
class Discussion {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new discussion
     * @param int $course_id The course ID
     * @param int $creator_id The creator user ID
     * @param string $title The discussion title
     * @param string $description The discussion description
     * @return array Result with success status and discussion ID
     */
    public function createDiscussion($course_id, $creator_id, $title, $description) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO discussions (course_id, creator_id, title, description)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$course_id, $creator_id, $title, $description]);
            
            return [
                'success' => true,
                'discussion_id' => $this->pdo->lastInsertId(),
                'message' => 'Discussion created successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get discussion by ID
     * @param int $discussion_id The discussion ID
     * @return mixed Discussion data or false
     */
    public function getDiscussionById($discussion_id) {
        $stmt = $this->pdo->prepare("
            SELECT d.*, u.first_name, u.last_name, u.username, c.course_name
            FROM discussions d
            JOIN users u ON d.creator_id = u.user_id
            JOIN courses c ON d.course_id = c.course_id
            WHERE d.discussion_id = ?
        ");
        $stmt->execute([$discussion_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get discussions by course ID
     * @param int $course_id The course ID
     * @return array List of discussions
     */
    public function getDiscussionsByCourse($course_id) {
        $stmt = $this->pdo->prepare("
            SELECT d.*, u.first_name, u.last_name, 
                  (SELECT COUNT(*) FROM comments WHERE discussion_id = d.discussion_id) as comment_count
            FROM discussions d
            JOIN users u ON d.creator_id = u.user_id
            WHERE d.course_id = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$course_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update discussion
     * @param int $discussion_id The discussion ID
     * @param string $title The discussion title
     * @param string $description The discussion description
     * @return array Result with success status and message
     */
    public function updateDiscussion($discussion_id, $title, $description) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE discussions
                SET title = ?, description = ?
                WHERE discussion_id = ?
            ");
            $stmt->execute([$title, $description, $discussion_id]);
            
            return [
                'success' => true,
                'message' => 'Discussion updated successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete discussion
     * @param int $discussion_id The discussion ID
     * @return array Result with success status and message
     */
    public function deleteDiscussion($discussion_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM discussions WHERE discussion_id = ?");
            $stmt->execute([$discussion_id]);
            
            return [
                'success' => true,
                'message' => 'Discussion deleted successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if user can edit discussion
     * @param int $discussion_id The discussion ID
     * @param int $user_id The user ID
     * @return bool True if user can edit, false otherwise
     */
    public function canUserEditDiscussion($discussion_id, $user_id) {
        // Get the discussion creator ID
        $stmt = $this->pdo->prepare("SELECT creator_id FROM discussions WHERE discussion_id = ?");
        $stmt->execute([$discussion_id]);
        $discussion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$discussion) {
            return false;
        }
        
        // Check if user is creator
        if ($discussion['creator_id'] == $user_id) {
            return true;
        }
        
        // Check if user is teacher of the course
        $stmt = $this->pdo->prepare("
            SELECT e.user_id 
            FROM discussions d
            JOIN enrollments e ON d.course_id = e.course_id
            WHERE d.discussion_id = ? AND e.user_id = ? AND e.enrollment_type = 'teacher'
        ");
        $stmt->execute([$discussion_id, $user_id]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $teacher ? true : false;
    }
}
?>