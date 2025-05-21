<?php
session_start();
require_once "../config.php";

if(!isset($_SESSION['logged_in']) || $_SESSION['role'] != 2) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Fetch question types
try {
    $typeStmt = $db->query("SELECT DISTINCT quest_type FROM questions ORDER BY quest_type ASC");
    $questionTypes = $typeStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error_message = "Tipləri yükləmək mümkün olmadı: " . $e->getMessage();
    $questionTypes = [];
}

// Fetch questions
try {
    $stmt = $db->query("SELECT id_quest, quest_text, quest_v_A, quest_v_B, quest_v_C, quest_v_D, quest_v_Cor, quest_type, header FROM questions");
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Sualları yükləmək mümkün olmadı: " . $e->getMessage();
    $questions = [];
}

// Handle question addition
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $header = $_POST['header'] ?? '';
    $quest_text = $_POST['quest_text'] ?? '';
    $quest_v_A = $_POST['quest_v_A'] ?? '';
    $quest_v_B = $_POST['quest_v_B'] ?? '';
    $quest_v_C = $_POST['quest_v_C'] ?? '';
    $quest_v_D = $_POST['quest_v_D'] ?? '';
    $quest_v_Cor = $_POST['quest_v_Cor'] ?? '';
    $quest_type = $_POST['quest_type'] ?? '';

    if ($header && $quest_text && $quest_v_A && $quest_v_B && $quest_v_C && $quest_v_D && $quest_v_Cor) {
        try {
            $stmt = $db->prepare("INSERT INTO questions (header, quest_text, quest_v_A, quest_v_B, quest_v_C, quest_v_D, quest_v_Cor, quest_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$header, $quest_text, $quest_v_A, $quest_v_B, $quest_v_C, $quest_v_D, $quest_v_Cor, $quest_type]);
            header("Location: questions.php");
            exit();
        } catch (PDOException $e) {
            $error_message = "Sualları əlavə etmək mümkün olmadı: " . $e->getMessage();
        }
    } else {
        $error_message = "Bütün sahələri doldurun.";
    }
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suallar - Rabite Exam</title>
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
                    <a class="nav-link active" href="questions.php">
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
        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Suallar</h2>
                </div>

                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Yeni Sual Əlavə Et</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="header" class="form-label">Başlıq</label>
                                <input type="text" name="header" id="header" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="quest_text" class="form-label">Sual Mətni</label>
                                <input type="text" name="quest_text" id="quest_text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="quest_v_A" class="form-label">A Variantı</label>
                                <input type="text" name="quest_v_A" id="quest_v_A" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="quest_v_B" class="form-label">B Variantı</label>
                                <input type="text" name="quest_v_B" id="quest_v_B" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="quest_v_C" class="form-label">C Variantı</label>
                                <input type="text" name="quest_v_C" id="quest_v_C" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="quest_v_D" class="form-label">D Variantı</label>
                                <input type="text" name="quest_v_D" id="quest_v_D" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="quest_v_Cor" class="form-label">Düzgün Variant</label>
                                <input type="text" name="quest_v_Cor" id="quest_v_Cor" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="quest_type" class="form-label">Tip</label>
                                <select name="quest_type" id="quest_type" class="form-control">
                                    <option value="">Tip seçin</option>
                                    <?php foreach ($questionTypes as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Əlavə Et</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Başlıq</th>
                                        <th>Suallar Mətn</th>
                                        <th>A Variantı</th>
                                        <th>B Variantı</th>
                                        <th>C Variantı</th>
                                        <th>D Variantı</th>
                                        <th>Düzgün Variant</th>
                                        <th>Tip</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($questions as $question): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($question['id_quest']); ?></td>
                                        <td><?php echo htmlspecialchars($question['header']); ?></td>
                                        <td><?php echo htmlspecialchars($question['quest_text']); ?></td>
                                        <td><?php echo htmlspecialchars($question['quest_v_A']); ?></td>
                                        <td><?php echo htmlspecialchars($question['quest_v_B']); ?></td>
                                        <td><?php echo htmlspecialchars($question['quest_v_C']); ?></td>
                                        <td><?php echo htmlspecialchars($question['quest_v_D']); ?></td>
                                        <td><?php echo htmlspecialchars($question['quest_v_Cor']); ?></td>
                                        <td><?php echo htmlspecialchars($question['quest_type']); ?></td>
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
