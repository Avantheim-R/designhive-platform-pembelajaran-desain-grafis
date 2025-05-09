<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['lesson_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing lesson ID']);
    exit();
}

try {
    // Check if progress record exists
    $stmt = $pdo->prepare("
        SELECT id FROM progress 
        WHERE user_id = ? AND lesson_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $data['lesson_id']]);
    $exists = $stmt->fetch();

    if ($exists) {
        // Update existing record
        $stmt = $pdo->prepare("
            UPDATE progress 
            SET phase_completed = 1, completed_at = NOW()
            WHERE user_id = ? AND lesson_id = ?
        ");
    } else {
        // Insert new record
        $stmt = $pdo->prepare("
            INSERT INTO progress (user_id, lesson_id, phase_completed, completed_at)
            VALUES (?, ?, 1, NOW())
        ");
    }
    
    $stmt->execute([$_SESSION['user_id'], $data['lesson_id']]);

    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
