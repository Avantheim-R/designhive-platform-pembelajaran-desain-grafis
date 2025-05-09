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

if (!isset($data['assignment_id']) || !isset($data['grade'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    // Update assignment grade and feedback
    $stmt = $pdo->prepare("
        UPDATE assignments 
        SET grade = ?, feedback = ?, graded_at = NOW(), graded_by = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $data['grade'],
        $data['feedback'] ?? null,
        $_SESSION['user_id'],
        $data['assignment_id']
    ]);

    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
