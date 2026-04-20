<?php
date_default_timezone_set('Asia/Jakarta');
require_once '../config/database.php';
require_once '../config/session.php';

$camaba_id = $_SESSION['camaba_id'];

// Ambil data camaba (untuk nomor_tes)
$sql = "SELECT * FROM camaba WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $camaba_id);
$stmt->execute();
$result = $stmt->get_result();
$camaba = $result->fetch_assoc();
$stmt->close();

if (!$camaba) {
    die("Data peserta tidak ditemukan");
}

$nomor_tes = $camaba['nomor_tes']; // Ini yang akan dicocokkan dengan camaba_id di ujian_log

// Cek apakah ada ujian aktif
$now = date('Y-m-d H:i:s');
$today = date('Y-m-d');
$jam_sekarang = date('H:i:s');

$query_ujian = "SELECT * FROM ujian_setting 
                WHERE status = 'active' 
                AND tanggal_ujian = ? 
                AND jam_mulai <= ? 
                AND jam_selesai >= ?
                ORDER BY id DESC LIMIT 1";

$stmt = $conn->prepare($query_ujian);
$stmt->bind_param("sss", $today, $jam_sekarang, $jam_sekarang);
$stmt->execute();
$result_ujian = $stmt->get_result();

if ($result_ujian->num_rows == 0) {
    $no_exam = true;
    $ujian = null;
} else {
    $ujian = $result_ujian->fetch_assoc();
    $no_exam = false;
}
$stmt->close();

// Proses token verification
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['token'])) {
    $input_token = trim(strtoupper($_POST['token']));
    
    // Cek apakah token cocok dengan token_ujian di ujian_setting
    if ($input_token === $ujian['token_ujian']) {
        $ujian_id = $ujian['id'];
        
        // CARI data ujian_log yang sudah ada (dibuat admin)
        // cocokkan camaba_id (di ujian_log) dengan nomor_tes (dari camaba)
        $query_check_log = "SELECT * FROM ujian_log 
                           WHERE camaba_id = ? 
                           AND ujian_id = ?";
        
        $stmt = $conn->prepare($query_check_log);
        $stmt->bind_param("si", $nomor_tes, $ujian_id);
        $stmt->execute();
        $result_check_log = $stmt->get_result();
        
        if ($result_check_log->num_rows == 0) {
            // Jika admin belum input data ujian_log untuk user ini
            $error = "Anda tidak terdaftar dalam ujian ini. Hubungi administrator.";
        } else {
            // Data ujian_log ditemukan (dibuat admin)
            $existing_log = $result_check_log->fetch_assoc();
            
            // Cek jika sudah completed
            if ($existing_log['status'] == 'completed') {
                $_SESSION['error'] = 'Anda sudah menyelesaikan ujian ini.';
                header('Location: ../index.php');
                exit();
            }
            
            // UPDATE data yang sudah ada (bukan insert baru)
            $start_time = date('Y-m-d H:i:s');
            $ip_address = $_SERVER['REMOTE_ADDR'];
            
            $query_update_log = "UPDATE ujian_log 
                                SET start_time = ?, 
                                    status = 'active', 
                                    ip_address = ?
                                WHERE id = ?";
            
            $stmt_update = $conn->prepare($query_update_log);
            $stmt_update->bind_param("ssi", $start_time, $ip_address, $existing_log['id']);
            
            if ($stmt_update->execute()) {
                // Simpan data ke session
                $_SESSION['ujian_log_id'] = $existing_log['id'];
                $_SESSION['ujian_token_verified'] = true;
                $_SESSION['ujian_token'] = $input_token;
                $_SESSION['ujian_setting_id'] = $ujian_id;
                $_SESSION['ujian_start_time'] = $start_time;
                $_SESSION['ujian_durasi'] = $ujian['durasi_menit'];
                $_SESSION['nomor_tes'] = $nomor_tes;
                
                // Redirect ke halaman ujian
                header('Location: ujian.php');
                exit();
            } else {
                $error = "Gagal memulai ujian. Silakan hubungi administrator.";
            }
            $stmt_update->close();
        }
        $stmt->close();
    } else {
        $error = 'Token yang Anda masukkan salah. Silakan coba lagi.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Token Ujian - PMB Universitas Kita</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .token-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            border-top: 6px solid #d32f2f;
        }
        
        .token-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .token-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #d32f2f, #b71c1c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 32px;
        }
        
        .token-title {
            color: #b71c1c;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .exam-info {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #d32f2f;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .token-input {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 10px;
            text-align: center;
            text-transform: uppercase;
            height: 60px;
            border: 2px solid #ddd;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .token-input:focus {
            border-color: #d32f2f;
            box-shadow: 0 0 0 0.25rem rgba(211, 47, 47, 0.25);
        }
        
        .btn-submit-token {
            background: linear-gradient(135deg, #d32f2f, #b71c1c);
            color: white;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            width: 100%;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .btn-submit-token:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(211, 47, 47, 0.3);
        }
        
        .error-message {
            background-color: #ffe6e6;
            color: #d32f2f;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #d32f2f;
        }
        
        .no-exam-container {
            text-align: center;
            padding: 40px;
        }
        
        .no-exam-icon {
            font-size: 80px;
            color: #6c757d;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php if ($no_exam): ?>
    <!-- Jika tidak ada ujian aktif -->
    <div class="token-container">
        <div class="no-exam-container">
            <div class="no-exam-icon">
                <i class="fas fa-calendar-times"></i>
            </div>
            <h2 class="token-title mb-3">
                <?php echo $ujian ? 'Ujian Tidak Berlangsung' : 'Tidak Ada Ujian Aktif'; ?>
            </h2>
            
            <div class="exam-info">
                <p class="mb-4">Saat ini tidak ada ujian yang sedang berlangsung.</p>
                
                <?php if ($ujian): ?>
                <h6>Informasi Ujian:</h6>
                <ul class="list-unstyled">
                    <li><strong>Nama:</strong> <?php echo htmlspecialchars($ujian['nama_ujian']); ?></li>
                    <li><strong>Tanggal:</strong> <?php echo date('d/m/Y', strtotime($ujian['tanggal_ujian'])); ?></li>
                    <li><strong>Jam:</strong> <?php echo date('H:i', strtotime($ujian['jam_mulai'])); ?> - <?php echo date('H:i', strtotime($ujian['jam_selesai'])); ?></li>
                    <li><strong>Status:</strong> <?php echo strtoupper($ujian['status']); ?></li>
                </ul>
                <?php endif; ?>
            </div>
            
            <a href="../index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Beranda
            </a>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Jika ada ujian aktif -->
    <div class="token-container">
        <div class="token-header">
            <div class="token-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h1 class="token-title">Verifikasi Token Ujian</h1>
            <p class="text-muted">Masukkan token untuk mengaktifkan ujian Anda</p>
        </div>
        
        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="exam-info">
            <h6>Informasi Ujian:</h6>
            <ul class="list-unstyled">
                <li><strong>Nama Ujian:</strong> <?php echo htmlspecialchars($ujian['nama_ujian']); ?></li>
                <li><strong>Tanggal:</strong> <?php echo date('d/m/Y', strtotime($ujian['tanggal_ujian'])); ?></li>
                <li><strong>Waktu:</strong> <?php echo date('H:i', strtotime($ujian['jam_mulai'])); ?> - <?php echo date('H:i', strtotime($ujian['jam_selesai'])); ?></li>
                <li><strong>Durasi:</strong> <?php echo $ujian['durasi_menit']; ?> menit</li>
                <li><strong>Peserta:</strong> <?php echo htmlspecialchars($camaba['nama_lengkap']); ?></li>
                <li><strong>No. Tes:</strong> <?php echo htmlspecialchars($camaba['nomor_tes']); ?></li>
            </ul>
            <div class="alert alert-info mt-2 mb-0">
                <i class="fas fa-info-circle me-2"></i>
                Token akan mengubah status ujian Anda menjadi <strong>ACTIVE</strong> dan mulai menghitung waktu.
            </div>
        </div>
        
        <form method="POST" action="">
            <div class="mb-4">
                <label class="form-label">Token Ujian (6 karakter)</label>
                <input type="text" 
                       name="token" 
                       class="form-control token-input" 
                       placeholder="ABCDEF" 
                       maxlength="6" 
                       required
                       pattern="[A-Z0-9]{6}"
                       title="Masukkan 6 karakter token">
                <div class="form-text">Masukkan token 6 karakter yang diberikan</div>
            </div>
            
            <button type="submit" class="btn-submit-token">
                <i class="fas fa-play me-2"></i>Aktifkan & Mulai Ujian
            </button>
        </form>
        
        <div class="mt-3 text-center">
            <a href="../index.php" class="btn btn-link">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Beranda
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto focus on token input
        document.addEventListener('DOMContentLoaded', function() {
            const tokenInput = document.querySelector('input[name="token"]');
            if (tokenInput) {
                tokenInput.focus();
                
                // Auto uppercase
                tokenInput.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            }
        });
    </script>
</body>
</html>