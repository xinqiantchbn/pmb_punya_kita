<?php
require_once '../config/session.php';
require_once '../config/database.php';
requireLogin();

$camaba_id = $_SESSION['camaba_id'];

// Ambil data camaba
$sql = "SELECT * FROM camaba WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $camaba_id);
$stmt->execute();
$result = $stmt->get_result();
$camaba = $result->fetch_assoc();
$stmt->close();

// Status text mapping
$statusText = [
    'belum_verifikasi' => 'Belum Verifikasi',
    'baru' => 'Menunggu',
    'aktif' => 'Aktif (Bisa Ujian)',
    'sudah_ujian' => 'Sudah Ujian',
    'lulus' => 'Lulus Seleksi',
    'gagal' => 'Tidak Lulus',
    'daftar_ulang' => 'Daftar Ulang'
];

// Status badge color
$statusColor = [
    'belum_verifikasi' => 'warning',
    'baru' => 'info',
    'sudah_ujian' => 'primary',
    'lulus' => 'success',
    'gagal' => 'danger',
    'daftar_ulang' => 'success'
];
?>