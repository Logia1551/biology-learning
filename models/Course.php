<?php
namespace Models;

class Course {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM courses ORDER BY meeting_number");
        return $stmt->fetchAll();
    }
}