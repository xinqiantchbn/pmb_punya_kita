<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['nomor_tes'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

$nomor_tes = $_SESSION['nomor_tes'];
$ujian_id = $input['ujian_id'] ?? 0;
$jawaban = $input['jawaban'] ?? [];
$ditandai = $input['ditandai'] ?? [];

// Cek apakah ujian_log ada
$log = $conn->query("
    SELECT id FROM ujian_log 
    WHERE camaba_id = '$nomor_tes' 
    AND ujian_id = '$ujian_id'
")->fetch_assoc();

if ($log) {
    // Simpan jawaban ke database (Anda perlu menambahkan kolom jawaban_json di ujian_log)
    $jawaban_json = json_encode($jawaban);
    $ditandai_json = json_encode($ditandai);
    
    // ALTER TABLE ujian_log ADD COLUMN jawaban_json TEXT NULL, ADD COLUMN ditandai_json TEXT NULL;
    $conn->query("
        UPDATE ujian_log 
        SET jawaban_json = '$jawaban_json',
            ditandai_json = '$ditandai_json',
            updated_at = NOW()
        WHERE id = '{$log['id']}'
    ");
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Log tidak ditemukan']);
}
?>