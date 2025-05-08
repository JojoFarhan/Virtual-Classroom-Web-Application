

<?php
// classes/Course.php
class Course {
    private $pdo;
    private $course_data;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    //Get course by user
    public function getCoursesByUser($user_id, $role = null) {
        $query = "
            SELECT c.*, e.enrollment_type
            FROM courses c
            JOIN enrollments e ON c.course_id = e.course_id
            WHERE e.user_id = ?
        ";
        
        $params = [$user_id];
        
        if ($role) {
            $query .= " AND e.enrollment_type = ?";
            $params[] = $role;
        }
        
        $query .= " ORDER BY c.created_at DESC";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Get course by ID
    public function getCourseById($course_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
            $stmt->execute([$course_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $this->course_data = $result;
                return $result;
            }
            return false;
        } catch (PDOException $e) {
            error_log('Database error in getCourseById: ' . $e->getMessage());
            return false;
        }
    }

    // Get total number of courses
    public function getTotalCourses() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM courses");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Database error in getTotalCourses: ' . $e->getMessage());
            return 0;
        }
    }

    // Get recent courses
    public function getRecentCourses($limit = 5) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM courses 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database error in getRecentCourses: ' . $e->getMessage());
            return [];
        }
    }

    // Create a new course
    public function createCourse($name, $code, $description, $creator_id) {
        // Check if course code already exists
        $check = $this->pdo->prepare("SELECT course_id FROM courses WHERE course_code = ?");
        $check->execute([$code]);
        if ($check->rowCount() > 0) {
            return ['success' => false, 'message' => 'Course code already exists'];
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO courses (course_name, course_code, description, creator_id)
            VALUES (?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([$name, $code, $description, $creator_id]);
        
        if ($result) {
            $course_id = $this->pdo->lastInsertId();
            
            // Automatically enroll the creator as a teacher
            $enroll = $this->pdo->prepare("
                INSERT INTO enrollments (user_id, course_id, enrollment_type)
                VALUES (?, ?, 'teacher')
            ");
            $enroll->execute([$creator_id, $course_id]);
            
            return ['success' => true, 'course_id' => $course_id];
        } else {
            return ['success' => false, 'message' => 'Failed to create course'];
        }
    }

    // Update course information
    public function updateCourse($course_id, $data) {
        try {
            $fields = [];
            $values = [];
            
            // Build dynamic query based on provided data
            foreach ($data as $field => $value) {
                if (in_array($field, ['course_name', 'course_code', 'description', 'is_archived'])) {
                    $fields[] = "$field = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $values[] = $course_id;
            
            $stmt = $this->pdo->prepare("
                UPDATE courses SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP 
                WHERE course_id = ?
            ");
            
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log('Database error in updateCourse: ' . $e->getMessage());
            return false;
        }
    }

    // Archive a course
    public function archiveCourse($course_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE courses SET is_archived = 1, updated_at = CURRENT_TIMESTAMP 
                WHERE course_id = ?
            ");
            
            return $stmt->execute([$course_id]);
        } catch (PDOException $e) {
            error_log('Database error in archiveCourse: ' . $e->getMessage());
            return false;
        }
    }

    // Restore an archived course
    public function restoreCourse($course_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE courses SET is_archived = 0, updated_at = CURRENT_TIMESTAMP 
                WHERE course_id = ?
            ");
            
            return $stmt->execute([$course_id]);
        } catch (PDOException $e) {
            error_log('Database error in restoreCourse: ' . $e->getMessage());
            return false;
        }
    }

    // Get courses with pagination and filtering
    public function getCourses($page = 1, $limit = 10, $filters = []) {
        try {
            $page = (int)$page;
            $limit = (int)$limit;

            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);

            $offset = ($page - 1) * $limit;
            
            $where_clauses = [];
            $params = [];
            
            // Apply filters if provided
            if (!empty($filters)) {
                if (isset($filters['archived']) && $filters['archived'] !== null) {
                    $where_clauses[] = "is_archived = ?";
                    $params[] = $filters['archived'] ? 1 : 0;
                }
                
                if (isset($filters['search']) && !empty($filters['search'])) {
                    $search_term = '%' . $filters['search'] . '%';
                    $where_clauses[] = "(course_name LIKE ? OR course_code LIKE ? OR description LIKE ?)";
                    $params[] = $search_term;
                    $params[] = $search_term;
                    $params[] = $search_term;
                }
                
                if (isset($filters['creator_id']) && !empty($filters['creator_id'])) {
                    $where_clauses[] = "creator_id = ?";
                    $params[] = $filters['creator_id'];
                }
            }
            
            $where_sql = empty($where_clauses) ? "" : "WHERE " . implode(" AND ", $where_clauses);
            
            // Get total count for pagination
            $count_sql = "SELECT COUNT(*) FROM courses $where_sql";
            $count_stmt = $this->pdo->prepare($count_sql);
            $count_stmt->execute($params);
            $total = $count_stmt->fetchColumn();
            
            // Get courses with pagination
            $sql = "SELECT * FROM courses $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'courses' => $courses,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ];
        } catch (PDOException $e) {
            error_log('Database error in getCourses: ' . $e->getMessage());
            return [
                'courses' => [],
                'total' => 0,
                'page' => 1,
                'limit' => $limit,
                'pages' => 0
            ];
        }
    }

    // Get enrollments for a course
    public function getCourseEnrollments($course_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT e.*, u.username, u.first_name, u.last_name, u.email
                FROM enrollments e
                JOIN users u ON e.user_id = u.user_id
                WHERE e.course_id = ?
                ORDER BY e.enrollment_type, u.last_name, u.first_name
            ");
            $stmt->execute([$course_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database error in getCourseEnrollments: ' . $e->getMessage());
            return [];
        }
    }

    // Enroll a user in a course
    public function enrollUser($course_id, $user_id, $enrollment_type = 'student') {
        try {
            // Check if enrollment already exists
            $check = $this->pdo->prepare("
                SELECT COUNT(*) FROM enrollments 
                WHERE user_id = ? AND course_id = ?
            ");
            $check->execute([$user_id, $course_id]);
            
            if ($check->fetchColumn() > 0) {
                // Update existing enrollment
                $stmt = $this->pdo->prepare("
                    UPDATE enrollments 
                    SET enrollment_status = 'active', enrollment_type = ? 
                    WHERE user_id = ? AND course_id = ?
                ");
                return $stmt->execute([$enrollment_type, $user_id, $course_id]);
            } else {
                // Create new enrollment
                $stmt = $this->pdo->prepare("
                    INSERT INTO enrollments (user_id, course_id, enrollment_status, enrollment_type) 
                    VALUES (?, ?, 'active', ?)
                ");
                return $stmt->execute([$user_id, $course_id, $enrollment_type]);
            }
        } catch (PDOException $e) {
            error_log('Database error in enrollUser: ' . $e->getMessage());
            return false;
        }
    }

    // Unenroll a user from a course
    public function unenrollUser($course_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE enrollments 
                SET enrollment_status = 'inactive' 
                WHERE user_id = ? AND course_id = ?
            ");
            
            return $stmt->execute([$user_id, $course_id]);
        } catch (PDOException $e) {
            error_log('Database error in unenrollUser: ' . $e->getMessage());
            return false;
        }
    }

    // Get courses a user is enrolled in
    public function getUserCourses($user_id, $enrollment_status = 'active') {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.*, e.enrollment_type, e.enrollment_status
                FROM courses c
                JOIN enrollments e ON c.course_id = e.course_id
                WHERE e.user_id = ? AND e.enrollment_status = ?
                ORDER BY c.course_name
            ");
            $stmt->execute([$user_id, $enrollment_status]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database error in getUserCourses: ' . $e->getMessage());
            return [];
        }
    }

    // Check if user is enrolled in a course
    public function isUserEnrolled($course_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM enrollments 
                WHERE user_id = ? AND course_id = ? AND enrollment_status = 'active'
            ");
            $stmt->execute([$user_id, $course_id]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Database error in isUserEnrolled: ' . $e->getMessage());
            return false;
        }
    }

    // Get users who can be enrolled in a course (not already enrolled)
    public function getEnrollableCourseUsers($course_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.user_id, u.username, u.email, u.first_name, u.last_name
                FROM users u
                WHERE u.user_status = 'active'
                AND u.user_id NOT IN (
                    SELECT e.user_id FROM enrollments e 
                    WHERE e.course_id = ? AND e.enrollment_status = 'active'
                )
                ORDER BY u.last_name, u.first_name
            ");
            $stmt->execute([$course_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database error in getEnrollableCourseUsers: ' . $e->getMessage());
            return [];
        }
    }

    //=====================================
    
    
    
    
    public function getEnrolledUsers($course_id, $enrollment_type = null) {
        $query = "
            SELECT u.user_id, u.username, u.first_name, u.last_name, 
                   u.email, e.enrollment_type, e.enrolled_at
            FROM users u
            JOIN enrollments e ON u.user_id = e.user_id
            WHERE e.course_id = ?
        ";
        
        $params = [$course_id];
        
        if ($enrollment_type) {
            $query .= " AND e.enrollment_type = ?";
            $params[] = $enrollment_type;
        }
        
        $query .= " ORDER BY u.last_name, u.first_name";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getAllCourses() {
        $stmt = $this->pdo->query("
            SELECT c.*, u.first_name, u.last_name, 
                   COUNT(DISTINCT e.user_id) as enrollment_count
            FROM courses c
            JOIN users u ON c.creator_id = u.user_id
            LEFT JOIN enrollments e ON c.course_id = e.course_id
            GROUP BY c.course_id
            ORDER BY c.created_at DESC
        ");
        return $stmt->fetchAll();
    }
}