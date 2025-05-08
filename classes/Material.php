<?php
// classes/Material.php
class Material {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new course material
     * @param int $course_id The course ID
     * @param string $title The material title
     * @param string $description The material description
     * @param string $material_type The type of material (document, link, video, file)
     * @param string $content_url Optional URL for links or videos
     * @param string $file_path Optional file path for uploaded files
     * @return array Result with success status and material ID
     */
    public function createMaterial($course_id, $title, $description, $material_type, $content_url = null, $file_path = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO materials (course_id, title, description, material_type, content_url, file_path)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$course_id, $title, $description, $material_type, $content_url, $file_path]);
            
            return [
                'success' => true,
                'material_id' => $this->pdo->lastInsertId(),
                'message' => 'Material created successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get material by ID
     * @param int $material_id The material ID
     * @return mixed Material data or false
     */
    public function getMaterialById($material_id) {
        $stmt = $this->pdo->prepare("
            SELECT m.*, c.course_name
            FROM materials m
            JOIN courses c ON m.course_id = c.course_id
            WHERE m.material_id = ?
        ");
        $stmt->execute([$material_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get materials by course ID
     * @param int $course_id The course ID
     * @return array List of materials
     */
    public function getMaterialsByCourse($course_id) {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM materials
            WHERE course_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$course_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update material
     * @param int $material_id The material ID
     * @param string $title The material title
     * @param string $description The material description
     * @param string $material_type The type of material
     * @param string $content_url Optional URL for links or videos
     * @param string $file_path Optional file path for uploaded files
     * @return array Result with success status and message
     */
    public function updateMaterial($material_id, $title, $description, $material_type, $content_url = null, $file_path = null) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE materials
                SET title = ?, description = ?, material_type = ?, content_url = ?, file_path = ?
                WHERE material_id = ?
            ");
            $stmt->execute([$title, $description, $material_type, $content_url, $file_path, $material_id]);
            
            return [
                'success' => true,
                'message' => 'Material updated successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete material
     * @param int $material_id The material ID
     * @return array Result with success status and message
     */
    public function deleteMaterial($material_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM materials WHERE material_id = ?");
            $stmt->execute([$material_id]);
            
            return [
                'success' => true,
                'message' => 'Material deleted successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
}
?>