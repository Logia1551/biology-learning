<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

$id = $_GET['id'] ?? 0;

try {
    // Cek apakah konten ada
    $stmt = $pdo->prepare("SELECT * FROM biology_content WHERE id = ?");
    $stmt->execute([$id]);
    $content = $stmt->fetch();

    if ($content) {
        // Hapus konten
        $stmt = $pdo->prepare("DELETE FROM biology_content WHERE id = ?");
        $stmt->execute([$id]);

        // Set flash message untuk sukses
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => 'Konten berhasil dihapus'
        ];
    } else {
        // Set flash message untuk error
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Konten tidak ditemukan'
        ];
    }

} catch (PDOException $e) {
    // Set flash message untuk error
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Error menghapus konten: ' . $e->getMessage()
    ];
}

// Redirect kembali ke halaman manage content
header('Location: manage-content.php');
exit;