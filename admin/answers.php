<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] != 2) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Fetch answers
try {
    $stmt = $db->query("SELECT  a.* , u.username as username , q.quest_text as quest_text from answer  a inner  join  users  u   on u.iduser = a.ans_user_id inner join questions q  on q.id_quest = a.ans_question_id");
    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Cavabları yükləmək mümkün olmadı: " . $e->getMessage();
    $answers = [];
}

?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cavablar - Rabite Exam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        }
        .btn-custom {
            background-color: var(--primary-color);
            color: white;
        }
        .btn-custom:hover {
            background-color: var(--secondary-color);
            color: white;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar py-3">
            <div class="text-center mb-4">
                <h4 class="text-white">Admin Panel</h4>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link " href="dashboard.php">
                        <i class="fas fa-home me-2"></i> Ana Səhifə
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users me-2"></i> İştirakçılar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="questions.php">
                        <i class="fas fa-question-circle me-2"></i> Suallar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="roles.php">
                        <i class="fas fa-user-tag me-2"></i> Rollar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="answers.php">
                        <i class="fas fa-check-circle me-2"></i> Cavablar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="statistics.php">
                        <i class="fas fa-chart-bar me-2"></i> Statistika
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user me-2"></i> Profil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Çıxış
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Cavablar</h2>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                     
                                        <th>İstifadəçi </th>
                                        <th>Sual</th>
                                        <th>İstifadəçi Cavabı</th>
                                        <th>Düzgün Cavab</th>
                                        <th>Bal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($answers as $answer): ?>
                                    <tr>
                                        
                                        <td><?php echo htmlspecialchars($answer['username']); ?></td>
                                        <td><?php echo htmlspecialchars($answer['quest_text']); ?></td>
                                        <td><?php echo htmlspecialchars($answer['answer_user']); ?></td>
                                        <td><?php echo htmlspecialchars($answer['answer_corr']); ?></td>
                                        <td><?php echo htmlspecialchars($answer['score']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
