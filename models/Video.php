<?php
namespace Models;

class Video {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getByTopic($topic) {
        $stmt = $this->pdo->prepare("SELECT * FROM courses WHERE topic = ?");
        $stmt->execute([$topic]);
        return $stmt->fetchAll();
    }
}