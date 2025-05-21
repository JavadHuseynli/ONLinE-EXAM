<?php
class Database {
    private $host = "localhost:3306";
    private $db_name = "rabite";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
            return null;
        }
    }
}

class User {
    private $conn;
    private $table_name = "users"; // Note: Make sure this matches your table name

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createUser($username, $email, $password, $role, $inter = null) {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                    (username, email, password, role, inter) VALUES 
                    (:username, :email, :password, :role, :inter)";
            
            $stmt = $this->conn->prepare($query);
            
            // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':inter', $inter);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function login($email, $password) {
        try {
            $query = "SELECT iduser, username, password, role, inter 
                      FROM " . $this->table_name . " 
                      WHERE email = :email";
    
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
    
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($row) {
                if ($password === $row['password']) {
                    // Şifrə doğrudur — sessiya dəyişənlərini təyin edirik
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_id'] = $row['iduser'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = $row['role'];
                    $_SESSION['inter'] = $row['inter'];
    
                    return $row['role']; // Müvəffəqiyyətli giriş
                }
            }
    
            return false; // İstifadəçi tapılmadı və ya şifrə yanlışdır
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
}
?>