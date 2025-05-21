<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

try {
    // First, clear existing admin user
    $clear = "DELETE FROM users WHERE email = 'admin@rabite.az'";
    $db->exec($clear);

    // Create new admin user with fresh password hash
    $username = 'Admin';
    $email = 'admin@rabite.az';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $role = 2;

    $query = "INSERT INTO users (username, email, password, role) 
              VALUES (:username, :email, :password, :role)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':role', $role);
    
    if($stmt->execute()) {
        echo "Admin user created successfully!<br>";
        echo "Login with:<br>";
        echo "Email: admin@rabite.az<br>";
        echo "Password: admin123";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>