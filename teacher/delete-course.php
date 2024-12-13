<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'ID pertemuan tidak valid';
    header('Location: dashboard.php');
    exit;
}

$course_id = (int)$_GET['id'];

// Verify course exists and belongs to the teacher
try {
    $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$course_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'Pertemuan tidak ditemukan atau Anda tidak memiliki akses';
        header('Location: dashboard.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error verifying course: " . $e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan saat memverifikasi pertemuan';
    header('Location: dashboard.php');
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Delete in proper order to maintain referential integrity
    // 1. Delete student answers first
    $stmt = $pdo->prepare("
        DELETE sa FROM student_answers sa 
        INNER JOIN questions q ON sa.question_id = q.id 
        WHERE q.course_id = ?
    ");
    if (!$stmt->execute([$course_id])) {
        throw new PDOException("Failed to delete student answers");
    }

    // 2. Delete question options
    $stmt = $pdo->prepare("
        DELETE o FROM options o 
        INNER JOIN questions q ON o.question_id = q.id 
        WHERE q.course_id = ?
    ");
    if (!$stmt->execute([$course_id])) {
        throw new PDOException("Failed to delete question options");
    }

    // 3. Delete questions
    $stmt = $pdo->prepare("DELETE FROM questions WHERE course_id = ?");
    if (!$stmt->execute([$course_id])) {
        throw new PDOException("Failed to delete questions");
    }

    // 4. Finally delete the course
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ? AND teacher_id = ?");
    if (!$stmt->execute([$course_id, $_SESSION['user_id']])) {
        throw new PDOException("Failed to delete course");
    }

    // If all queries successful, commit the transaction
    $pdo->commit();
    $_SESSION['message'] = 'Pertemuan berhasil dihapus';

} catch (PDOException $e) {
    // Rollback on error
    $pdo->rollBack();
    
    // Log the error
    error_log("Error deleting course: " . $e->getMessage());
    
    // Set user-friendly error message
    $_SESSION['error'] = 'Gagal menghapus pertemuan. Silakan coba lagi nanti.';
    
} catch (Exception $e) {
    // Catch any other types of exceptions
    $pdo->rollBack();
    error_log("Unexpected error: " . $e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan yang tidak diharapkan';
}

// Redirect back to dashboard
header('Location: dashboard.php');
exit;
?>