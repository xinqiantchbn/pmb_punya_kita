<?php
// ubah_password.php
require_once '../config/session.php';
require_once '../config/database.php';
requireLogin();

$camaba_id = $_SESSION['camaba_id'];
$message = '';
$message_type = '';

// Ambil data camaba untuk informasi
$sql = "SELECT * FROM camaba WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $camaba_id);
$stmt->execute();
$result = $stmt->get_result();
$camaba = $result->fetch_assoc();
$stmt->close();

// Proses Ubah Password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi input
    $errors = [];
    
    // 1. Cek password lama
    if (!password_verify($current_password, $camaba['password'])) {
        $errors[] = "Password saat ini salah!";
    }
    
    // 2. Cek konfirmasi password baru
    if ($new_password !== $confirm_password) {
        $errors[] = "Password baru tidak cocok!";
    }
    
    // 3. Validasi kekuatan password
    if (strlen($new_password) < 8) {
        $errors[] = "Password minimal 8 karakter!";
    }
    
    // 4. Validasi password tidak boleh sama dengan yang lama
    if (password_verify($new_password, $camaba['password'])) {
        $errors[] = "Password baru tidak boleh sama dengan password lama!";
    }
    
    if (empty($errors)) {
        // Hash password baru
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password di database
        $update_sql = "UPDATE camaba SET password = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $hashed_password, $camaba_id);
        
        if ($update_stmt->execute()) {
            $message = "Password berhasil diubah! Silakan login ulang.";
            $message_type = "success";
            
            // Logout otomatis setelah 3 detik
            echo '<script>
                setTimeout(function() {
                    window.location.href = "../config/logout.php?password_changed=true";
                }, 3000);
            </script>';
        } else {
            $message = "Gagal mengubah password!";
            $message_type = "danger";
        }
        $update_stmt->close();
    } else {
        $message = implode("<br>", $errors);
        $message_type = "danger";
    }
}

// Check for message from URL parameter
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $message_type = isset($_GET['type']) ? $_GET['type'] : 'info';
}
?>
