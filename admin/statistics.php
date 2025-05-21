<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] != 2) {
   header("Location: ../login.php");
   exit();
}

$database = new Database();
$db = $database->getConnection();

try {
   // İstifadəçi statistikaları
   $userStats = $db->query("
       SELECT 
           u.username,
           u.work_place,
           COUNT(DISTINCT a.ans_question_id) as answered_questions,
           AVG(a.score) as avg_score,
           MAX(a.score) as max_score,
           COUNT(DISTINCT qt.idquest_type) as topics_covered
       FROM users u
       LEFT JOIN answer a ON u.iduser = a.ans_user_id
       LEFT JOIN questions q ON a.ans_question_id = q.id_quest
       LEFT JOIN quest_type qt ON q.quest_type = qt.idquest_type
       GROUP BY u.iduser, u.username, u.work_place
   ")->fetchAll(PDO::FETCH_ASSOC);

   // Ən çətin suallar
   $hardQuestions = $db->query("
       SELECT 
           q.quest_text,
           qt.type as topic_name,
           COUNT(a.idanswer) as attempt_count,
           COUNT(CASE WHEN a.answer_user != q.quest_v_Cor THEN 1 END) as wrong_answers,
           (COUNT(CASE WHEN a.answer_user != q.quest_v_Cor THEN 1 END) * 100.0 / COUNT(a.idanswer)) as error_rate
       FROM questions q
       JOIN quest_type qt ON q.quest_type = qt.idquest_type
       JOIN answer a ON q.id_quest = a.ans_question_id
       GROUP BY q.id_quest, q.quest_text, qt.type
       HAVING attempt_count >= 5
       ORDER BY error_rate DESC
       LIMIT 10
   ")->fetchAll(PDO::FETCH_ASSOC);

   // Filial statistikası
   $branchStats = $db->query("
       SELECT 
           u.work_place,
           COUNT(DISTINCT u.iduser) as employee_count,
           AVG(a.score) as avg_score,
           COUNT(DISTINCT a.ans_question_id) as total_questions,
           COUNT(DISTINCT qt.idquest_type) as topics_covered
       FROM users u
       LEFT JOIN answer a ON u.iduser = a.ans_user_id
       LEFT JOIN questions q ON a.ans_question_id = q.id_quest
       LEFT JOIN quest_type qt ON q.quest_type = qt.idquest_type
       GROUP BY u.work_place
       ORDER BY avg_score DESC
   ")->fetchAll(PDO::FETCH_ASSOC);

   // Mövzu statistikası
   $topicStats = $db->query("
       SELECT 
           qt.type as topic_name,
           COUNT(q.id_quest) as total_questions,
           COUNT(DISTINCT a.ans_user_id) as unique_users,
           AVG(a.score) as avg_score,
           COUNT(CASE WHEN a.score >= 70 THEN 1 END) as passed_count
       FROM quest_type qt
       LEFT JOIN questions q ON qt.idquest_type = q.quest_type
       LEFT JOIN answer a ON q.id_quest = a.ans_question_id
       GROUP BY qt.idquest_type, qt.type
       ORDER BY avg_score DESC
   ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
   $error_message = "Statistikanı yükləmək mümkün olmadı: " . $e->getMessage();
}

if (!isset($hardQuestions)) $hardQuestions = [];
if (!isset($userStats)) $userStats = [];
if (!isset($branchStats)) $branchStats = [];
if (!isset($topicStats)) $topicStats = [];
?>

<!DOCTYPE html>
<html lang="az">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Statistika - Rabite Exam</title>
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
   <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <style>
       :root {
           --primary-color: #2ecc71;
           --secondary-color: #27ae60;
       }
       .sidebar {
           background-color: #2c3e50;
           min-height: 100vh;
           position: fixed;
           width: 250px;
       }
       .main-content {
           margin-left: 250px;
           padding: 20px;
       }
       .nav-link {
           color: white;
           padding: 10px 20px;
           margin: 5px;
           border-radius: 5px;
       }
       .nav-link:hover, .nav-link.active {
           background-color: var(--primary-color);
           color: white;
       }
       .card {
           border: none;
           border-radius: 10px;
           box-shadow: 0 0 15px rgba(0,0,0,0.1);
           margin-bottom: 20px;
       }
       .stat-card {
           padding: 20px;
           text-align: center;
           background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
           color: white;
       }
       .stat-number {
           font-size: 24px;
           font-weight: bold;
       }
   </style>
</head>
<body>
   <div class="d-flex">
       <?php include 'sidebar.php'; ?>

       <div class="main-content flex-grow-1">
           <div class="container-fluid">
               <h2 class="mb-4">Statistika Paneli</h2>

               <!-- Ümumi Statistika -->
               <div class="row mb-4">
                   <div class="col-md-3">
                       <div class="card stat-card">
                           <div class="stat-number">
                               <?php echo count($userStats); ?>
                           </div>
                           <div>İştirakçı Sayı</div>
                       </div>
                   </div>
                   <div class="col-md-3">
                       <div class="card stat-card">
                           <div class="stat-number">
                               <?php 
                                   $avgScore = array_sum(array_column($userStats, 'avg_score')) / (count($userStats) ?: 1);
                                   echo number_format($avgScore, 1);
                               ?>
                           </div>
                           <div>Ortalama Bal</div>
                       </div>
                   </div>
                   <div class="col-md-3">
                       <div class="card stat-card">
                           <div class="stat-number">
                               <?php echo count($hardQuestions); ?>
                           </div>
                           <div>Çətin Suallar</div>
                       </div>
                   </div>
                   <div class="col-md-3">
                       <div class="card stat-card">
                           <div class="stat-number">
                               <?php echo count($topicStats); ?>
                           </div>
                           <div>Mövzu Sayı</div>
                       </div>
                   </div>
               </div>

               <!-- Çətin Suallar -->
               <div class="card mb-4">
                   <div class="card-header">
                       <h5 class="mb-0">Ən Çətin Suallar</h5>
                   </div>
                   <div class="card-body">
                       <div class="table-responsive">
                           <table class="table">
                               <thead>
                                   <tr>
                                       <th>Sual</th>
                                       <th>Mövzu</th>
                                       <th>Cəhd Sayı</th>
                                       <th>Səhv Sayı</th>
                                       <th>Səhv %</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   <?php foreach ($hardQuestions as $question): ?>
                                       <tr>
                                           <td><?php echo htmlspecialchars($question['quest_text']); ?></td>
                                           <td><?php echo htmlspecialchars($question['topic_name']); ?></td>
                                           <td><?php echo $question['attempt_count']; ?></td>
                                           <td><?php echo $question['wrong_answers']; ?></td>
                                           <td><?php echo number_format($question['error_rate'], 1); ?>%</td>
                                       </tr>
                                   <?php endforeach; ?>
                               </tbody>
                           </table>
                       </div>
                   </div>
               </div>

               <!-- Qrafiklər -->
               <div class="row">
                   <div class="col-md-6">
                       <div class="card">
                           <div class="card-header">
                               <h5 class="mb-0">Filial Performansı</h5>
                           </div>
                           <div class="card-body">
                               <canvas id="branchChart"></canvas>
                           </div>
                       </div>
                   </div>
                   <div class="col-md-6">
                       <div class="card">
                           <div class="card-header">
                               <h5 class="mb-0">Mövzu Performansı</h5>
                           </div>
                           <div class="card-body">
                               <canvas id="topicChart"></canvas>
                           </div>
                       </div>
                   </div>
               </div>
           </div>
       </div>
   </div>

   <script>
       // Filial Qrafiki
       new Chart(document.getElementById('branchChart'), {
           type: 'bar',
           data: {
               labels: <?php echo json_encode(array_column($branchStats, 'work_place')); ?>,
               datasets: [{
                   label: 'Ortalama Bal',
                   data: <?php echo json_encode(array_column($branchStats, 'avg_score')); ?>,
                   backgroundColor: '#2ecc71'
               }]
           },
           options: {
               responsive: true,
               scales: {
                   y: {
                       beginAtZero: true,
                       max: 100
                   }
               }
           }
       });

       // Mövzu Qrafiki
       new Chart(document.getElementById('topicChart'), {
           type: 'bar',
           data: {
               labels: <?php echo json_encode(array_column($topicStats, 'topic_name')); ?>,
               datasets: [{
                   label: 'İştirakçı Sayı',
                   data: <?php echo json_encode(array_column($topicStats, 'unique_users')); ?>,
                   backgroundColor: '#3498db'
               }]
           },
           options: {
               responsive: true,
               scales: {
                   y: {
                       beginAtZero: true
                   }
               }
           }
       });
   </script>

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>