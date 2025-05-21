<?php
session_start();
require_once "../config.php";

// Verify session is valid
if(!isset($_SESSION['exam_start']) || !isset($_SESSION['exam_questions'])) {
    header("Location: dashboard.php");
    exit();
}

// Start database transaction for faster batch processing
$database = new Database();
$db = $database->getConnection();
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Use native prepared statements
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Better error handling
$db->beginTransaction(); // Start transaction to improve performance

try {
    $correct_count = 0;
    $incorrect_count = 0;
    $total_score = 0;
    $answers = $_POST['answers'] ?? [];
    $user_id = $_SESSION['user_id'];
    $user_inter = $_SESSION['inter'];
    
    // Prepare statements once, outside the loop
    $getCorrectAnswerStmt = $db->prepare("SELECT quest_v_Cor FROM questions WHERE id_quest = ?");
    $saveAnswerStmt = $db->prepare("INSERT INTO answer (ans_user_id, ans_question_id, answer_user, answer_corr, score)
                                    VALUES (?, ?, ?, ?, ?)");
    
    // Process all questions in batch
    foreach($_SESSION['exam_questions'] as $question_id) {
        $user_answer = $answers[$question_id] ?? null;
        
        if($user_answer) {
            // Get correct answer
            $getCorrectAnswerStmt->execute([$question_id]);
            $correct_answer = $getCorrectAnswerStmt->fetchColumn();
            
            // Calculate score (5 points per correct answer)
            $is_correct = ($user_answer === $correct_answer);
            $score = $is_correct ? 5 : 0;
            $total_score += $score;
            
            // Count correct and incorrect answers
            if ($is_correct) {
                $correct_count++;
            } else {
                $incorrect_count++;
            }
            
            // Save answer with all required fields
            $saveAnswerStmt->execute([
                $user_id,
                $question_id,
                $user_answer,
                $correct_answer,
                $score
            ]);
        }
    }
    
    // Save counts to session for display on results page
    $_SESSION['exam_result'] = [
        'total_score' => $total_score,
        'correct_count' => $correct_count,
        'incorrect_count' => $incorrect_count,
        'total_questions' => count($_SESSION['exam_questions']),
        'answered_questions' => count($answers)
    ];
    
    // Commit all changes at once
    $db->commit();
    
    // Clean up exam session data
    unset($_SESSION['exam_start'], $_SESSION['exam_questions'], $_SESSION['exam_time_seconds']);
    
    // Redirect to results page with correct/incorrect counts
    header("Location: exam_result.php?correct=" . $correct_count . "&incorrect=" . $incorrect_count . "&score=" . $total_score);
    exit();
    
} catch (Exception $e) {
    // If anything goes wrong, roll back the transaction
    $db->rollBack();
    error_log("Exam submission error: " . $e->getMessage());
    
    // Redirect to an error page
    header("Location: error.php?message=exam_submission_failed");
    exit();
}
?>