<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['thread_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing thread ID']);
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Delete comments first (due to foreign key constraint)
    $stmt = $pdo->prepare("DELETE FROM forum_comments WHERE forum_id = ?");
    $stmt->execute([$data['thread_id']]);

    // Delete thread
    $stmt = $pdo->prepare("DELETE FROM forum WHERE id = ?");
    $stmt->execute([$data['thread_id']]);

    // Commit transaction
    $pdo->commit();

    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
