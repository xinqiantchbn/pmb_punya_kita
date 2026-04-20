<?php
session_start();
require_once '../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['camaba_id'])) {
    header("Location: ../gate/login/login.php");
    exit;
}

$camaba_id = $_SESSION['camaba_id'];

// Ambil data camaba
$query = "SELECT * FROM camaba WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $camaba_id);
$stmt->execute();
$result = $stmt->get_result();
$camaba = $result->fetch_assoc();

if (!$camaba) {
    header("Location: ../gate/login/login.php");
    exit;
}

// Status text mapping
$statusText = [
    'belum_verifikasi' => 'Belum Verifikasi Email',
    'baru' => 'Pendaftar Baru',
    'aktif' => 'Aktif',
    'sudah_ujian' => 'Sudah Mengikuti Ujian',
    'lulus' => 'LULUS SELEKSI',
    'gagal' => 'Tidak Lulus Seleksi',
    'daftar_ulang' => 'Daftar Ulang',
    'selected' => 'Terpilih',
    'not_selected' => 'Tidak Terpilih'
];

// Status color mapping
$statusColor = [
    'belum_verifikasi' => 'warning',
    'baru' => 'info',
    'aktif' => 'success',
    'sudah_ujian' => 'primary',
    'lulus' => 'success',
    'gagal' => 'danger',
    'daftar_ulang' => 'info',
    'selected' => 'success',
    'not_selected' => 'secondary'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Calon Mahasiswa - PMB Universitas Kita</title>
    
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
            --success-green: #2e7d32;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        /* NAVBAR */
        .navbar-custom {
            background: linear-gradient(135deg, var(--dark-red), var(--primary-red));
            padding: 15px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--white) !important;
        }
        
        .navbar-brand span {
            font-weight: 400;
            font-size: 0.9rem;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            transition: all 0.3s;
            margin: 0 5px;
        }
        
        .nav-link:hover {
            color: var(--white) !important;
            transform: translateY(-2px);
        }
        
        /* PROFILE DROPDOWN */
        .profile-dropdown .dropdown-toggle {
            background: rgba(255,255,255,0.2);
            color: var(--white);
            border-radius: 50px;
            padding: 8px 20px;
            border: none;
        }
        
        .profile-dropdown .dropdown-toggle:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .profile-dropdown .dropdown-toggle::after {
            margin-left: 8px;
        }
        
        .dropdown-menu {
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border: none;
            margin-top: 10px;
        }
        
        .dropdown-item {
            padding: 10px 20px;
            transition: all 0.3s;
        }
        
        .dropdown-item:hover {
            background: var(--bg-light);
            color: var(--primary-red);
        }
        
        /* DASHBOARD CONTAINER */
        .dashboard-container {
            margin-top: 100px;
            margin-bottom: 60px;
            padding: 0 20px;
        }
        
        /* WELCOME CARD */
        .welcome-card {
            background: linear-gradient(135deg, var(--bg-light), var(--white));
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid rgba(198, 40, 40, 0.2);
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--light-red), var(--primary-red));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 30px;
        }
        
        .user-avatar i {
            font-size: 40px;
            color: var(--white);
        }
        
        .welcome-info h2 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--dark-red);
            margin-bottom: 10px;
        }
        
        .welcome-info p {
            margin-bottom: 5px;
            color: var(--text-light);
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            background: linear-gradient(135deg, var(--light-red), var(--primary-red));
            color: var(--white);
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        
        /* INFO CARDS */
        .info-card {
            background: var(--white);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(198, 40, 40, 0.1);
            height: 100%;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(198, 40, 40, 0.15);
            border-color: var(--light-red);
        }
        
        .info-icon {
            width: 60px;
            height: 60px;
            background: var(--bg-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .info-icon i {
            font-size: 28px;
            color: var(--primary-red);
        }
        
        .info-card h3 {
            font-size: 1rem;
            color: var(--text-light);
            margin-bottom: 10px;
        }
        
        .info-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark-red);
            margin-bottom: 8px;
        }
        
        /* ACTION BUTTONS */
        .action-btn {
            display: inline-flex;
            align-items: center;
            padding: 12px 28px;
            background: linear-gradient(135deg, var(--light-red), var(--primary-red));
            color: var(--white);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(198, 40, 40, 0.3);
            color: var(--white);
        }
        
        .action-btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
        }
        
        .action-btn-secondary:hover {
            box-shadow: 0 10px 20px rgba(108, 117, 125, 0.3);
        }
        
        /* TIMELINE CARD */
        .timeline-card {
            background: var(--white);
            border-radius: 20px;
            padding: 30px;
            margin-top: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(198, 40, 40, 0.1);
        }
        
        .timeline-card h3 {
            color: var(--dark-red);
            font-weight: 600;
            margin-bottom: 25px;
        }
        
        .timeline-item {
            display: flex;
            margin-bottom: 25px;
            position: relative;
        }
        
        .timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 40px;
            bottom: -25px;
            width: 2px;
            background: #e0e0e0;
        }
        
        .timeline-step {
            width: 40px;
            height: 40px;
            background: #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 20px;
            position: relative;
            z-index: 1;
        }
        
        .timeline-item.completed .timeline-step {
            background: var(--success-green);
            color: var(--white);
        }
        
        .timeline-item.current .timeline-step {
            background: var(--primary-red);
            color: var(--white);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .timeline-content {
            flex: 1;
        }
        
        .timeline-content h5 {
            margin-bottom: 5px;
            color: var(--text-dark);
        }
        
        .timeline-content p {
            margin-bottom: 5px;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        /* FOOTER */
        .footer {
            background: linear-gradient(135deg, var(--dark-red), var(--primary-red));
            color: var(--white);
            padding: 50px 0 20px;
            margin-top: 60px;
        }
        
        .footer h5 {
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .footer-links a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--white);
            transform: translateX(5px);
        }
        
        .contact-info p {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .contact-info i {
            width: 20px;
        }
        
        .copyright {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .dashboard-container {
                margin-top: 80px;
            }
            
            .welcome-card {
                text-align: center;
            }
            
            .user-avatar {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .action-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-graduation-cap me-2"></i>
                PMB <span>Universitas Kita</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"><i class="fas fa-bars text-white"></i></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#dashboard">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#informasi">Informasi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#timeline">Alur Saya</a>
                    </li>
                    
                    <!-- PROFILE DROPDOWN -->
                    <li class="nav-item ms-2 profile-dropdown">
                        <div class="dropdown">
                            <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars(explode(' ', $camaba['nama_lengkap'])[0]); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li> 
                                    <a class="dropdown-item" href="dashboard_camaba.php">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="profile.php">
                                        <i class="fas fa-user-edit"></i> Lihat Profil
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="ubah_password.php">
                                        <i class="fas fa-key"></i> Ubah Password
                                    </a>
                                </li>
                                <?php if ($camaba['status'] == 'sudah_ujian' || $camaba['status'] == 'lulus' || $camaba['status'] == 'gagal' || $camaba['status'] == 'daftar_ulang'): ?>
                                <li>
                                    <a class="dropdown-item" href="hasil_tes.php">
                                        <i class="fas fa-chart-bar"></i> Lihat Hasil Tes
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="../config/logout.php">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul> 
            </div>
        </div>
    </nav>

    <!-- DASHBOARD CONTENT -->
    <div class="dashboard-container" id="dashboard">
        <div class="container">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <div class="d-flex flex-column flex-md-row align-items-center">
                    <div class="user-avatar mb-3 mb-md-0">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="welcome-info text-center text-md-start">
                        <h2>Selamat Datang, <?php echo htmlspecialchars($camaba['nama_lengkap']); ?>!</h2>
                        <p>Nomor Tes: <strong><?php echo htmlspecialchars($camaba['nomor_tes'] ?? '-'); ?></strong></p>
                        <p>Program Studi: <strong><?php echo htmlspecialchars($camaba['prodi_pilihan']); ?></strong></p>
                        <div class="status-badge">
                            <i class="fas fa-user-check me-1"></i> 
                            <?php echo $statusText[$camaba['status']]; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Info Cards -->
            <div class="row g-4 mb-5" id="informasi">
                <div class="col-lg-3 col-md-6">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <h3>Nomor Tes</h3>
                        <div class="info-value"><?php echo htmlspecialchars($camaba['nomor_tes'] ?? '-'); ?></div>
                        <p>Gunakan nomor ini untuk login</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3>Program Studi</h3>
                        <div class="info-value"><?php echo htmlspecialchars($camaba['prodi_pilihan']); ?></div>
                        <p>Pilihan program studi Anda</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Status Pendaftaran</h3>
                        <div class="info-value">
                            <span class="badge bg-<?php echo $statusColor[$camaba['status']]; ?> px-3 py-2">
                                <?php echo $statusText[$camaba['status']]; ?>
                            </span>
                        </div>
                        <p>Status saat ini</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3>Tanggal Daftar</h3>
                        <div class="info-value">
                            <?php echo date('d/m/Y', strtotime($camaba['tanggal_daftar'])); ?>
                        </div>
                        <p>Tanggal pendaftaran</p>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-3">
                        <?php if ($camaba['status'] == 'baru' || $camaba['status'] == 'aktif'): ?>
                            <!-- Jika status baru/aktif, tampilkan tombol ujian -->
                            <a href="ujian.php" class="action-btn">
                                <i class="fas fa-pencil-alt me-2"></i> Ikuti Ujian
                            </a>
                        <?php elseif ($camaba['status'] == 'sudah_ujian' || $camaba['status'] == 'lulus' || $camaba['status'] == 'gagal' || $camaba['status'] == 'daftar_ulang'): ?>
                            <!-- Jika sudah ujian, tampilkan hasil -->
                            <a href="hasil_tes.php" class="action-btn">
                                <i class="fas fa-chart-bar me-2"></i> Lihat Hasil Tes
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($camaba['status'] == 'lulus'): ?>
                            <!-- Jika lulus, tampilkan daftar ulang -->
                            <a href="daftar_ulang.php" class="action-btn">
                                <i class="fas fa-clipboard-check me-2"></i> Daftar Ulang
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($camaba['status'] == 'daftar_ulang'): ?>
                            <!-- Jika daftar ulang, lanjutkan proses daftar ulang -->
                            <a href="daftar_ulang.php" class="action-btn">
                                <i class="fas fa-file-signature me-2"></i> Lengkapi Daftar Ulang
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($camaba['nim']): ?>
                            <!-- Jika sudah punya NIM -->
                            <a href="#" class="action-btn action-btn-secondary">
                                <i class="fas fa-id-card me-2"></i> NIM: <?php echo $camaba['nim']; ?>
                            </a>
                        <?php endif; ?>
                        
                        <a href="profile.php" class="action-btn action-btn-secondary">
                            <i class="fas fa-user-edit me-2"></i> Edit Profil
                        </a>
                        
                        <a href="ubah_password.php" class="action-btn action-btn-secondary">
                            <i class="fas fa-key me-2"></i> Ubah Password
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Timeline Alur Pendaftaran -->
            <div class="timeline-card" id="timeline">
                <h3><i class="fas fa-list-ol me-2"></i> Alur Pendaftaran Saya</h3>
                
                <?php
                // Timeline steps berdasarkan status (tanpa pembayaran terpisah)
                $timeline_steps = [
                    1 => ['title' => 'Registrasi Akun', 'desc' => 'Pendaftaran akun berhasil', 'completed' => $camaba['status'] != 'belum_verifikasi'],
                    2 => ['title' => 'Verifikasi Email', 'desc' => 'Email telah diverifikasi', 'completed' => $camaba['status'] != 'belum_verifikasi'],
                    3 => ['title' => 'Ujian Online', 'desc' => 'Ikuti ujian seleksi online', 'completed' => in_array($camaba['status'], ['sudah_ujian', 'lulus', 'gagal', 'daftar_ulang', 'selected', 'not_selected'])],
                    4 => ['title' => 'Pengumuman Hasil', 'desc' => 'Lihat hasil ujian Anda', 'completed' => in_array($camaba['status'], ['lulus', 'gagal', 'daftar_ulang', 'selected', 'not_selected'])],
                    5 => ['title' => 'Daftar Ulang & Pembayaran', 'desc' => 'Lengkapi data, upload dokumen, dan lakukan pembayaran', 'completed' => $camaba['is_daftar_ulang_complete'] == 1],
                    6 => ['title' => 'Terima NIM', 'desc' => 'Dapatkan Nomor Induk Mahasiswa', 'completed' => !empty($camaba['nim'])]
                ];
                
                // Tentukan step current
                $current_step = 1;
                if ($camaba['status'] == 'belum_verifikasi') $current_step = 2;
                if ($camaba['status'] == 'baru') $current_step = 3;
                if ($camaba['status'] == 'aktif') $current_step = 3;
                if ($camaba['status'] == 'sudah_ujian') $current_step = 4;
                if (in_array($camaba['status'], ['lulus', 'gagal'])) $current_step = 5;
                if ($camaba['status'] == 'daftar_ulang' && $camaba['is_daftar_ulang_complete'] == 0) $current_step = 5;
                if ($camaba['status'] == 'daftar_ulang' && $camaba['is_daftar_ulang_complete'] == 1) $current_step = 6;
                if (!empty($camaba['nim'])) $current_step = 6;
                ?>
                
                <?php foreach ($timeline_steps as $step => $data): ?>
                <div class="timeline-item 
                    <?php echo $data['completed'] ? 'completed' : ''; ?>
                    <?php echo $step == $current_step && !$data['completed'] ? 'current' : ''; ?>">
                    <div class="timeline-step"><?php echo $step; ?></div>
                    <div class="timeline-content">
                        <h5><?php echo $data['title']; ?></h5>
                        <p><?php echo $data['desc']; ?></p>
                        <?php if ($step == $current_step && !$data['completed']): ?>
                            <small class="text-warning"><i class="fas fa-clock me-1"></i> Langkah saat ini</small>
                        <?php elseif ($data['completed']): ?>
                            <small class="text-success"><i class="fas fa-check me-1"></i> Selesai</small>
                        <?php else: ?>
                            <small class="text-muted"><i class="fas fa-clock me-1"></i> Menunggu</small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Nilai Ujian (jika sudah ujian) -->
            <?php if (in_array($camaba['status'], ['sudah_ujian', 'lulus', 'gagal', 'daftar_ulang', 'selected', 'not_selected']) && $camaba['nilai_ujian'] > 0): ?>
            <div class="row mt-5">
                <div class="col-lg-6">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3>Hasil Ujian</h3>
                        <div class="info-value"><?php echo $camaba['nilai_ujian']; ?> / 100</div>
                        <p>Nilai akhir ujian Anda</p>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Jawaban Benar</span>
                                <span class="fw-bold text-success"><?php echo $camaba['jawaban_benar']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Jawaban Salah</span>
                                <span class="fw-bold text-danger"><?php echo $camaba['jawaban_salah']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Status Kelulusan</span>
                                <span class="badge bg-<?php echo $camaba['status'] == 'lulus' ? 'success' : ($camaba['status'] == 'gagal' ? 'danger' : 'warning'); ?>">
                                    <?php echo $camaba['status'] == 'lulus' ? 'LULUS' : ($camaba['status'] == 'gagal' ? 'TIDAK LULUS' : 'MENUNGGU'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="hasil_tes.php" class="action-btn w-100 text-center">
                                <i class="fas fa-chart-bar me-2"></i> Lihat Detail Hasil
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <h3>Informasi Penting</h3>
                        <p><strong>Catatan:</strong></p>
                        <ul class="mb-3 text-start">
                            <?php if ($camaba['status'] == 'lulus'): ?>
                                <li>Anda dinyatakan <strong class="text-success">LULUS</strong> seleksi</li>
                                <li>Segera lakukan daftar ulang untuk mendapatkan NIM</li>
                                <li>Batas waktu daftar ulang: 7 hari setelah pengumuman</li>
                            <?php elseif ($camaba['status'] == 'gagal'): ?>
                                <li>Anda dinyatakan <strong class="text-danger">TIDAK LULUS</strong> seleksi</li>
                                <li>Silakan coba lagi di gelombang berikutnya</li>
                                <li>Terus tingkatkan persiapan untuk seleksi mendatang</li>
                            <?php elseif ($camaba['status'] == 'daftar_ulang'): ?>
                                <li>Anda dinyatakan <strong class="text-success">LULUS</strong> seleksi</li>
                                <li>Segera lengkapi data daftar ulang untuk mendapatkan NIM</li>
                                <?php if ($camaba['is_daftar_ulang_complete'] == 0): ?>
                                    <li class="text-warning">⚠️ Data daftar ulang Anda belum lengkap</li>
                                <?php elseif (empty($camaba['nim'])): ?>
                                    <li class="text-warning">⚠️ NIM akan diberikan setelah daftar ulang selesai</li>
                                <?php endif; ?>
                            <?php elseif ($camaba['status'] == 'selected'): ?>
                                <li>Anda dinyatakan <strong class="text-success">TERPILIH</strong> dalam seleksi</li>
                                <li>Silakan tunggu informasi lebih lanjut</li>
                            <?php else: ?>
                                <li>Hasil ujian sedang diproses</li>
                                <li>Pengumuman akan tersedia dalam 3 hari kerja</li>
                            <?php endif; ?>
                            
                            <?php if (!empty($camaba['nim'])): ?>
                                <li class="text-success mt-2">
                                    <strong>✅ NIM Anda: <?php echo $camaba['nim']; ?></strong>
                                </li>
                                <li>Selamat! Anda telah resmi menjadi mahasiswa Universitas Kita</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <h5>PMB Universitas Kita</h5>
                    <p class="mb-4" style="color: rgba(255,255,255,0.8);">
                        Portal resmi penerimaan mahasiswa baru tahun akademik 2024/2025. 
                        Mari bergabung menjadi bagian dari kami.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube fa-lg"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h5>Tautan Cepat</h5>
                    <div class="footer-links">
                        <a href="../index.php">Beranda</a>
                        <a href="#dashboard">Dashboard</a>
                        <a href="#informasi">Informasi</a>
                        <a href="#timeline">Alur Saya</a>
                        <a href="profile.php">Profil Saya</a>
                        <a href="../config/logout.php">Logout</a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5>Kontak Kami</h5>
                    <div class="contact-info">
                        <p class="mb-3">
                            <i class="fas fa-map-marker-alt"></i>
                            Jl. Pendidikan No. 123, Kota Kita
                        </p>
                        <p class="mb-3">
                            <i class="fas fa-phone"></i>
                            (021) 1234-5678
                        </p>
                        <p class="mb-3">
                            <i class="fas fa-envelope"></i>
                            pmb@universitaskita.ac.id
                        </p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5>Status Akun</h5>
                    <div class="footer-links">
                        <a href="#" class="text-success">
                            <i class="fas fa-check-circle me-1"></i> Terverifikasi
                        </a>
                        <a href="#">
                            <i class="fas fa-id-card me-1"></i> <?php echo htmlspecialchars($camaba['nomor_tes'] ?? '-'); ?>
                        </a>
                        <a href="#">
                            <i class="fas fa-graduation-cap me-1"></i> <?php echo htmlspecialchars($camaba['prodi_pilihan']); ?>
                        </a>
                        <a href="#">
                            <i class="fas fa-chart-line me-1"></i> <?php echo $statusText[$camaba['status']]; ?>
                        </a>
                        <?php if (!empty($camaba['nim'])): ?>
                        <a href="#" class="text-warning">
                            <i class="fas fa-id-card me-1"></i> NIM: <?php echo $camaba['nim']; ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; 2024 PMB Universitas Kita. Semua hak dilindungi.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Smooth scrolling untuk anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>