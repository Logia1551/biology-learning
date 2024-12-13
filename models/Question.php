<?php
namespace Models;

class Question {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new question
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO questions (course_id, question_text, type) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['course_id'],
                $data['question_text'],
                $data['type'] ?? 'essay'
            ]);
            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Error creating question: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get question by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM questions WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log("Error getting question: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all questions for a course
     */
    public function getByCourseId($courseId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT q.*, 
                       COUNT(DISTINCT sa.student_id) as answer_count
                FROM questions q
                LEFT JOIN student_answers sa ON sa.question_id = q.id
                WHERE q.course_id = ?
                GROUP BY q.id
                ORDER BY q.created_at DESC
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error getting course questions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update a question
     */
    public function update($id, $data) {
        try {
            $sql = "UPDATE questions SET question_text = ?, type = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $data['question_text'],
                $data['type'] ?? 'essay',
                $id
            ]);
        } catch (\PDOException $e) {
            error_log("Error updating question: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a question
     */
    public function delete($id) {
        try {
            // First delete related answers
            $stmt = $this->pdo->prepare("DELETE FROM student_answers WHERE question_id = ?");
            $stmt->execute([$id]);
            
            // Then delete the question
            $stmt = $this->pdo->prepare("DELETE FROM questions WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            error_log("Error deleting question: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a question belongs to a specific course
     */
    public function belongsToCourse($questionId, $courseId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM questions 
                WHERE id = ? AND course_id = ?
            ");
            $stmt->execute([$questionId, $courseId]);
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("Error checking question ownership: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get student answers for a question
     */
    public function getAnswers($questionId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT sa.*, u.username
                FROM student_answers sa
                JOIN users u ON u.id = sa.student_id
                WHERE sa.question_id = ?
                ORDER BY sa.submitted_at DESC
            ");
            $stmt->execute([$questionId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error getting answers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get questions statistics for a course
     */
    public function getCourseStats($courseId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_questions,
                    COUNT(DISTINCT sa.student_id) as total_students,
                    AVG(sa.score) as average_score
                FROM questions q
                LEFT JOIN student_answers sa ON sa.question_id = q.id
                WHERE q.course_id = ?
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log("Error getting course stats: " . $e->getMessage());
            return [
                'total_questions' => 0,
                'total_students' => 0,
                'average_score' => 0
            ];
        }
    }

    /**
     * Validate question data
     */
    public function validate($data) {
        $errors = [];
        
        if (empty($data['question_text'])) {
            $errors[] = "Teks pertanyaan tidak boleh kosong";
        }
        
        if (empty($data['course_id'])) {
            $errors[] = "Course ID tidak boleh kosong";
        }
        
        if (!empty($data['type']) && !in_array($data['type'], ['essay', 'multiple_choice'])) {
            $errors[] = "Tipe pertanyaan tidak valid";
        }
        
        return $errors;
    }
}