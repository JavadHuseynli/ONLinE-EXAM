<?php
// admin/dashboard.php
session_start();
require_once "../config.php";

// Check auth
if(!isset($_SESSION['logged_in']) || $_SESSION['role'] != 2) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Helper function to safely execute queries
function safeQuery($db, $query) {
    try {
        $stmt = $db->query($query);
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        return 0;
    }
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Rabite Exam</title>
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
                    <a class="nav-link active" href="dashboard.php">
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
                    <a class="nav-link" href="answers.php">
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

        <!-- Main Content Area -->
        <div class="main-content flex-grow-1">
            <!-- Top Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5>Ümumi İştirakçı</h5>
                            <h3 class="text-primary">
                                <?php echo safeQuery($db, "SELECT COUNT(*) FROM users where role = 1 and username<>'tester'"); ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5>Ümumi Sual</h5>
                            <h3 class="text-success">
                                <?php echo safeQuery($db, "SELECT COUNT(*) FROM questions"); ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5>Cavablar</h5>
                            <h3 class="text-warning">
                                <?php echo safeQuery($db, "SELECT COUNT(*) FROM answer"); ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5>Ortalama Bal</h5>
                            <h3 class="text-info">
                                <?php 
                                    $avg = safeQuery($db, "SELECT AVG(score) FROM answer");
                                    echo number_format($avg, 1);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Son Fəaliyyətlər</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>İstifadəçi</th>
                                    <th>Fəaliyyət</th>
                                    <th>Tarix</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $db->query("
                                      SELECT 
                                            u.username, 
                                            a.answer_user, 
                                            a.score as individual_score,
                                            a.idanswer,
                                            (SELECT SUM(score) 
                                            FROM answer 
                                            WHERE ans_user_id = u.iduser) as total_score
                                        FROM answer a 
                                        JOIN users u ON a.ans_user_id = u.iduser 
                                        ORDER BY a.idanswer DESC limit 1
                                    ");
                                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<tr>
                                            <td>{$row['username']}</td>
                                            <td>İmtahan verdi - {$row['total_score ']} bal</td>
                                            <td>" . date('d.m.Y H:i') . "</td>
                                        </tr>";
                                    }
                                } catch(PDOException $e) {
                                    echo "<tr><td colspan='3' class='text-center'>Məlumat tapılmadı</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>