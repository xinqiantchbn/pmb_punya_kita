<?php
// handle_setting.php - khusus menangani POST request

// Fungsi untuk generate token
function generateToken($length = 6) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[rand(0, $charactersLength - 1)];
    }
    return $token;
}

// Buat tabel ujian_setting jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS ujian_setting (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_ujian VARCHAR(100),
    tanggal_ujian DATE,
    jam_mulai TIME,
    jam_selesai TIME,
    durasi_menit INT DEFAULT 120,
    token_ujian VARCHAR(6),
    status ENUM('pending', 'active', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Ambil setting terbaru
$setting = $conn->query("SELECT * FROM ujian_setting ORDER BY id DESC LIMIT 1")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_ujian = $_POST['nama_ujian'];
    $tanggal_ujian = $_POST['tanggal_ujian'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $durasi_menit = $_POST['durasi_menit'];
    
    // Gunakan token manual jika ada, jika tidak generate baru
    $token_ujian = isset($_POST['manual_token']) && !empty($_POST['manual_token']) 
        ? strtoupper(substr($_POST['manual_token'], 0, 6))
        : generateToken(6);
    
    if ($setting) {
        // Update existing
        $sql = "UPDATE ujian_setting SET nama_ujian = ?, tanggal_ujian = ?, jam_mulai = ?, jam_selesai = ?, durasi_menit = ?, token_ujian = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssisi", $nama_ujian, $tanggal_ujian, $jam_mulai, $jam_selesai, $durasi_menit, $token_ujian, $setting['id']);
    } else {
        // Insert new
        $sql = "INSERT INTO ujian_setting (nama_ujian, tanggal_ujian, jam_mulai, jam_selesai, durasi_menit, token_ujian) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssis", $nama_ujian, $tanggal_ujian, $jam_mulai, $jam_selesai, $durasi_menit, $token_ujian);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Setting ujian berhasil disimpan! Token: $token_ujian";
    } else {
        $_SESSION['error'] = "Error: " . $stmt->error;
    }
    $stmt->close();
    
    // Redirect setelah proses POST
    header("Location: index.php?page=setting_ujian");
    exit();
}
?>