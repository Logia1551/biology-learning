<?php
namespace Lib;

class Database {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function saveVideo($videoData) {
        $stmt = $this->pdo->prepare("
            INSERT INTO courses (title, description, video_url, topic, grade)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $videoData['title'],
            $videoData['description'],
            $videoData['video_url'],
            $videoData['metadata']['topic'],
            $videoData['metadata']['grade']
        ]);
    }
}