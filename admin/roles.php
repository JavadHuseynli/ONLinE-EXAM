<?php
// admin/roles.php
session_start();
require_once "../config.php";

if(!isset($_SESSION['logged_in']) || $_SESSION['role'] != 2) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Sual tipi əlavə etmə
if (isset($_POST['add_type'])) {
    try {
        // `time` inputundakı dəyəri alırıq
        $time = (int)$_POST['time']; // Rəqəm olaraq qəbul edirik
        
        // Tip adı daxil edilir
        $query = "INSERT INTO quest_type (type, questin_time) VALUES (:type, :questin_time)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':type', $_POST['type']);
        $stmt->bindParam(':questin_time', $time, PDO::PARAM_INT);
        
        if($stmt->execute()) {
            $success_message = "Sual tipi uğurla əlavə edildi";
        }
    } catch(PDOException $e) {
        $error_message = "Xəta baş verdi: " . $e->getMessage();
    }
}

// Sual tipini silmə
if(isset($_POST['delete_type'])) {
    try {
        $query = "DELETE FROM quest_type WHERE idquest_type = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_POST['type_id']);
        
        if($stmt->execute()) {
            $success_message = "Sual tipi silindi";
        }
    } catch(PDOException $e) {
        $error_message = "Bu tip istifadə olunur. Silmək mümkün deyil.";
    }
}

// Sual tiplərini əldə et
try {
    $stmt = $db->query("SELECT qt.*, COUNT(q.id_quest) as question_count 
                        FROM quest_type qt 
                        LEFT JOIN questions q ON qt.idquest_type = q.quest_type 
                        GROUP BY qt.idquest_type");
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Sual tiplərini yükləmək mümkün olmadı";
    $types = [];
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sual Tipləri - Rabite Exam</title>
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
        <!-- Sidebar remains same -->
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
                    <a class="nav-link active" href="roles.php">
                        <i class="fas fa-user-tag me-2"></i> Sual rolu
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
                    <h2>Sual Tipləri</h2>
                    <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#addTypeModal">
                        <i class="fas fa-plus me-2"></i>Yeni Sual Tipi
                    </button>
                </div>

                <?php if(isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tip Adı</th>
                                        <th>Sual Sayı</th>
                                        <th>Imtahan vaxtı (-dəq)</th>
                                        <th>Əməliyyatlar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($types as $type): ?>
                                        <tr>
                                            <td><?php echo $type['idquest_type']; ?></td>
                                            <td><?php echo htmlspecialchars($type['type']); ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo $type['question_count']; ?> sual
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($type['questin_time']) . " dəqiqə"; ?></td>

                                            <td>
                                                <button class="btn btn-sm btn-warning me-2" onclick="editType('<?php echo $type['idquest_type']; ?>', '<?php echo htmlspecialchars($type['type']); ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if($type['question_count'] == 0): ?>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteType(<?php echo $type['idquest_type']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
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

    <!-- Add Type Modal -->
    <div class="modal fade" id="addTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Sual Tipi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tip Adı</label>
                            <input type="text" class="form-control" name="type" required>
                        </div>
                       
                        <div class="mb-3">
                            <label class="form-label">Zaman</label>
                            <input type="number" class="form-control" name="time"  required>
                        </div>

                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
                        <button type="submit" name="add_type" class="btn btn-custom">Əlavə et</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Type Modal -->
    <div class="modal fade" id="editTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sual Tipini Düzəlt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="type_id" id="editTypeId">
                        <div class="mb-3">
                            <label class="form-label">Tip Adı</label>
                            <input type="text" class="form-control" name="type" id="editTypeName" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
                        <button type="submit" name="edit_type" class="btn btn-custom">Yadda Saxla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Type Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="type_id" id="deleteTypeId">
        <input type="hidden" name="delete_type" value="1">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteType(typeId) {
            if(confirm('Bu sual tipini silmək istədiyinizə əminsiniz?')) {
                document.getElementById('deleteTypeId').value = typeId;
                document.getElementById('deleteForm').submit();
            }
        }

        function editType(typeId, typeName) {
            document.getElementById('editTypeId').value = typeId;
            document.getElementById('editTypeName').value = typeName;
            new bootstrap.Modal(document.getElementById('editTypeModal')).show();
        }
    </script>
</body>
</html> 