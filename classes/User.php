

<?php
// classes/User.php
class User {
    private $pdo;
    private $user_data;

    
    private $user_id;
    private $username;
    private $email;
    private $first_name;
    private $last_name;
    private $roles = [];

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Get user by ID
    public function getUserById($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $this->user_data = $result;
                return $result;
            }
            return false;
        } catch (PDOException $e) {
            error_log('Database error in getUserById: ' . $e->getMessage());
            return false;
        }
    }

    // Get user by username
    public function getUserByUsername($username) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $this->user_data = $result;
                return $result;
            }
            return false;
        } catch (PDOException $e) {
            error_log('Database error in getUserByUsername: ' . $e->getMessage());
            return false;
        }
    }

    // Get total number of users
    public function getTotalUsers() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Database error in getTotalUsers: ' . $e->getMessage());
            return 0;
        }
    }

    // Get recent users
    public function getRecentUsers($limit = 5) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM users 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database error in getRecentUsers: ' . $e->getMessage());
            return [];
        }
    }

    // Create a new user
    public function createUser($username, $email, $password, $first_name, $last_name) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password_hash, first_name, last_name) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([$username, $email, $password_hash, $first_name, $last_name]);
            
            if ($result) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log('Database error in createUser: ' . $e->getMessage());
            return false;
        }
    }

    // Update user information
    public function updateUser($user_id, $data) {
        try {
            $fields = [];
            $values = [];
            
            // Build dynamic query based on provided data
            foreach ($data as $field => $value) {
                if (in_array($field, ['username', 'email', 'first_name', 'last_name', 'user_status'])) {
                    $fields[] = "$field = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $values[] = $user_id;
            
            $stmt = $this->pdo->prepare("
                UPDATE users SET " . implode(', ', $fields) . " 
                WHERE user_id = ?
            ");
            
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log('Database error in updateUser: ' . $e->getMessage());
            return false;
        }
    }

    // Update user password
    public function updatePassword($user_id, $new_password) {
        try {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("
                UPDATE users SET password_hash = ? 
                WHERE user_id = ?
            ");
            
            return $stmt->execute([$password_hash, $user_id]);
        } catch (PDOException $e) {
            error_log('Database error in updatePassword: ' . $e->getMessage());
            return false;
        }
    }

    // Assign role to user
    public function assignRole($user_id, $role_id) {
        try {
            // Check if role assignment already exists
            $check = $this->pdo->prepare("
                SELECT COUNT(*) FROM user_roles 
                WHERE user_id = ? AND role_id = ?
            ");
            $check->execute([$user_id, $role_id]);
            
            if ($check->fetchColumn() > 0) {
                return true; // Role already assigned
            }
            
            // Add new role assignment
            $stmt = $this->pdo->prepare("
                INSERT INTO user_roles (user_id, role_id) 
                VALUES (?, ?)
            ");
            
            return $stmt->execute([$user_id, $role_id]);
        } catch (PDOException $e) {
            error_log('Database error in assignRole: ' . $e->getMessage());
            return false;
        }
    }

    // Remove role from user
    public function removeRole($user_id, $role_id) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM user_roles 
                WHERE user_id = ? AND role_id = ?
            ");
            
            return $stmt->execute([$user_id, $role_id]);
        } catch (PDOException $e) {
            error_log('Database error in removeRole: ' . $e->getMessage());
            return false;
        }
    }

    // Get user's roles
    public function getUserRoles($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.role_id, r.role_name, r.description 
                FROM roles r
                JOIN user_roles ur ON r.role_id = ur.role_id
                WHERE ur.user_id = ?
            ");
            $stmt->execute([$user_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database error in getUserRoles: ' . $e->getMessage());
            return [];
        }
    }

    // Check if user has specific role
    public function hasRole($user_id, $role_name) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM user_roles ur
                JOIN roles r ON ur.role_id = r.role_id
                WHERE ur.user_id = ? AND r.role_name = ?
            ");
            $stmt->execute([$user_id, $role_name]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Database error in hasRole: ' . $e->getMessage());
            return false;
        }
    }

    // Update last login time
    public function updateLastLogin($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users SET last_login = CURRENT_TIMESTAMP 
                WHERE user_id = ?
            ");
            
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log('Database error in updateLastLogin: ' . $e->getMessage());
            return false;
        }
    }

    // Get users with pagination and filtering
    public function getUsers($page = 1, $limit = 10, $filters = []) {
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
                if (isset($filters['status']) && !empty($filters['status'])) {
                    $where_clauses[] = "user_status = ?";
                    $params[] = $filters['status'];
                }
                
                if (isset($filters['search']) && !empty($filters['search'])) {
                    $search_term = '%' . $filters['search'] . '%';
                    $where_clauses[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
                    $params[] = $search_term;
                    $params[] = $search_term;
                    $params[] = $search_term;
                    $params[] = $search_term;
                }
                
                if (isset($filters['role']) && !empty($filters['role'])) {
                    $where_clauses[] = "user_id IN (
                        SELECT user_id FROM user_roles ur
                        JOIN roles r ON ur.role_id = r.role_id
                        WHERE r.role_name = ?
                    )";
                    $params[] = $filters['role'];
                }
            }
            
            $where_sql = empty($where_clauses) ? "" : "WHERE " . implode(" AND ", $where_clauses);
            
            // Get total count for pagination
            $count_sql = "SELECT COUNT(*) FROM users $where_sql";
            $count_stmt = $this->pdo->prepare($count_sql);
            $count_stmt->execute($params);
            $total = $count_stmt->fetchColumn();
            
            // Get users with pagination
            $sql = "SELECT * FROM users $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'users' => $users,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ];
        } catch (PDOException $e) {
            error_log('Database error in getUsers: ' . $e->getMessage());
            return [
                'users' => [],
                'total' => 0,
                'page' => 1,
                'limit' => $limit,
                'pages' => 0
            ];
        }
    }

    //========================================
    

    

    public function register($username, $email, $password, $first_name, $last_name) {
        // Check if username or email already exists
        $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            return false; // Username or email already exists
        }

        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert the new user
        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, email, password_hash, first_name, last_name) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([$username, $email, $password_hash, $first_name, $last_name]);
        
        if ($success) {
            // Assign default student role
            $user_id = $this->pdo->lastInsertId();
            $this->assignRole($user_id, 3); // 3 = student role
            return $user_id;
        }
        
        return false;
    }

    public function login($username_or_email, $password) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM users 
            WHERE username = ? OR email = ?
        ");
        $stmt->execute([$username_or_email, $username_or_email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last login time
            $update = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $update->execute([$user['user_id']]);
            
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            
            // Load user data
            $this->user_id = $user['user_id'];
            $this->username = $user['username'];
            $this->email = $user['email'];
            $this->first_name = $user['first_name'];
            $this->last_name = $user['last_name'];
            
            // Load user roles
            $this->loadUserRoles();
            $_SESSION['roles'] = $this->roles;
            
            return true;
        }
        
        return false;
    }

    private function loadUserRoles() {
        $stmt = $this->pdo->prepare("
            SELECT r.role_id, r.role_name 
            FROM roles r
            JOIN user_roles ur ON r.role_id = ur.role_id
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$this->user_id]);
        $this->roles = $stmt->fetchAll();
    }

    

    public function getAllUsers() {
        $stmt = $this->pdo->query("
            SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, 
                   u.created_at, u.last_login, u.user_status,
                   GROUP_CONCAT(r.role_name) as roles
            FROM users u
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.role_id
            GROUP BY u.user_id
            ORDER BY u.last_name, u.first_name
        ");
        return $stmt->fetchAll();
    }

    // Getters
    public function getId() { return $this->user_id; }
    public function getUsername() { return $this->username; }
    public function getEmail() { return $this->email; }
    public function getFirstName() { return $this->first_name; }
    public function getLastName() { return $this->last_name; }
    public function getFullName() { return $this->first_name . ' ' . $this->last_name; }
    public function getRoles() { return $this->roles; }
}