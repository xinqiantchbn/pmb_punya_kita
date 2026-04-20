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

// Ambil data camaba
$camaba = $conn->query("
    SELECT id, prodi_pilihan 
    FROM camaba 
    WHERE nomor_tes = '$nomor_tes'
")->fetch_assoc();

if (!$camaba) {
    echo json_encode(['success' => false, 'message' => 'Data camaba tidak ditemukan']);
    exit;
}

// Ambil log ujian
$log = $conn->query("
    SELECT * FROM ujian_log 
    WHERE camaba_id = '$nomor_tes' 
    AND ujian_id = '$ujian_id'
")->fetch_assoc();

if (!$log) {
    echo json_encode(['success' => false, 'message' => 'Data ujian tidak ditemukan']);
    exit;
}

// Ambil urutan soal
$soal_order = json_decode($log['soal_order'], true);
if (!is_array($soal_order)) {
    echo json_encode(['success' => false, 'message' => 'Urutan soal tidak ditemukan']);
    exit;
}

// Ambil semua soal
$ids_string = implode(',', array_map('intval', $soal_order));
$soal_list = $conn->query("
    SELECT id, jawaban_benar 
    FROM soal 
    WHERE id IN ($ids_string)
");

$kunci_jawaban = [];
while ($s = $soal_list->fetch_assoc()) {
    $kunci_jawaban[$s['id']] = $s['jawaban_benar'];
}

// Hitung jawaban benar dan salah
$jawaban_benar = 0;
$jawaban_salah = 0;

foreach ($jawaban as $soal_id => $jawaban_user) {
    if (isset($kunci_jawaban[$soal_id])) {
        if ($jawaban_user == $kunci_jawaban[$soal_id]) {
            $jawaban_benar++;
        } else {
            $jawaban_salah++;
        }
    }
}

$total_soal = count($kunci_jawaban);
$nilai = $total_soal > 0 ? round(($jawaban_benar / $total_soal) * 100) : 0;

// Update ujian_log
$conn->query("
    UPDATE ujian_log 
    SET status = 'completed',
        end_time = NOW(),
        jawaban_benar = $jawaban_benar,
        jawaban_salah = $jawaban_salah,
        nilai_ujian = $nilai,
        jawaban_json = '" . $conn->real_escape_string(json_encode($jawaban)) . "',
        ditandai_json = '" . $conn->real_escape_string(json_encode($ditandai)) . "'
    WHERE id = '{$log['id']}'
");

// Update camaba - status diubah jadi 'sudah_ujian' TAPI SESSION TETAP ADA
$conn->query("
    UPDATE camaba 
    SET status = 'sudah_ujian',
        nilai_ujian = $nilai,
        jawaban_benar = $jawaban_benar,
        jawaban_salah = $jawaban_salah,
        tanggal_ujian = NOW()
    WHERE nomor_tes = '$nomor_tes'
");

// JANGAN HAPUS SESSION! Hanya hapus data yang tidak perlu
// Biarkan session tetap ada agar user tetap login

echo json_encode([
    'success' => true, 
    'message' => 'Ujian berhasil dikumpulkan',
    'redirect' => '../index.php' // Langsung ke index tanpa parameter
]);
?>