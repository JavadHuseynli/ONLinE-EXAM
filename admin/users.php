<?php
// admin/users.php
session_start();
require_once "../config.php";

if(!isset($_SESSION['logged_in']) || $_SESSION['role'] != 2) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// İstifadəçi əlavə etmə
if(isset($_POST['add_user'])) {
    try {
        $query = "INSERT INTO users (username, email, password, position, role, work_place,inter) 
                  VALUES (:username, :email, :password, :badge, :role, :work_place,:inter)";
        $stmt = $db->prepare($query);
        
        // $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $stmt->bindParam(':username', $_POST['username']);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':password', $_POST['password']);
        $stmt->bindParam(':badge', $_POST['badge']);
        $stmt->bindParam(':role', $_POST['role']);
        $stmt->bindParam(':work_place', $_POST['work_place']);
        $stmt->bindParam(':inter', $_POST['inter']);
        
        if($stmt->execute()) {
            $success_message = "İstifadəçi uğurla əlavə edildi";
        }
    } catch(PDOException $e) {
        $error_message = "Xəta baş verdi: " . $e->getMessage();
    }
}

// İstifadəçi silmə
if(isset($_POST['delete_user'])) {
    try {
        $query = "DELETE FROM users WHERE iduser = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $_POST['user_id']);
        if($stmt->execute()) {
            $success_message = "İstifadəçi silindi";
        }
    } catch(PDOException $e) {
        $error_message = "Xəta baş verdi";
    }
}

// İstifadəçi düzəliş
if(isset($_POST['edit_user'])) {
    try {
        $query = "UPDATE users SET 
                    username = :username,
                    email = :email,
                    password = :password,
                    position = :badge,
                    role = :role,
                    work_place = :work_place,
                    inter = :inter
                  WHERE iduser = :id";
                  
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':username', $_POST['username']);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':password', $_POST['password']);
        $stmt->bindParam(':badge', $_POST['badge']);
        $stmt->bindParam(':role', $_POST['role']);
        $stmt->bindParam(':work_place', $_POST['work_place']);
        $stmt->bindParam(':inter', $_POST['inter']);
        $stmt->bindParam(':id', $_POST['user_id']);
        
        if($stmt->execute()) {
            $success_message = "İstifadəçi məlumatları uğurla yeniləndi";
        }
    } catch(PDOException $e) {
        $error_message = "Xəta baş verdi: " . $e->getMessage();
    }
}

// Bütün istifadəçiləri əldə et
try {
    $stmt = $db->query("SELECT * FROM users ORDER BY iduser DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "İstifadəçiləri yükləmək mümkün olmadı";
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İstifadəçilər - Rabite Exam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Dashboard-dan olan eyni CSS styles burda da olacaq */
        :root {
            --primary-color: #2ecc71;
            --secondary-color: #27ae60;
        }
        .sidebar {
            background-color: #2c3e50;
            min-height: 100vh;
            position: fixed;
            width: 250px;
            z-index: 1000;
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

        /* Simplified and fixed responsive table styles */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        /* Fixed column styling - simplified approach */
        .fixed-column {
            position: sticky;
            left: 0;
            z-index: 2;
            background-color: #fff;
        }
        
        .fixed-column-header {
            position: sticky;
            left: 0;
            z-index: 3;
            background-color: #f8f9fa;
        }
        
        /* Search box styling */
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .search-input {
            padding-left: 35px;
            border-radius: 20px;
            border: 1px solid #ced4da;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(46, 204, 113, 0.25);
            border-color: var(--primary-color);
        }
        
        /* No results message */
        .no-results {
            display: none;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }
        
        /* Highlight search matches */
        .highlight {
            background-color: rgba(255, 255, 0, 0.3);
            padding: 2px;
            border-radius: 2px;
        }
        
        /* Table sorting styles */
        .sort-icons {
            display: inline-block;
            width: 1rem;
            text-align: center;
        }
        
        th {
            position: relative;
            user-select: none;
        }
        
        /* Media query for mobile screens */
        @media (max-width: 991.98px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .sidebar-toggle {
                display: block;
            }
            
            .sidebar-collapsed .sidebar {
                display: none;
            }
        }
        
        /* Sidebar toggle button */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1001;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
        }
    </style>
</head>
<body class="sidebar-expanded">
    <!-- Sidebar toggle button (for mobile) -->
    <button class="sidebar-toggle btn" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="d-flex">
        <!-- Sidebar - dashboard-dakı eyni sidebar -->
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
                    <a class="nav-link active" href="users.php">
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

        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>İştirakçılar</h2>
                    <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus me-2"></i>Yeni İştirakçı
                    </button>
                </div>

                <?php if(isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <!-- Search Box -->
                <div class="search-box mb-3">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="form-control search-input" id="searchInput" placeholder="Ada görə axtar..." autocomplete="off">
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="usersTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60">ID</th>
                                        <th width="150" class="fixed-column-header">Ad</th>
                                        <th width="180">Email</th>
                                        <th width="150">Parol</th>
                                        <th width="120">Filial</th>
                                        <th width="100">Rol</th>
                                        <th width="150">İş Yeri</th>
                                        <th width="180">Imtahan bloku</th>
                                        <th width="120">Əməliyyatlar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['iduser']; ?></td>
                                            <td class="fixed-column"><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['password']); ?></td>
                                            <td><?php echo $user['position']; ?></td>
                                            <td>
                                                <?php echo $user['role'] == 2 ? 'Admin' : 'İstifadəçi'; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['work_place']); ?></td>
                                            <td><?php echo htmlspecialchars($user['inter']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning me-1" onclick="editUser(<?php echo $user['iduser']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['iduser']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <!-- No results message -->
                            <div id="noResults" class="no-results">
                                <i class="fas fa-search me-2"></i> Nəticə tapılmadı
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni İştirakçı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Ad</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifrə</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Filial</label>
                            <input type="text" class="form-control" name="badge">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rol</label>
                            <select class="form-control" name="role">
                                <option value="1">İstifadəçi</option>
                                <option value="2">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">İş Yeri</label>
                            <input type="text" class="form-control" name="work_place">
                        </div>

                        <div class="mb-3">
                           <label class="form-label">Imtahan bloku</label>
                            <select class="form-control" name="inter">
                            <option value="7">ƏMƏLİYYATÇI Hüquqi şəxslər </option>
                            <option value="8">MXT</option>
                            <option value="9">Xəzinədar</option>
                            <option value="10">Kredit admin</option>
                            <option value="11">ipoteka</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
                        <button type="submit" name="add_user" class="btn btn-custom">Əlavə et</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">İstifadəçi Düzəliş</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label class="form-label">Ad</label>
                            <input type="text" class="form-control" name="username" id="edit_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifrə</label>
                            <input type="text" class="form-control" name="password" id="edit_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Filial</label>
                            <input type="text" class="form-control" name="badge" id="edit_badge">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rol</label>
                            <select class="form-control" name="role" id="edit_role">
                                <option value="1">İstifadəçi</option>
                                <option value="2">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">İş Yeri</label>
                            <input type="text" class="form-control" name="work_place" id="edit_work_place">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Imtahan bloku</label>
                            <select class="form-control" name="inter" id="edit_inter">
                                <option value="7">ƏMƏLİYYATÇI Hüquqi şəxslər</option>
                                <option value="8">MXT</option>
                                <option value="9">Xəzinədar</option>
                                <option value="10">Kredit admin</option>
                                <option value="11">ipoteka</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
                        <button type="submit" name="edit_user" class="btn btn-custom">Yadda saxla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Form (hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="user_id" id="deleteUserId">
        <input type="hidden" name="delete_user" value="1">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Added user data array for JavaScript access
        const userData = <?php echo json_encode($users); ?>;
        
        function deleteUser(userId) {
            if(confirm('Bu istifadəçini silmək istədiyinizə əminsiniz?')) {
                document.getElementById('deleteUserId').value = userId;
                document.getElementById('deleteForm').submit();
            }
        }

        function editUser(userId) {
            // Find the user in the userData array
            const user = userData.find(u => u.iduser == userId);
            
            if (user) {
                // Populate the form fields with user data
                document.getElementById('edit_user_id').value = user.iduser;
                document.getElementById('edit_username').value = user.username;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_password').value = user.password;
                document.getElementById('edit_badge').value = user.position;
                document.getElementById('edit_role').value = user.role;
                document.getElementById('edit_work_place').value = user.work_place;
                document.getElementById('edit_inter').value = user.inter;
                
                // Show the modal
                const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                editModal.show();
            } else {
                alert('İstifadəçi tapılmadı!');
            }
        }
        
        // Table sorting functionality
        function setupTableSorting() {
            const table = document.getElementById('usersTable');
            const headers = table.querySelectorAll('th');
            const tableBody = table.querySelector('tbody');
            const rows = tableBody.querySelectorAll('tr');
            
            // Track sort state for each column (1: ascending, -1: descending, 0: default/unsorted)
            const sortStates = Array(headers.length).fill(0);
            
            // Add sorting icons and click event to headers
            headers.forEach((header, index) => {
                // Create sorting icons container
                const iconContainer = document.createElement('span');
                iconContainer.className = 'ms-1 sort-icons';
                iconContainer.innerHTML = '<i class="fas fa-sort text-muted"></i>';
                
                // Add the icon to the header
                header.appendChild(iconContainer);
                header.style.cursor = 'pointer';
                
                // Add click event for sorting
                header.addEventListener('click', () => {
                    // Update the sort state for this column (cycle between ascending, descending, default)
                    // For this implementation, we'll just toggle between ascending and descending
                    sortStates[index] = sortStates[index] === 1 ? -1 : 1;
                    
                    // Update the header icons
                    headers.forEach((h, i) => {
                        const icons = h.querySelector('.sort-icons');
                        if (i === index) {
                            // Set active icon
                            if (sortStates[i] === 1) {
                                icons.innerHTML = '<i class="fas fa-sort-up"></i>';
                            } else if (sortStates[i] === -1) {
                                icons.innerHTML = '<i class="fas fa-sort-down"></i>';
                            }
                        } else {
                            // Reset other icons
                            icons.innerHTML = '<i class="fas fa-sort text-muted"></i>';
                            sortStates[i] = 0;
                        }
                    });
                    
                    // Get all rows as an array for sorting
                    const rowsArray = Array.from(rows);
                    
                    // Sort the array
                    rowsArray.sort((rowA, rowB) => {
                        // Get cell value from the relevant column
                        const cellA = rowA.querySelectorAll('td')[index].textContent.trim();
                        const cellB = rowB.querySelectorAll('td')[index].textContent.trim();
                        
                        // Special handling for columns that might contain numbers
                        // Detect if the cell contains a number
                        if (!isNaN(cellA) && !isNaN(cellB)) {
                            return (Number(cellA) - Number(cellB)) * sortStates[index];
                        } else {
                            // Regular string comparison
                            return cellA.localeCompare(cellB) * sortStates[index];
                        }
                    });
                    
                    // Clear the table body
                    while (tableBody.firstChild) {
                        tableBody.removeChild(tableBody.firstChild);
                    }
                    
                    // Add the sorted rows back to the table
                    rowsArray.forEach(row => {
                        tableBody.appendChild(row);
                    });
                });
            });
        }
        
        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const table = document.getElementById('usersTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            const noResults = document.getElementById('noResults');
            
            // Setup table sorting
            setupTableSorting();
            
            // Function to filter table based on search input
            function filterTable() {
                // Get search term and convert to lowercase
                const searchTerm = searchInput.value.toLowerCase();
                
                // Variable to track if any results are found
                let resultsFound = false;
                
                // Loop through all table rows
                for (let i = 0; i < rows.length; i++) {
                    // Get the second cell (Ad/Name column) in each row
                    const nameCell = rows[i].getElementsByTagName('td')[1];
                    
                    if (nameCell) {
                        // Get the text content of the name cell
                        const nameText = nameCell.textContent || nameCell.innerText;
                        
                        // Check if the search term is found in the name
                        if (nameText.toLowerCase().indexOf(searchTerm) > -1) {
                            // Show the row if the name matches
                            rows[i].style.display = '';
                            resultsFound = true;
                            
                            // Highlight the matching text if there's a search term
                            if (searchTerm.length > 0) {
                                // Store the original text
                                const originalText = nameText;
                                // Create a regular expression to find the search term (case insensitive)
                                const regex = new RegExp('(' + searchTerm + ')', 'gi');
                                // Replace the matching text with highlighted version
                                nameCell.innerHTML = originalText.replace(regex, '<span class="highlight">$1</span>');
                            } else {
                                // Reset to original text if search term is empty
                                nameCell.textContent = nameText;
                            }
                        } else {
                            // Hide the row if the name doesn't match
                            rows[i].style.display = 'none';
                        }
                    }
                }
                
                // Show or hide the "No results" message
                if (resultsFound) {
                    noResults.style.display = 'none';
                } else {
                    noResults.style.display = 'block';
                }
            }
            
            // Add event listener to search input
            searchInput.addEventListener('keyup', filterTable);
            
            // Initial search on page load (in case there's a value)
            filterTable();
            
            // Sidebar toggle functionality for mobile
            const sidebarToggle = document.getElementById('sidebarToggle');
            const body = document.body;
            
            sidebarToggle.addEventListener('click', function() {
                body.classList.toggle('sidebar-collapsed');
                body.classList.toggle('sidebar-expanded');
            });
            
            // Auto-collapse sidebar on small screens
            function checkScreenSize() {
                if (window.innerWidth < 992) {
                    body.classList.add('sidebar-collapsed');
                    body.classList.remove('sidebar-expanded');
                } else {
                    body.classList.remove('sidebar-collapsed');
                    body.classList.add('sidebar-expanded');
                }
            }
            
            // Initialize on page load
            checkScreenSize();
            
            // Listen for window resize
            window.addEventListener('resize', checkScreenSize);
        });</script>
        </body>
        </html>