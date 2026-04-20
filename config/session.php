<?php
// config/session.php
session_start();

// Cek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['camaba_id']);
}

// Cek apakah admin sudah login  
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Redirect jika belum login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../gate/login/login.php");
        exit();
    }
}

// Redirect jika sudah login
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header("Location: gate/login/login.php");
        exit();
    }
}
?>