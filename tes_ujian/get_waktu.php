<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['nomor_tes'])) {
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

$nomor_tes = $_SESSION['nomor_tes'];
$ujian_id = $_GET['ujian_id'] ?? 0;

$log = $conn->query("
    SELECT waktu_mulai, waktu_sisa 
    FROM ujian_log 
    WHERE camaba_id = '$nomor_tes' 
    AND ujian_id = '$ujian_id'
    AND status = 'active'
")->fetch_assoc();

if ($log) {
    if ($log['waktu_mulai']) {
        $waktu_mulai = strtotime($log['waktu_mulai']);
        $waktu_sekarang = time();
        $durasi = 120 * 60; // default 120 menit
        
        // Ambil durasi dari setting
        $setting = $conn->query("SELECT durasi_menit FROM ujian_setting WHERE id = '$ujian_id'")->fetch_assoc();
        if ($setting) {
            $durasi = $setting['durasi_menit'] * 60;
        }
        
        $waktu_berlalu = $waktu_sekarang - $waktu_mulai;
        $sisa_waktu = max(0, $durasi - $waktu_berlalu);
        
        echo json_encode(['waktu' => $sisa_waktu]);
    } else {
        echo json_encode(['waktu' => $log['waktu_sisa'] ?? 0]);
    }
} else {
    echo json_encode(['waktu' => 0, 'error' => 'Log tidak ditemukan']);
}
?>