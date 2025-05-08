<?php
// classes/Comment.php
class Comment {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new comment
     * @param int $user_id The user ID
     * @param int $discussion_id The discussion ID
     * @param string $content The comment content
     * @param int $parent_id Optional parent comment ID for replies
     * @return array Result with success status and comment ID
     */
    public function createComment($user_id, $discussion_id, $content, $parent_id = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO comments (user_id, discussion_id, content, parent_id)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $discussion_id, $content, $parent_id]);
            
            return [
                'success' => true,
                'comment_id' => $this->pdo->lastInsertId(),
                'message' => 'Comment posted successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get comments by discussion ID
     * @param int $discussion_id The discussion ID
     * @return array List of comments
     */
    public function getCommentsByDiscussion($discussion_id) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.first_name, u.last_name, u.username
            FROM comments c
            JOIN users u ON c.user_id = u.user_id
            WHERE c.discussion_id = ?
            ORDER BY c.posted_at
        ");
        $stmt->execute([$discussion_id]);
        return $this->buildCommentTree($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * Build hierarchical comment tree from flat list
     * @param array $comments Flat list of comments
     * @param int $parent_id Parent comment ID
     * @return array Hierarchical comment tree
     */
    private function buildCommentTree($comments, $parent_id = null) {
        $tree = [];
        
        foreach ($comments as $comment) {
            if ($comment['parent_id'] == $parent_id) {
                $comment['replies'] = $this->buildCommentTree($comments, $comment['comment_id']);
                $tree[] = $comment;
            }
        }
        
        return $tree;
    }
    
    /**
     * Get comment by ID
     * @param int $comment_id The comment ID
     * @return mixed Comment data or false
     */
    public function getCommentById($comment_id) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.first_name, u.last_name, u.username
            FROM comments c
            JOIN users u ON c.user_id = u.user_id
            WHERE c.comment_id = ?
        ");
        $stmt->execute([$comment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update comment
     * @param int $comment_id The comment ID
     * @param string $content The comment content
     * @return array Result with success status and message
     */
    public function updateComment($comment_id, $content) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE comments
                SET content = ?
                WHERE comment_id = ?
            ");
            $stmt->execute([$content, $comment_id]);
            
            return [
                'success' => true,
                'message' => 'Comment updated successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete comment
     * @param int $comment_id The comment ID
     * @return array Result with success status and message
     */
    public function deleteComment($comment_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM comments WHERE comment_id = ?");
            $stmt->execute([$comment_id]);
            
            return [
                'success' => true,
                'message' => 'Comment deleted successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if user can edit comment
     * @param int $comment_id The comment ID
     * @param int $user_id The user ID
     * @return bool True if user can edit, false otherwise
     */
    public function canUserEditComment($comment_id, $user_id) {
        // Get the comment creator ID
        $stmt = $this->pdo->prepare("SELECT user_id FROM comments WHERE comment_id = ?");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$comment) {
            return false;
        }
        
        // Check if user is creator
        if ($comment['user_id'] == $user_id) {
            return true;
        }
        
        // Check if user is teacher of the course
        $stmt = $this->pdo->prepare("
            SELECT e.user_id 
            FROM comments c
            JOIN discussions d ON c.discussion_id = d.discussion_id
            JOIN enrollments e ON d.course_id = e.course_id
            WHERE c.comment_id = ? AND e.user_id = ? AND e.enrollment_type = 'teacher'
        ");
        $stmt->execute([$comment_id, $user_id]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $teacher ? true : false;
    }
}
?>