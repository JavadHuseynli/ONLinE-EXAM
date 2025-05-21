<?php
// class/User.php

class User {
    private $conn;
    private $table_name = "user";
    
    const ROLE_ADMIN = 1;
    const ROLE_STUDENT = 2;
    
    public $iduser;
    public $username;
    public $email;
    public $password;
    public $badge;
    public $role;
    public $work_place;
    public $inter; // inter sütununu əlavə etdim
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Get all users
    public function getAllUsers() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // Get user by ID
    public function getUserById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE iduser = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Create new user
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                (username, email, password, badge, role, work_place, inter) 
                VALUES 
                (:username, :email, :password, :badge, :role, :work_place, :inter)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->work_place = htmlspecialchars(strip_tags($this->work_place));
        
        // Hash password
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        
        // Bind values
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":badge", $this->badge);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":work_place", $this->work_place);
        $stmt->bindParam(":inter", $this->inter); // inter dəyərini bağladım
        
        return $stmt->execute();
    }
    
    // Update user
    public function update() {
        $password_set = !empty($this->password) ? ", password = :password" : "";
        
        $query = "UPDATE " . $this->table_name . " 
                SET 
                    username = :username, 
                    email = :email, 
                    badge = :badge, 
                    role = :role, 
                    work_place = :work_place,
                    inter = :inter
                    {$password_set}
                WHERE iduser = :iduser";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->work_place = htmlspecialchars(strip_tags($this->work_place));
        
        // Bind values
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":badge", $this->badge);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":work_place", $this->work_place);
        $stmt->bindParam(":inter", $this->inter); // inter dəyərini bağladım
        $stmt->bindParam(":iduser", $this->iduser);
        
        // Hash and bind password if it was provided
        if(!empty($this->password)){
            $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
            $stmt->bindParam(":password", $password_hash);
        }
        
        return $stmt->execute();
    }
    
    // Delete user
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE iduser = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }
    
    // Session-da inter dəyərini saxlamaq üçün login metodu
    public function login($email, $password) {
        // Istifadəçini email-ə görə tap
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // İstifadəçi tapıldımı və şifrə doğrudurmu?
        if($user && password_verify($password, $user['password'])) {
            // Sessiyada məlumatları saxla
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['iduser'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['inter'] = $user['inter']; // inter dəyərini sessiyada saxla
            
            return $user['role'];
        }
        
        return false;
    }
}
?>