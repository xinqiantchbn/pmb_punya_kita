<?php
session_start();
require_once '../../config/database.php';

// Cek jika user datang dari registrasi
if (!isset($_SESSION['verification_email'])) {
    header("Location: register.php");
    exit();
}

$error = '';
$success = '';

// Ambil data dari session
$email = $_SESSION['verification_email'];
$camaba_id = $_SESSION['verification_camaba_id'];

// Proses verifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_input = $_POST['kode_verifikasi'] ?? '';
    
    if (empty($kode_input)) {
        $error = "Masukkan kode verifikasi!";
    } else {
        // Cek kode verifikasi di database
        $sql = "SELECT kode_verifikasi, nomor_tes FROM camaba WHERE id = ? AND email = ? AND status = 'belum_verifikasi'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $camaba_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $camaba = $result->fetch_assoc();
            
            if ($camaba['kode_verifikasi'] == $kode_input) {
                // Update status menjadi 'baru'
                $update_sql = "UPDATE camaba SET status = 'baru', kode_verifikasi = NULL, tanggal_verifikasi = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $camaba_id);
                
                if ($update_stmt->execute()) {
                    $success = "Verifikasi berhasil! Akun Anda telah aktif.";
                    
                    // Set session untuk auto login
                    $_SESSION['camaba_id'] = $camaba_id;
                    $_SESSION['nomor_tes'] = $camaba['nomor_tes'];
                    $_SESSION['nama'] = ''; // Akan diambil dari database nanti
                    $_SESSION['status'] = 'baru';
                    
                    // Hapus session verifikasi
                    unset($_SESSION['verification_email']);
                    unset($_SESSION['verification_camaba_id']);
                    
                    // Redirect ke homepage setelah 3 detik
                    header("refresh:3;url=../../index.php");
                } else {
                    $error = "Terjadi kesalahan saat verifikasi: " . $conn->error;
                }
                
                $update_stmt->close();
            } else {
                $error = "Kode verifikasi salah!";
            }
        } else {
            $error = "Data verifikasi tidak ditemukan atau akun sudah diverifikasi.";
        }
        
        $stmt->close();
    }
}

// Ambil data camaba untuk ditampilkan
$sql = "SELECT nama_lengkap, nomor_tes FROM camaba WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $camaba_id);
$stmt->execute();
$result = $stmt->get_result();
$camaba_data = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Akun - PMB Universitas Kita</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* VARIABLES WARNA MERAH */
        :root {
            --primary-red: #c62828;
            --dark-red: #b71c1c;
            --light-red: #ff5252;
            --accent-red: #ff8a80;
            --bg-light: #fff5f5;
            --text-dark: #212121;
            --text-light: #757575;
            --white: #ffffff;
        }
        
        /* GLOBAL STYLES */
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--dark-red), var(--primary-red));
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        /* VERIFICATION CONTAINER */
        .verification-container {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(183, 28, 28, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
        }
        
        /* HEADER */
        .verification-header {
            background: linear-gradient(135deg, var(--dark-red), var(--primary-red));
            color: var(--white);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .verification-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
            opacity: 0.3;
        }
        
        .verification-icon {
            font-size: 3.5rem;
            color: var(--accent-red);
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .verification-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .verification-header p {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        /* BODY */
        .verification-body {
            padding: 40px 30px;
        }
        
        /* USER INFO */
        .user-info-card {
            background-color: var(--bg-light);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--accent-red);
        }
        
        .user-info-card h5 {
            color: var(--dark-red);
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .info-item {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .info-item i {
            color: var(--primary-red);
            margin-right: 10px;
            width: 20px;
        }
        
        .info-item span {
            color: var(--text-dark);
            font-weight: 500;
        }
        
        /* ALERT */
        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            font-size: 0.95rem;
            margin-bottom: 25px;
        }
        
        .alert-error {
            background-color: #ffebee;
            color: var(--primary-red);
            border-left: 4px solid var(--primary-red);
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        
        /* VERIFICATION FORM */
        .verification-form {
            margin-bottom: 30px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-red);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-group-custom .input-group-text {
            background-color: #ffebee;
            border: 2px solid #ffcdd2;
            border-right: none;
            color: var(--primary-red);
            padding: 12px 15px;
            border-radius: 10px 0 0 10px;
        }
        
        .input-group-custom .form-control {
            border: 2px solid #ffcdd2;
            border-left: none;
            padding: 12px 15px;
            font-size: 1.2rem;
            font-weight: 600;
            text-align: center;
            letter-spacing: 3px;
            border-radius: 0 10px 10px 0;
            transition: all 0.3s;
        }
        
        .input-group-custom .form-control:focus {
            border-color: var(--light-red);
            box-shadow: 0 0 0 3px rgba(255, 82, 82, 0.2);
        }
        
        /* BUTTON */
        .btn-verify {
            background: linear-gradient(135deg, var(--light-red), var(--primary-red));
            color: var(--white);
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-verify:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(198, 40, 40, 0.3);
            background: linear-gradient(135deg, var(--primary-red), var(--dark-red));
        }
        
        .btn-verify:active {
            transform: translateY(-1px);
        }
        
        /* INFO BOX */
        .info-box {
            background-color: var(--bg-light);
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
            border-left: 4px solid var(--accent-red);
        }
        
        .info-box h5 {
            color: var(--dark-red);
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .info-box ul {
            padding-left: 20px;
            margin-bottom: 0;
        }
        
        .info-box li {
            margin-bottom: 8px;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .info-box a {
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .info-box a:hover {
            color: var(--dark-red);
            text-decoration: underline;
        }
        
        /* FOOTER */
        .verification-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        /* ANIMATIONS */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .verification-container {
                border-radius: 15px;
            }
            
            .verification-header, .verification-body {
                padding: 30px 20px;
            }
            
            .verification-header h1 {
                font-size: 1.8rem;
            }
            
            .verification-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container fade-in">
        <!-- Header -->
        <div class="verification-header">
            <div class="verification-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1>Verifikasi Akun</h1>
            <p>Masukkan kode verifikasi yang dikirim ke email Anda</p>
        </div>
        
        <!-- Body -->
        <div class="verification-body">
            <!-- User Info -->
            <div class="user-info-card fade-in">
                <h5><i class="fas fa-user-check me-2"></i> Informasi Pendaftaran</h5>
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <span>Nama: <strong><?php echo htmlspecialchars($camaba_data['nama_lengkap']); ?></strong></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <span>Email: <strong><?php echo htmlspecialchars($email); ?></strong></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-id-card"></i>
                    <span>Nomor Tes: <strong><?php echo htmlspecialchars($camaba_data['nomor_tes']); ?></strong></span>
                </div>
            </div>
            
            <!-- Pesan Error/Sukses -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-custom alert-error fade-in" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-circle me-3"></i>
                        <div><?php echo $error; ?></div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-custom alert-success fade-in" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-3"></i>
                        <div>
                            <?php echo $success; ?>
                            <div class="mt-2">
                                <small class="d-block">
                                    <i class="fas fa-info-circle me-1"></i> 
                                    Anda akan dialihkan ke halaman utama dalam 3 detik...
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Form Verifikasi -->
                <div class="verification-form">
                    <form action="" method="POST" id="verificationForm">
                        <div class="mb-4">
                            <label for="kode_verifikasi" class="form-label">Kode Verifikasi (6 digit)</label>
                            <div class="input-group input-group-custom">
                                <span class="input-group-text">
                                    <i class="fas fa-key"></i>
                                </span>
                                <input type="text" 
                                       id="kode_verifikasi" 
                                       name="kode_verifikasi" 
                                       class="form-control" 
                                       placeholder="123456"
                                       maxlength="6"
                                       required
                                       autofocus>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-verify">
                            <i class="fas fa-check-circle me-2"></i> Verifikasi Akun
                        </button>
                    </form>
                </div>
                
                <!-- Informasi -->
                <div class="info-box fade-in">
                    <h5><i class="fas fa-info-circle me-2"></i> Informasi Verifikasi:</h5>
                    <ul class="mb-0">
                        <li>Kode verifikasi telah dikirim ke email <strong><?php echo htmlspecialchars($email); ?></strong></li>
                        <li>Kode berupa 6 digit angka (contoh: 123456)</li>
                        <li>Untuk development, kode verifikasi disimpan di file <code>email_verifikasi_*.txt</code></li>
                        <li>Setelah verifikasi, akun Anda akan aktif dan bisa login</li>
                        <li>Jika tidak menerima email, cek folder spam atau <a href="register.php">registrasi ulang</a></li>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Footer -->
            <div class="verification-footer">
                <p class="mb-2">© 2024 PMB Universitas Kita. Semua hak dilindungi.</p>
                <p class="mb-0">
                    <a href="../../index.php">
                        <i class="fas fa-home me-1"></i> Kembali ke Beranda
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto format kode verifikasi (hanya angka)
        document.getElementById('kode_verifikasi').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Auto submit jika sudah 6 digit
            if (this.value.length === 6) {
                document.getElementById('verificationForm').submit();
            }
        });
        
        // Form Validation
        document.getElementById('verificationForm').addEventListener('submit', function(e) {
            const kode = document.getElementById('kode_verifikasi').value.trim();
            
            if (!kode) {
                e.preventDefault();
                alert('Masukkan kode verifikasi!');
                document.getElementById('kode_verifikasi').focus();
                return false;
            }
            
            if (kode.length !== 6) {
                e.preventDefault();
                alert('Kode verifikasi harus 6 digit angka!');
                document.getElementById('kode_verifikasi').focus();
                return false;
            }
            
            if (!/^\d{6}$/.test(kode)) {
                e.preventDefault();
                alert('Kode verifikasi harus berupa angka!');
                document.getElementById('kode_verifikasi').focus();
                return false;
            }
            
            return true;
        });
        
        // Auto focus on kode field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('kode_verifikasi').focus();
        });
    </script>
</body>
</html>