<?php
// get_ujian_time.php - HANYA waktu global per hari
date_default_timezone_set('Asia/Jakarta');
session_start();
require_once '../config/database.php';

// Bersihkan output buffer
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');

// Ambil setting ujian aktif
$setting = $conn->query("SELECT * FROM ujian_setting WHERE status = 'active' LIMIT 1")->fetch_assoc();

if ($setting) {
    // Waktu server sekarang
    $current_time = time();
    
    // Batas akhir hari ujian (jam selesai)
    $end_datetime = $setting['tanggal_ujian'] . ' ' . $setting['jam_selesai'];
    $end_time = strtotime($end_datetime);
    
    // Hitung sisa waktu hingga batas akhir hari
    $remaining_seconds = $end_time - $current_time;
    
    // Jika waktu sudah lewat
    if ($remaining_seconds < 0) {
        $remaining_seconds = 0;
    }
    
    if ($remaining_seconds > 0) {
        $hours = floor($remaining_seconds / 3600);
        $minutes = floor(($remaining_seconds % 3600) / 60);
        $seconds = $remaining_seconds % 60;
        $remaining_time = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        
        $response = [
            'success' => true,
            'remaining' => $remaining_time,
            'total_seconds' => $remaining_seconds,
            'exam_name' => $setting['nama_ujian'],
            'status' => $setting['status'],
            'jam_mulai' => $setting['jam_mulai'],
            'jam_selesai' => $setting['jam_selesai'],
            'tanggal_ujian' => $setting['tanggal_ujian']
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Waktu ujian hari ini telah habis',
            'jam_selesai' => $setting['jam_selesai']
        ];
    }
} else {
    $response = [
        'success' => false, 
        'message' => 'Tidak ada ujian aktif'
    ];
}

echo json_encode($response);
exit();
?>