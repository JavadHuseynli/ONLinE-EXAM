<?php
// login.php
session_start();
require_once "config.php";

// If already logged in, redirect
if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if($_SESSION['role'] == 2) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$message = "";

if($_POST) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if(empty($email) || empty($password)) {
        $message = "Bütün xanaları doldurun!";
    } else {
        $login_result = $user->login($email, $password);
        
        if($login_result == 2) {  // Admin
            header("Location: admin/dashboard.php");
            exit();
        } elseif($login_result == 1) {  // Regular user
            header("Location: user/dashboard.php");
            exit();
        } else {
            $message = "Email və ya şifrə yanlışdır!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rabite Exam - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 15px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background: #2ecc71;
            color: white;
            text-align: center;
            padding: 1.5rem;
            border-radius: 10px 10px 0 0;
            border: none;
        }
        .form-control {
            height: 46px;
            border-radius: 8px;
        }
        .form-control:focus {
            border-color: #2ecc71;
            box-shadow: 0 0 0 0.2rem rgba(46, 204, 113, 0.25);
        }
        .btn-login {
            background: #2ecc71;
            border: none;
            height: 46px;
            border-radius: 8px;
        }
        .btn-login:hover {
            background: #27ae60;
        }
        .alert {
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Rabite Exam</h4>
            </div>
            <div class="card-body p-4">
                <?php if($message): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifrə</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-login text-white w-100">Daxil ol</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>