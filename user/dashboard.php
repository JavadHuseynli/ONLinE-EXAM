<?php // user/dashboard.php
session_start();
require_once "../config.php";

if(!isset($_SESSION['logged_in']) || $_SESSION['role'] != 1) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get user's quest_type information
$stmt = $db->prepare("
    SELECT qt.idquest_type, qt.type 
    FROM quest_type qt
    WHERE qt.idquest_type = ?
");
$stmt->execute([$_SESSION['inter'] ?? 0]);
$quest_type_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's previous exam results
$stmt = $db->prepare("
    SELECT a.score, a.answer_user, q.quest_v_Cor, q.quest_text,
           COUNT(*) as total_questions, AVG(a.score) as avg_score
    FROM answer a
    JOIN questions q ON a.ans_question_id = q.id_quest
    WHERE a.ans_user_id = ?
    GROUP BY a.idanswer 
"); 
$stmt->execute([$_SESSION['user_id']]);
$previous_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <title>İstifadəçi Paneli - Rabite Exam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .exam-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .exam-card:hover {
            transform: translateY(-5px);
        }
        .btn-start {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 25px;
        }
        .btn-start:hover {
            background: #27ae60;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Xoş gəlmisiniz, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
            </div>
            <div class="col-md-4 text-end">
                <a href="../logout.php" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt"></i> Çıxış
                </a>
            </div>
        </div>
        
        <!-- İmtahan Başlatma -->
        <div class="row mb-5">
            <div class="col-md-6">
                <div class="card exam-card">
                    <div class="card-body text-center p-5">
                        <h4 class="mb-4">Yeni İmtahan</h4>
                        <p class="mb-4">İmtahan növü: 
                            <?php echo $quest_type_info && isset($quest_type_info['type']) ? 
                                htmlspecialchars($quest_type_info['type']) : 'Təyin edilməyib'; ?>
                        </p>
                        <a href="start_exam.php" class="btn btn-start">
                            <i class="fas fa-play me-2"></i>İmtahana Başla
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>