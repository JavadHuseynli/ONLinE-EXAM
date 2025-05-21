<?php
// check_auth.php - put this in admin folder
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] != 2) {
    header("Location: ../login.php");
    exit();
}
?>