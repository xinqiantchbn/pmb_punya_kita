<?php
// File: admin/pages/setting_ujian/handle_setting.php
session_start();
require_once '../../config/database.php';

// Pastikan user adalah admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Set header JSON
header('Content-Type: application/json');

if (!isset($_GET['ajax_action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit();
}

$action = $_GET['ajax_action'];

switch ($action) {
    case 'start_ujian':
        startUjianHandler($conn);
        break;
    case 'stop_ujian':
        stopUjianHandler($conn);
        break;
    case 'reset_ujian':
        resetUjianHandler($conn);
        break;
    case 'get_ujian_status':
        getUjianStatusHandler($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function startUjianHandler($conn) {
    // Cek apakah sudah ada ujian aktif
    $check_active = $conn->query("SELECT * FROM ujian_setting WHERE status = 'active' LIMIT 1");
    
    if ($check_active->num_rows > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Masih ada ujian aktif. Hentikan dulu ujian sebelumnya.'
        ]);
        exit();
    }
    
    // Ambil setting terbaru
    $setting = $conn->query("SELECT * FROM ujian_setting ORDER BY id DESC LIMIT 1")->fetch_assoc();
    
    if (!$setting) {
        echo json_encode(['success' => false, 'message' => 'Setting ujian belum ada. Buat setting terlebih dahulu.']);
        exit();
    }
    
    // Cek apakah setting sudah valid
    if (empty($setting['nama_ujian']) || empty($setting['tanggal_ujian']) || 
        empty($setting['jam_mulai']) || empty($setting['jam_selesai'])) {
        echo json_encode(['success' => false, 'message' => 'Setting ujian belum lengkap. Lengkapi semua field.']);
        exit();
    }
    
    // Generate token jika belum ada
    if (empty($setting['token_ujian'])) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $token = '';
        for ($i = 0; $i < 6; $i++) {
            $token .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        $conn->query("UPDATE ujian_setting SET token_ujian = '$token' WHERE id = '{$setting['id']}'");
        $setting['token_ujian'] = $token;
    }
    
    // Update status ujian menjadi active
    $update_result = $conn->query("UPDATE ujian_setting SET status = 'active' WHERE id = '{$setting['id']}'");
    
    if (!$update_result) {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupdate database: ' . $conn->error]);
        exit();
    }
    
    // Hitung jumlah peserta yang eligible
    $peserta_result = $conn->query("SELECT * FROM camaba WHERE status IN ('baru', 'belum_verifikasi')");
    $count = $peserta_result->num_rows;
    
    echo json_encode([
        'success' => true, 
        'message' => 'Ujian berhasil dimulai!', 
        'token' => $setting['token_ujian'],
        'count' => $count
    ]);
    exit();
}

function stopUjianHandler($conn) {
    // ... (implementasi seperti sebelumnya)
}

function resetUjianHandler($conn) {
    // ... (implementasi seperti sebelumnya)
}

function getUjianStatusHandler($conn) {
    // ... (implementasi seperti sebelumnya)
}
?>