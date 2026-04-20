<?php
require_once '../../config/database.php';

$setting = $conn->query("SELECT * FROM ujian_setting WHERE status = 'active' LIMIT 1")->fetch_assoc();

if ($setting) {
    // Hitung waktu tersisa
    $start_time = strtotime($setting['tanggal_ujian'] . ' ' . $setting['jam_mulai']);
    $end_time = strtotime($setting['tanggal_ujian'] . ' ' . $setting['jam_selesai']);
    $current_time = time();
    
    if ($current_time < $start_time) {
        $remaining = $start_time - $current_time;
        $status = 'Menunggu';
    } elseif ($current_time >= $start_time && $current_time <= $end_time) {
        $remaining = $end_time - $current_time;
        $status = 'Berjalan';
    } else {
        $remaining = 0;
        $status = 'Selesai';
    }
    
    // Format waktu
    $hours = floor($remaining / 3600);
    $minutes = floor(($remaining % 3600) / 60);
    $seconds = $remaining % 60;
    $remaining_time = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    
    // Ambil data peserta
    $participants = [];
    $result = $conn->query("
        SELECT c.id, ul.start_time, ul.ip_address, 
               TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(ul.start_time, INTERVAL {$setting['durasi_menit']} MINUTE)) as remaining_seconds
        FROM camaba c 
        LEFT JOIN ujian_log ul ON c.id = ul.camaba_id AND ul.status = 'active'
        WHERE c.status IN ('baru', 'sudah_ujian')
    ");
    
    while ($row = $result->fetch_assoc()) {
        if ($row['remaining_seconds'] > 0) {
            $hours = floor($row['remaining_seconds'] / 3600);
            $minutes = floor(($row['remaining_seconds'] % 3600) / 60);
            $seconds = $row['remaining_seconds'] % 60;
            $remaining_time_participant = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            $remaining_time_participant = '00:00:00';
        }
        
        $participants[] = [
            'id' => $row['id'],
            'start_time' => $row['start_time'] ? date('H:i', strtotime($row['start_time'])) : '-',
            'ip_address' => $row['ip_address'] ?: '-',
            'remaining_time' => $remaining_time_participant
        ];
    }
    
    echo json_encode([
        'remaining' => $remaining_time,
        'status' => $status,
        'participants' => $participants
    ]);
} else {
    echo json_encode(['remaining' => '00:00:00', 'status' => 'Tidak aktif']);
}
?>