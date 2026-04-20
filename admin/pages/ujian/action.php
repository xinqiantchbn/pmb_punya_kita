<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

// Buat tabel ujian_log jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS ujian_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    camaba_id INT,
    token VARCHAR(50),
    start_time DATETIME,
    end_time DATETIME,
    status ENUM('active', 'completed', 'expired') DEFAULT 'active',
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (camaba_id) REFERENCES camaba(id) ON DELETE CASCADE
)");

if ($action == 'start') {
    // Update ujian_setting status
    $conn->query("UPDATE ujian_setting SET status = 'active' WHERE status != 'completed'");
    
    // Generate token dan kirim email
    $setting = $conn->query("SELECT * FROM ujian_setting ORDER BY id DESC LIMIT 1")->fetch_assoc();
    
    if ($setting) {
        // Kirim email ke semua peserta
        $peserta = $conn->query("SELECT * FROM camaba WHERE status IN ('baru', 'sudah_ujian')");
        
        while ($p = $peserta->fetch_assoc()) {
            // Simpan token untuk peserta
            $sql = "INSERT INTO ujian_log (camaba_id, token, start_time, status) 
                    VALUES (?, ?, NOW(), 'active') 
                    ON DUPLICATE KEY UPDATE token = VALUES(token)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $p['id'], $setting['token_ujian']);
            $stmt->execute();
            $stmt->close();
            
            // Kirim email (implementasi email bisa ditambahkan)
            // sendEmail($p['email'], 'Token Ujian', "Token ujian Anda: {$setting['token_ujian']}");
        }
        
        echo json_encode(['success' => true, 'message' => 'Ujian dimulai']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Setting ujian belum ada']);
    }
} elseif ($action == 'stop') {
    $conn->query("UPDATE ujian_setting SET status = 'completed'");
    echo json_encode(['success' => true, 'message' => 'Ujian dihentikan']);
} elseif ($action == 'send_tokens') {
    $setting = $conn->query("SELECT * FROM ujian_setting ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $count = 0;
    
    if ($setting) {
        $peserta = $conn->query("SELECT * FROM camaba WHERE status IN ('baru', 'sudah_ujian')");
        
        while ($p = $peserta->fetch_assoc()) {
            // Update token
            $sql = "INSERT INTO ujian_log (camaba_id, token) VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE token = VALUES(token)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $p['id'], $setting['token_ujian']);
            $stmt->execute();
            $stmt->close();
            $count++;
            
            // Kirim email
            // sendEmail($p['email'], 'Token Ujian', "Token ujian Anda: {$setting['token_ujian']}");
        }
        
        echo json_encode(['success' => true, 'count' => $count]);
    }
} elseif ($action == 'reset') {
    $id = $_GET['id'];
    $conn->query("DELETE FROM ujian_log WHERE camaba_id = $id");
    echo json_encode(['success' => true]);
}
?>