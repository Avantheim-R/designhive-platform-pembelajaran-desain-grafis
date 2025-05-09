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

if (!isset($data['quiz_id']) || !isset($data['answers'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get correct answers for the quiz
    $stmt = $pdo->prepare("
        SELECT question_id, correct_answer 
        FROM quiz_questions 
        WHERE quiz_id = ?
    ");
    $stmt->execute([$data['quiz_id']]);
    $correct_answers = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Calculate score
    $total_questions = count($correct_answers);
    $correct_count = 0;

    foreach ($data['answers'] as $question_id => $answer) {
        if (isset($correct_answers[$question_id]) && $correct_answers[$question_id] === $answer) {
            $correct_count++;
        }
    }

    $score = ($correct_count / $total_questions) * 100;

    // Save quiz result
    $stmt = $pdo->prepare("
        INSERT INTO quiz_results (quiz_id, user_id, score, answers, submitted_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $data['quiz_id'],
        $_SESSION['user_id'],
        $score,
        json_encode($data['answers'])
    ]);

    $result_id = $pdo->lastInsertId();

    // Update user progress if this is their best score
    $stmt = $pdo->prepare("
        UPDATE user_progress 
        SET best_quiz_score = GREATEST(COALESCE(best_quiz_score, 0), ?)
        WHERE user_id = ? AND quiz_id = ?
    ");
    $stmt->execute([$score, $_SESSION['user_id'], $data['quiz_id']]);

    // If this was a final exam, check if certificate should be generated
    $stmt = $pdo->prepare("SELECT is_final_exam FROM quizzes WHERE id = ?");
    $stmt->execute([$data['quiz_id']]);
    $quiz = $stmt->fetch();

    if ($quiz['is_final_exam'] && $score >= 70) { // Passing score is 70%
        // Generate certificate
        $stmt = $pdo->prepare("
            INSERT INTO certificates (user_id, quiz_id, score, generated_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$_SESSION['user_id'], $data['quiz_id'], $score]);
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'result_id' => $result_id,
        'score' => $score,
        'correct_count' => $correct_count,
        'total_questions' => $total_questions
    ]);

} catch(PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

// Function to handle different question types
function validateAnswer($question_type, $user_answer, $correct_answer) {
    switch ($question_type) {
        case 'multiple_choice':
            return $user_answer === $correct_answer;
        
        case 'drag_drop':
            // For drag and drop, answers are arrays that need to be in the correct order
            if (!is_array($user_answer) || !is_array($correct_answer)) {
                return false;
            }
            return $user_answer === $correct_answer;
        
        case 'matching':
            // For matching, answers are associative arrays that need to match pairs
            if (!is_array($user_answer) || !is_array($correct_answer)) {
                return false;
            }
            return count(array_diff_assoc($user_answer, $correct_answer)) === 0;
        
        default:
            return false;
    }
}

// Function to calculate partial credit for complex question types
function calculatePartialCredit($question_type, $user_answer, $correct_answer) {
    switch ($question_type) {
        case 'drag_drop':
            // Count number of items in correct position
            $correct = 0;
            foreach ($user_answer as $index => $item) {
                if (isset($correct_answer[$index]) && $item === $correct_answer[$index]) {
                    $correct++;
                }
            }
            return $correct / count($correct_answer);
        
        case 'matching':
            // Count number of correct matches
            $correct = 0;
            foreach ($user_answer as $key => $value) {
                if (isset($correct_answer[$key]) && $value === $correct_answer[$key]) {
                    $correct++;
                }
            }
            return $correct / count($correct_answer);
        
        default:
            return 0;
    }
}
?>
